<?php

namespace App\Jobs;

use App\Models\CreditTransaction;
use App\Models\VideoGeneration;
use App\Services\AI\WanImageToVideoService;
use App\Services\Credits\CreditService;
use App\Services\Storage\R2StorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Self-rescheduling polling job for Image-to-Video generations.
 *
 * Each invocation does ONE provider status check, then:
 *  - If done (succeeded/failed/cancelled/expired): updates DB and stops.
 *  - If still running: re-dispatches itself with a short delay.
 *  - If max total time exceeded: marks as failed and stops.
 *
 * This design survives queue worker restarts cleanly — a job that was
 * running when the worker stopped will be re-tried by the queue, and the
 * duplicate-dispatch guard in the controller prevents double-dispatch on page reload.
 */
class PollImageToVideoPredictionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Each individual poll attempt runs fast — well within any timeout. */
    public int $timeout = 60;

    /** @var int Only 1 try per dispatch; we re-dispatch explicitly on the happy path. */
    public int $tries = 1;

    /** Seconds between each re-dispatched poll */
    private const POLL_INTERVAL_SECONDS = 3;

    /** Max total re-dispatch attempts before giving up (600s / 3s = 200) */
    private const MAX_ATTEMPTS = 200;

    /** After this many consecutive provider-returned succeeded-but-empty-URL, treat as expired */
    private const MAX_EMPTY_OUTPUT_RETRIES = 10;

    public function __construct(
        public readonly string $generationId,
        public readonly int $attemptNumber = 1,
        public readonly int $emptyOutputCount = 0,
    ) {}

    public function handle(WanImageToVideoService $aiService, R2StorageService $r2Service): void
    {
        $generation = VideoGeneration::find($this->generationId);

        if (!$generation) {
            Log::warning("[I2V JOB STOPPED] Generation {$this->generationId} not found in database.");
            return;
        }

        // Exit immediately if already in a terminal or cancelled state
        if (in_array($generation->status, ['succeeded', 'failed', 'cancelled'], true)) {
            Log::info("[I2V JOB STOPPED] Generation {$this->generationId} is already in '{$generation->status}' status.");
            return;
        }

        if (empty($generation->prediction_id)) {
            Log::error("[I2V JOB FAILED] No prediction_id for generation {$this->generationId}. Marking as failed.");
            $generation->update([
                'status'         => 'failed',
                'error_message'  => 'Missing prediction identifier.',
                'job_dispatched' => false,
            ]);
            return;
        }

        // Timeout guard: if we've been polling too long, mark as timed out
        if ($this->attemptNumber > self::MAX_ATTEMPTS) {
            Log::warning("[I2V TIMEOUT] Generation {$this->generationId} exceeded maximum poll attempts ({$this->attemptNumber}/" . self::MAX_ATTEMPTS . "). Marking as failed.");
            $generation->update([
                'status'         => 'failed',
                'error_message'  => 'Video generation timed out after 10 minutes. Please try again.',
                'job_dispatched' => false,
            ]);
            $this->refundCreditsIfEligible($generation, 'Video generation timed out after 10 minutes');
            return;
        }

        $predictionId = $generation->prediction_id;

        Log::info("[I2V JOB START] Poll attempt {$this->attemptNumber}/" . self::MAX_ATTEMPTS . " for generation {$this->generationId} (Prediction ID: {$predictionId})");

        try {
            // --- Poll the provider API ---
            $statusData = $aiService->getPredictionStatus($predictionId);
            $currentStatus = $statusData['status'] ?? 'processing';
            $rawStatus = $statusData['raw_status'] ?? $currentStatus;
            $outputUrl = $statusData['output'] ?? null;

            Log::info("[I2V POLL ATTEMPT] Attempt {$this->attemptNumber} — Provider Status: '{$rawStatus}' (Normalized: '{$currentStatus}'), Output: " . ($outputUrl ? $outputUrl : 'none'));

            // --- 1. Handle Succeeded State ---
            if ($currentStatus === 'succeeded') {
                if (empty($outputUrl)) {
                    $newEmptyCount = $this->emptyOutputCount + 1;
                    Log::warning("[I2V RETRY] Prediction {$predictionId} marked succeeded but output URL is empty ({$newEmptyCount}/" . self::MAX_EMPTY_OUTPUT_RETRIES . "). Waiting for URL...");

                    if ($newEmptyCount >= self::MAX_EMPTY_OUTPUT_RETRIES) {
                        Log::error("[I2V FAILED] Prediction {$predictionId} returned succeeded but output URL was never provided. Marking as failed.");
                        $generation->update([
                            'status'         => 'failed',
                            'error_message'  => 'Video generation completed on provider but no output video URL was returned. Please try again.',
                            'job_dispatched' => false,
                        ]);
                        $this->refundCreditsIfEligible($generation, 'Provider returned empty output video URL');
                        return;
                    }

                    // Re-dispatch with incremented empty count
                    self::dispatch($this->generationId, $this->attemptNumber + 1, $newEmptyCount)
                        ->delay(now()->addSeconds(self::POLL_INTERVAL_SECONDS));
                    return;
                }

                // Check for cancellation before heavy download/upload
                $generation->refresh();
                if ($generation->status === 'cancelled') {
                    Log::info("[I2V CANCELLED] Generation {$this->generationId} was cancelled by user before R2 upload.");
                    return;
                }

                Log::info("[I2V SUCCEEDED] Prediction {$predictionId} succeeded. Downloading video and uploading to Cloudflare R2...");

                $upload = $r2Service->uploadVideoFromUrl(
                    $outputUrl,
                    $generation->user_id ?? 'guest',
                    $generation->id
                );

                // Final cancellation check after upload
                $generation->refresh();
                if ($generation->status === 'cancelled') {
                    Log::info("[I2V CANCELLED] Generation {$this->generationId} was cancelled by user during R2 upload.");
                    return;
                }

                $generation->update([
                    'video_path'     => $upload['path'],
                    'remote_url'     => $upload['url'],
                    'status'         => 'succeeded',
                    'error_message'  => null,
                    'job_dispatched' => false,
                    'expires_at'     => now()->addHours(24),
                ]);

                Log::info("[I2V COMPLETED] Generation {$this->generationId} successfully stored in Cloudflare R2: {$upload['url']}");
                return;
            }

            // --- 2. Handle Failed State ---
            if ($currentStatus === 'failed') {
                $error = $statusData['error'] ?? 'Video generation failed on provider.';
                $errorMessage = is_string($error) ? $error : (is_array($error) ? json_encode($error) : 'Video generation failed.');
                Log::warning("[I2V FAILED] Prediction {$predictionId} failed on provider: {$errorMessage}");
                
                $generation->update([
                    'status'         => 'failed',
                    'error_message'  => $errorMessage,
                    'job_dispatched' => false,
                ]);
                $this->refundCreditsIfEligible($generation, $errorMessage);
                return;
            }

            // --- 3. Handle Running State (starting / processing) ---
            if ($generation->status !== 'processing' && in_array($currentStatus, ['starting', 'processing'], true)) {
                $generation->update(['status' => 'processing', 'poll_attempts' => $this->attemptNumber]);
            } else {
                $generation->update(['poll_attempts' => $this->attemptNumber]);
            }

            Log::info("[I2V RETRY] Generation {$this->generationId} is '{$currentStatus}'. Scheduling attempt " . ($this->attemptNumber + 1) . " in " . self::POLL_INTERVAL_SECONDS . "s...");

            // Re-dispatch for next poll
            self::dispatch($this->generationId, $this->attemptNumber + 1, 0)
                ->delay(now()->addSeconds(self::POLL_INTERVAL_SECONDS));

        } catch (\Throwable $e) {
            Log::warning("[I2V POLL ERROR] Attempt {$this->attemptNumber} exception: {$e->getMessage()}", [
                'generation_id' => $this->generationId,
                'prediction_id' => $predictionId,
            ]);

            // Re-dispatch anyway on transient network errors
            self::dispatch($this->generationId, $this->attemptNumber + 1, $this->emptyOutputCount)
                ->delay(now()->addSeconds(self::POLL_INTERVAL_SECONDS));
        }
    }

    /**
     * Idempotently refund credits for a failed or timed out generation.
     */
    protected function refundCreditsIfEligible(VideoGeneration $generation, string $reason): void
    {
        if (!$generation->user_id) {
            return;
        }

        $alreadyRefunded = CreditTransaction::where('reference_id', $generation->id)
            ->where('type', 'generation_refund')
            ->exists();

        if ($alreadyRefunded) {
            return;
        }

        $cost = (int) config('credits.costs.image_to_video', 5);
        try {
            $creditService = app(CreditService::class);
            $creditService->refundCredits(
                user: $generation->user_id,
                amount: $cost,
                source: 'image_to_video',
                referenceId: $generation->id,
                description: "Refunded {$cost} credits for failed image-to-video {$generation->id}: {$reason}"
            );
            Log::info("[I2V REFUND SUCCESS] Refunded {$cost} credits for failed Generation {$generation->id}");
        } catch (\Throwable $e) {
            Log::error("[I2V REFUND FAILED] Could not refund credits for Generation {$generation->id}: {$e->getMessage()}");
        }
    }
}
