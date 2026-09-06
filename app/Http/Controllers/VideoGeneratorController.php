<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateVideoRequest;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Models\VideoGeneration;
use App\Services\AI\Contracts\VideoGenerationInterface;
use App\Services\Credits\CreditService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoGeneratorController extends Controller
{
    protected VideoGenerationInterface $videoService;
    protected CreditService $creditService;

    public function __construct(VideoGenerationInterface $videoService, CreditService $creditService)
    {
        $this->videoService = $videoService;
        $this->creditService = $creditService;
    }

    /**
     * Submit a new text-to-video generation request.
     */
    public function generate(GenerateVideoRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : null);

        if (!$userId) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required. Please sign in or register to generate videos.',
                'message' => 'Authentication required. Please sign in or register to generate videos.',
                'requires_auth' => true,
            ], 401);
        }

        // 1. Resolve User and check credit balance
        $user = $request->user() ?: User::where('id', $userId)->orWhere('email', session('supabase_user.email'))->first();
        $creditCost = (int) config('credits.costs.video_generation', 5);

        if (!$user || !$this->creditService->hasEnoughCredits($user, $creditCost)) {
            $currentBalance = $user ? $this->creditService->getBalance($user) : 0;
            return response()->json([
                'success' => false,
                'error' => 'insufficient_credits',
                'message' => "Insufficient credits. Generating a video requires {$creditCost} credit(s), but your current balance is {$currentBalance}.",
                'required_credits' => $creditCost,
                'current_balance' => $currentBalance,
            ], 402);
        }

        // 2. Atomically deduct credits before initiating prediction
        $promptPreview = Str::limit($validated['prompt'], 50);
        $creditTxn = $this->creditService->deductCredits(
            user: $user,
            amount: $creditCost,
            type: 'generation_deduction',
            source: 'video_generation',
            description: "Generation deduction for video: \"{$promptPreview}\""
        );

        try {
            // Initiate prediction via video service
            $prediction = $this->videoService->createPrediction($validated);

            // Compute dimensions for Pure Text-to-Video
            $width = (int) ($validated['width'] ?? 864);
            $height = (int) ($validated['height'] ?? 480);
            $aspectRatio = $validated['aspect_ratio'] ?? '16:9';

            if ($aspectRatio === '16:9') {
                $width = 864;
                $height = 480;
            } elseif ($aspectRatio === '9:16') {
                $width = 480;
                $height = 864;
            } elseif ($aspectRatio === '4:3') {
                $width = 768;
                $height = 576;
            } elseif ($aspectRatio === '21:9') {
                $width = 1024;
                $height = 432;
            } elseif ($aspectRatio === '1:1') {
                $width = 512;
                $height = 512;
            }

            // Create record in database with 24h expiration
            $videoGeneration = VideoGeneration::create([
                'user_id' => $userId,
                'prediction_id' => $prediction['prediction_id'],
                'prompt' => $validated['prompt'],
                'aspect_ratio' => $aspectRatio,
                'width' => $width,
                'height' => $height,
                'steps' => (int) ($validated['steps'] ?? 30),
                'crf' => (int) ($validated['crf'] ?? 19),
                'flow_shift' => (int) ($validated['flow_shift'] ?? 9),
                'frame_rate' => (int) ($validated['frame_rate'] ?? 24),
                'guidance_scale' => (float) ($validated['guidance_scale'] ?? 6.0),
                'denoise_strength' => (float) ($validated['denoise_strength'] ?? 0.85),
                'model_version' => $prediction['version'] ?? '6c9132aee14409cd6568d030453f1ba50f5f3412b844fe67f78a9eb62d55664f',
                'status' => strtolower($prediction['status'] ?? 'starting'),
                'expires_at' => now()->addHours(24),
            ]);

            // Link credit transaction reference to local video generation ID
            $creditTxn->update(['reference_id' => $videoGeneration->id]);

            Log::info("[VIDEO CREATE]\n" .
                "- Local Generation ID: {$videoGeneration->id}\n" .
                "- API Prediction ID: {$videoGeneration->prediction_id}\n" .
                "- Complete API Response: " . json_encode($prediction['raw'] ?? $prediction));

            return response()->json([
                'success' => true,
                'message' => 'Video generation started',
                'generation' => $videoGeneration,
                'credit_balance' => $this->creditService->getBalance($user),
            ], 201);
        } catch (Exception $e) {
            Log::error('[VIDEO CREATE FAILED] ' . $e->getMessage());

            // Safely refund deducted credits on AI invocation failure
            try {
                $this->creditService->refundCredits(
                    user: $user,
                    amount: $creditCost,
                    source: 'video_generation',
                    referenceId: (string) $creditTxn->id,
                    description: "Refunded {$creditCost} credits due to AI video service creation failure: " . $e->getMessage()
                );
            } catch (Exception $refundEx) {
                Log::error('[REFUND FAILED] ' . $refundEx->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'An error occurred while starting video generation.',
            ], 500);
        }
    }

    /**
     * Poll status of an ongoing video generation.

     */
    public function status(string $id): JsonResponse
    {
        $videoGeneration = VideoGeneration::find($id);

        if (!$videoGeneration) {
            return response()->json([
                'success' => false,
                'message' => 'Video generation task not found.',
            ], 404);
        }

        // If already succeeded, failed, or cancelled, return directly
        if (in_array($videoGeneration->status, ['succeeded', 'failed', 'cancelled'])) {
            return response()->json([
                'success' => true,
                'generation' => $videoGeneration,
            ]);
        }

        try {
            $predictionStatus = $this->videoService->getPredictionStatus($videoGeneration->prediction_id);
            $status = strtolower($predictionStatus['status'] ?? 'processing');
            $httpStatus = $predictionStatus['http_status'] ?? 200;
            $rawBody = $predictionStatus['raw_body'] ?? json_encode($predictionStatus['raw'] ?? []);
            $outputUrl = $predictionStatus['output'] ?? null;

            Log::info("[VIDEO POLL]\n" .
                "- Local Generation ID: {$videoGeneration->id}\n" .
                "- Prediction ID: {$videoGeneration->prediction_id}\n" .
                "- HTTP Status: {$httpStatus}\n" .
                "- Raw Response Body: {$rawBody}\n" .
                "- Parsed API Status: {$status}\n" .
                "- Parsed Output URL: " . ($outputUrl ?: 'null'));

            // RACE-CONDITION PROTECTION: Check if generation was cancelled during poll
            $fresh = VideoGeneration::find($id);
            if (!$fresh || $fresh->status === 'cancelled') {
                Log::info("[VIDEO POLL IGNORED] Video {$id} is cancelled.");
                return response()->json([
                    'success' => true,
                    'generation' => $fresh ?: $videoGeneration,
                ]);
            }

            if ($status === 'succeeded' && !empty($outputUrl)) {
                $localPath = null;

                try {
                    // Download and cache video locally
                    $localPath = $this->videoService->downloadAndStoreVideo($outputUrl);
                } catch (Exception $dlEx) {
                    Log::warning("Could not cache video locally, fallback to remote URL: " . $dlEx->getMessage());
                }

                // Final check before saving
                $fresh = VideoGeneration::find($id);
                if ($fresh && $fresh->status === 'cancelled') {
                    Log::info("[VIDEO SAVE IGNORED] Video {$id} was cancelled during download.");
                    return response()->json([
                        'success' => true,
                        'generation' => $fresh,
                    ]);
                }

                $videoGeneration->update([
                    'status' => 'succeeded',
                    'remote_url' => $outputUrl,
                    'video_path' => $localPath,
                    'error_message' => null,
                ]);

                Log::info("[VIDEO FINAL]\n" .
                    "- Local Generation ID: {$videoGeneration->id}\n" .
                    "- Status: succeeded\n" .
                    "- Output URL: {$outputUrl}\n" .
                    "- Downloaded Local File Path: " . ($localPath ?: 'none') . "\n" .
                    "- Database Status: " . $videoGeneration->fresh()->status);

            } elseif ($status === 'failed') {
                $errorMessage = $predictionStatus['error'] ?? 'Video generation failed on provider.';
                $videoGeneration->update([
                    'status' => 'failed',
                    'error_message' => $errorMessage,
                ]);

                Log::warning("[VIDEO FINAL]\n" .
                    "- Local Generation ID: {$videoGeneration->id}\n" .
                    "- Status: failed\n" .
                    "- Error: {$errorMessage}\n" .
                    "- Database Status: " . $videoGeneration->fresh()->status);
            } else {
                $fresh = VideoGeneration::find($id);
                if ($fresh && $fresh->status !== 'cancelled') {
                    $videoGeneration->update([
                        'status' => $status,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'generation' => $videoGeneration->fresh(),
            ]);
        } catch (Exception $e) {
            Log::warning("[VIDEO POLL WARNING] for {$id}: " . $e->getMessage());

            return response()->json([
                'success' => true,
                'is_retrying' => true,
                'message' => 'Rendering video motion frames...',
                'generation' => $videoGeneration->fresh(),
            ]);
        }
    }

    /**
     * Cancel an active video generation.
     */
    public function cancel(string $id, Request $request): JsonResponse
    {
        $videoGeneration = VideoGeneration::find($id);

        if (!$videoGeneration) {
            return response()->json([
                'success' => false,
                'message' => 'Video generation not found.',
            ], 404);
        }

        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : session()->getId());

        if ($videoGeneration->user_id && $videoGeneration->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to cancel this video generation.',
            ], 403);
        }

        if (in_array($videoGeneration->status, ['succeeded', 'failed', 'cancelled'])) {
            return response()->json([
                'success' => true,
                'message' => "Video generation is already {$videoGeneration->status}.",
                'generation' => $videoGeneration,
            ]);
        }

        if (!empty($videoGeneration->prediction_id)) {
            try {
                $this->videoService->cancelPrediction($videoGeneration->prediction_id);
            } catch (Exception $e) {
                Log::info("Provider video cancellation notice: " . $e->getMessage());
            }
        }

        $videoGeneration->update([
            'status' => 'cancelled',
            'error_message' => 'Video generation was stopped by user.',
        ]);

        // Refund credits if not already refunded
        if ($videoGeneration->user_id) {
            $alreadyRefunded = CreditTransaction::where('reference_id', $videoGeneration->id)
                ->where('type', 'generation_refund')
                ->exists();
            if (!$alreadyRefunded) {
                $cost = (int) config('credits.costs.video_generation', 5);
                try {
                    $this->creditService->refundCredits(
                        user: $videoGeneration->user_id,
                        amount: $cost,
                        source: 'video_generation',
                        referenceId: $videoGeneration->id,
                        description: "Refunded {$cost} credits for cancelled video generation {$videoGeneration->id}"
                    );
                } catch (Exception $refEx) {
                    Log::error('[VIDEO CANCEL REFUND FAILED] ' . $refEx->getMessage());
                }
            }
        }

        Log::info("[VIDEO CANCELLED]\n" .
            "- Local Generation ID: {$videoGeneration->id}\n" .
            "- Prediction ID: {$videoGeneration->prediction_id}\n" .
            "- Status: cancelled");

        return response()->json([
            'success' => true,
            'message' => 'Video generation cancelled successfully.',
            'generation' => $videoGeneration->fresh(),
        ]);

    }

    /**
     * Retrieve user's video generation history.
     */
    public function history(Request $request): JsonResponse
    {
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : null);

        if (!$userId) {
            return response()->json([
                'success' => true,
                'generations' => [],
            ]);
        }

        $generations = VideoGeneration::where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'generations' => $generations,
        ]);
    }

    /**
     * Download generated video MP4 file.
     */
    public function download(string $id)
    {
        $videoGen = VideoGeneration::findOrFail($id);

        if ($videoGen->video_path && Storage::disk('public')->exists($videoGen->video_path)) {
            $path = Storage::disk('public')->path($videoGen->video_path);
            $filename = 'ai-video-' . substr($videoGen->id, 0, 8) . '.mp4';
            return response()->download($path, $filename, [
                'Content-Type' => 'video/mp4',
            ]);
        }

        if ($videoGen->remote_url) {
            return redirect($videoGen->remote_url);
        }

        abort(404, 'Video file not found.');
    }

    /**
     * Delete a video generation.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : null);
        $videoGen = VideoGeneration::find($id);

        if (!$videoGen) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        if ($videoGen->user_id && $videoGen->user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $videoGen->delete();

        return response()->json([
            'success' => true,
            'message' => 'Video generation deleted successfully.',
        ]);
    }
}
