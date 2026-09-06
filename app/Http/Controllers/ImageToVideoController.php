<?php

namespace App\Http\Controllers;

use App\Jobs\PollImageToVideoPredictionJob;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Models\VideoGeneration;
use App\Services\AI\WanImageToVideoService;
use App\Services\Credits\CreditService;
use App\Services\Storage\R2StorageService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageToVideoController extends Controller
{
    protected WanImageToVideoService $aiService;
    protected R2StorageService $r2Service;
    protected CreditService $creditService;

    public function __construct(WanImageToVideoService $aiService, R2StorageService $r2Service, CreditService $creditService)
    {
        $this->aiService = $aiService;
        $this->r2Service = $r2Service;
        $this->creditService = $creditService;
    }

    /**
     * Start an Image-to-Video generation request:
     * 1. Validates source image & prompt
     * 2. Checks and deducts credits atomically
     * 3. Uploads image to Cloudflare R2
     * 4. Submits prediction to api.market with R2 public HTTPS URL
     * 5. Saves record in database (job_dispatched = true)
     * 6. Dispatches background polling job
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image'             => 'required|file|mimes:jpg,jpeg,png,webp|max:15360',
            'prompt'            => 'required|string|min:1|max:2000',
            'aspect_ratio'      => 'nullable|string|in:16:9,9:16',
            'resolution'        => 'nullable|string|in:720p,480p',
            'num_frames'        => 'nullable|integer|in:41,81,121',
            'frames_per_second' => 'nullable|integer|in:16,24',
            'seed'              => 'nullable|integer|min:0|max:2147483647',
        ], [
            'image.required'  => 'Please select a source image to animate.',
            'image.file'      => 'The uploaded file must be a valid image.',
            'image.mimes'     => 'Only JPG, JPEG, PNG, and WebP images are supported.',
            'image.max'       => 'The image file size must not exceed 15 MB.',
            'prompt.required' => 'Please provide a motion prompt describing what should happen in the video.',
        ]);

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
        $creditCost = (int) config('credits.costs.image_to_video', 5);

        if (!$user || !$this->creditService->hasEnoughCredits($user, $creditCost)) {
            $currentBalance = $user ? $this->creditService->getBalance($user) : 0;
            return response()->json([
                'success' => false,
                'error' => 'insufficient_credits',
                'message' => "Insufficient credits. Generating an image-to-video animation requires {$creditCost} credit(s), but your current balance is {$currentBalance}.",
                'required_credits' => $creditCost,
                'current_balance' => $currentBalance,
            ], 402);
        }

        // 2. Atomically deduct credits before proceeding with upload and prediction
        $promptPreview = Str::limit($validated['prompt'], 50);
        $creditTxn = $this->creditService->deductCredits(
            user: $user,
            amount: $creditCost,
            type: 'generation_deduction',
            source: 'image_to_video',
            description: "Generation deduction for image-to-video: \"{$promptPreview}\""
        );


        try {
            // Step 1: Upload source image to Cloudflare R2
            $imageUpload = $this->r2Service->uploadImage($request->file('image'), $userId);
            $r2ImageUrl  = $imageUpload['url'];
            $r2ImagePath = $imageUpload['path'];

            $aspectRatio = $validated['aspect_ratio'] ?? '16:9';
            $resolution  = $validated['resolution'] ?? '720p';
            $numFrames   = (int) ($validated['num_frames'] ?? 81);
            $fps         = (int) ($validated['frames_per_second'] ?? 24);

            // Step 2: Create initial record in database
            $generation = VideoGeneration::create([
                'user_id'           => $userId,
                'generation_type'   => 'image-to-video',
                'prompt'            => trim($validated['prompt']),
                'source_image_url'  => $r2ImageUrl,
                'source_image_path' => $r2ImagePath,
                'aspect_ratio'      => $aspectRatio,
                'resolution'        => $resolution,
                'num_frames'        => $numFrames,
                'frame_rate'        => $fps,
                'status'            => 'starting',
                'job_dispatched'    => false,
                'expires_at'        => now()->addHours(24),
            ]);

            // Step 3: Create prediction on api.market provider
            $prediction = $this->aiService->createPrediction([
                'image'             => $r2ImageUrl,
                'prompt'            => $generation->prompt,
                'resolution'        => $resolution,
                'aspect_ratio'      => $aspectRatio,
                'num_frames'        => $numFrames,
                'frames_per_second' => $fps,
                'seed'              => $validated['seed'] ?? null,
            ]);

            // Step 4: Save prediction ID and mark job_dispatched = true
            $generation->update([
                'prediction_id'  => $prediction['id'],
                'model_version'  => $prediction['version'] ?? config('services.magicapi.image_to_video_version'),
                'status'         => $prediction['status'] ?? 'starting',
                'job_dispatched' => true,
            ]);

            // Link credit transaction reference to local video generation ID
            $creditTxn->update(['reference_id' => $generation->id]);

            // Step 5: Dispatch background polling job
            Log::info("[I2V DISPATCH] Dispatching PollImageToVideoPredictionJob for Generation {$generation->id} (Prediction ID: {$prediction['id']}, Queue: " . config('queue.default') . ")");

            PollImageToVideoPredictionJob::dispatch($generation->id)
                ->delay(now()->addSeconds(3));

            return response()->json([
                'success'        => true,
                'generation'     => $generation->fresh(),
                'prediction_id'  => $prediction['id'],
                'credit_balance' => $this->creditService->getBalance($user),
            ], 201);

        } catch (\App\Services\Storage\R2StorageException $e) {
            Log::error("ImageToVideoController R2 storage error: {$e->getMessage()}");
            $this->creditService->refundCredits($user, $creditCost, 'image_to_video', (string) $creditTxn->id, "Refunded due to storage error: {$e->getMessage()}");
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (\App\Services\AI\ImageToVideoException $e) {
            Log::error("ImageToVideoController AI prediction error: {$e->getMessage()}");
            $this->creditService->refundCredits($user, $creditCost, 'image_to_video', (string) $creditTxn->id, "Refunded due to AI prediction error: {$e->getMessage()}");
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error("ImageToVideoController unexpected error: {$e->getMessage()}", ['exception' => $e]);
            $this->creditService->refundCredits($user, $creditCost, 'image_to_video', (string) $creditTxn->id, "Refunded due to unexpected error: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error'   => 'An unexpected error occurred while starting your video generation. Please try again.',
            ], 500);
        }
    }


    /**
     * Get the current status of an Image-to-Video generation.
     * Reads from the database only. Self-heals stranded generations on queue restart.
     */
    public function status(Request $request, string $id): JsonResponse
    {
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : session()->getId());

        $generation = VideoGeneration::where('id', $id)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->first();

        if (!$generation) {
            return response()->json(['success' => false, 'error' => 'Generation record was not found.'], 404);
        }

        // Self-healing: if in-progress but job not dispatched (e.g. after worker restart),
        // re-dispatch the polling job if the generation is not expired and has a prediction ID.
        if (in_array($generation->status, ['starting', 'processing'], true)
            && !$generation->job_dispatched
            && !empty($generation->prediction_id)
            && ($generation->expires_at === null || $generation->expires_at->isFuture())
        ) {
            // Use a DB-level atomic update to prevent duplicate dispatch from concurrent requests
            $updated = DB::table('video_generations')
                ->where('id', $generation->id)
                ->where('job_dispatched', false)
                ->whereIn('status', ['starting', 'processing'])
                ->update(['job_dispatched' => true]);

            if ($updated) {
                Log::info("[I2V SELF-HEAL] Re-dispatching orphaned polling job for Generation {$generation->id} (Prediction ID: {$generation->prediction_id})");
                PollImageToVideoPredictionJob::dispatch($generation->id)
                    ->delay(now()->addSeconds(2));
            }
        }

        return response()->json([
            'success'    => true,
            'generation' => $generation->fresh(),
        ]);
    }

    /**
     * Cancel an ongoing Image-to-Video generation request.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : session()->getId());

        $generation = VideoGeneration::where('id', $id)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('user_id', session()->getId())
                    ->orWhereNull('user_id');
            })
            ->first();

        if (!$generation) {
            return response()->json(['success' => false, 'error' => 'Generation not found'], 404);
        }

        if (in_array($generation->status, ['starting', 'processing'], true)) {
            Log::info("[I2V CANCEL] User requested cancellation of Generation {$generation->id} (Prediction ID: {$generation->prediction_id})");

            $generation->update([
                'status'         => 'cancelled',
                'job_dispatched' => false,
                'error_message'  => 'Video generation was stopped by user.',
            ]);

            if ($generation->prediction_id) {
                $this->aiService->cancelPrediction($generation->prediction_id);
            }

            // Refund credits if not already refunded
            if ($generation->user_id) {
                $alreadyRefunded = CreditTransaction::where('reference_id', $generation->id)
                    ->where('type', 'generation_refund')
                    ->exists();
                if (!$alreadyRefunded) {
                    $cost = (int) config('credits.costs.image_to_video', 5);
                    try {
                        $this->creditService->refundCredits(
                            user: $generation->user_id,
                            amount: $cost,
                            source: 'image_to_video',
                            referenceId: $generation->id,
                            description: "Refunded {$cost} credits for cancelled video generation {$generation->id}"
                        );
                    } catch (Exception $refEx) {
                        Log::error('[I2V CANCEL REFUND FAILED] ' . $refEx->getMessage());
                    }
                }
            }
        }

        return response()->json([

            'success'    => true,
            'generation' => $generation->fresh(),
        ]);
    }

    /**
     * Retrieve recent Image-to-Video generations for user.
     */
    public function history(Request $request): JsonResponse
    {
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : null);

        if (!$userId) {
            return response()->json([
                'success' => true,
                'history' => [],
            ]);
        }

        $generations = VideoGeneration::where('generation_type', 'image-to-video')
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->limit(24)
            ->get();

        return response()->json([
            'success' => true,
            'history' => $generations,
        ]);
    }

    /**
     * Download the synthesized video file.
     */
    public function download(Request $request, string $id)
    {
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : session()->getId());

        $generation = VideoGeneration::where('id', $id)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)->orWhereNull('user_id');
            })
            ->firstOrFail();

        if ($generation->remote_url && str_starts_with($generation->remote_url, 'http')) {
            return redirect()->away($generation->remote_url);
        }

        if ($generation->video_path && Storage::disk('r2')->exists($generation->video_path)) {
            $publicUrl = $this->r2Service->buildPublicUrl($generation->video_path);
            return redirect()->away($publicUrl);
        }

        if ($generation->video_path && Storage::disk('public')->exists($generation->video_path)) {
            return response()->download(storage_path('app/public/' . $generation->video_path), "cinematic-i2v-{$generation->id}.mp4");
        }

        abort(404, 'Video file not available for download.');
    }

    /**
     * Delete an Image-to-Video generation and its R2 assets.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : session()->getId());

        $generation = VideoGeneration::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if ($generation) {
            $generation->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Generation and storage assets removed successfully.',
        ]);
    }
}
