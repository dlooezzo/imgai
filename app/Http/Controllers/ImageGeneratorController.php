<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateImageRequest;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Services\AI\Contracts\ImageGenerationInterface;
use App\Services\Credits\CreditService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageGeneratorController extends Controller
{
    protected ImageGenerationInterface $aiService;
    protected CreditService $creditService;

    public function __construct(ImageGenerationInterface $aiService, CreditService $creditService)
    {
        $this->aiService = $aiService;
        $this->creditService = $creditService;
    }

    /**
     * Submit a new image generation request.
     */
    public function generate(GenerateImageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : null);

        if (!$userId) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required. Please sign in or register to generate images.',
                'message' => 'Authentication required. Please sign in or register to generate images.',
                'requires_auth' => true,
            ], 401);
        }

        // 1. Resolve User and check credit balance
        $user = $request->user() ?: User::where('id', $userId)->orWhere('email', session('supabase_user.email'))->first();
        $creditCost = (int) config('credits.costs.image_generation', 1);

        if (!$user || !$this->creditService->hasEnoughCredits($user, $creditCost)) {
            $currentBalance = $user ? $this->creditService->getBalance($user) : 0;
            return response()->json([
                'success' => false,
                'error' => 'insufficient_credits',
                'message' => "Insufficient credits. Generating an image requires {$creditCost} credit(s), but your current balance is {$currentBalance}.",
                'required_credits' => $creditCost,
                'current_balance' => $currentBalance,
            ], 402);
        }

        // 2. Atomically deduct credits before invoking AI service
        $promptPreview = Str::limit($validated['prompt'], 50);
        $creditTxn = $this->creditService->deductCredits(
            user: $user,
            amount: $creditCost,
            type: 'generation_deduction',
            source: 'image_generation',
            description: "Generation deduction for image: \"{$promptPreview}\""
        );

        try {
            // Call AI service to initiate prediction
            $prediction = $this->aiService->createPrediction($validated);

            // Create record in database with 24h expiration
            $generation = Generation::create([
                'user_id' => $userId,
                'prediction_id' => $prediction['prediction_id'],
                'prompt' => $validated['prompt'],
                'aspect_ratio' => $validated['aspect_ratio'] ?? '1:1',
                'megapixels' => $validated['megapixels'] ?? 2,
                'output_format' => $validated['output_format'] ?? 'jpg',
                'output_quality' => $validated['output_quality'] ?? 80,
                'seed' => $validated['seed'] ?? null,
                'juiced' => $validated['juiced'] ?? false,
                'model_version' => $prediction['version'] ?? '16e15e913fcc71c1a5defb335ea84739f99731fa1ee17995117c7d9adc6d176c',
                'status' => strtolower($prediction['status'] ?? 'starting'),
                'expires_at' => now()->addHours(24),
            ]);

            // Link credit transaction reference to local generation ID
            $creditTxn->update(['reference_id' => $generation->id]);

            // Structured logging as requested
            Log::info("[CREATE]\n" .
                "- Local Generation ID: {$generation->id}\n" .
                "- API Prediction ID: {$generation->prediction_id}\n" .
                "- Complete API Response: " . json_encode($prediction['raw'] ?? $prediction));

            return response()->json([
                'success' => true,
                'message' => 'Image generation started',
                'generation' => $generation,
                'credit_balance' => $this->creditService->getBalance($user),
            ], 201);
        } catch (Exception $e) {
            Log::error('[CREATE FAILED] ' . $e->getMessage());

            // Safely refund deducted credits on AI invocation failure
            try {
                $this->creditService->refundCredits(
                    user: $user,
                    amount: $creditCost,
                    source: 'image_generation',
                    referenceId: (string) $creditTxn->id,
                    description: "Refunded {$creditCost} credits due to AI service creation failure: " . $e->getMessage()
                );
            } catch (Exception $refundEx) {
                Log::error('[REFUND FAILED] ' . $refundEx->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'An error occurred while starting generation.',
            ], 500);
        }
    }


    /**
     * Poll status of an ongoing generation.
     */
    public function status(string $id): JsonResponse
    {
        $generation = Generation::find($id);

        if (!$generation) {
            return response()->json([
                'success' => false,
                'message' => 'Generation task not found.',
            ], 404);
        }

        // If already succeeded, failed, or cancelled, return directly without polling
        if (in_array($generation->status, ['succeeded', 'failed', 'cancelled'])) {
            return response()->json([
                'success' => true,
                'generation' => $generation,
            ]);
        }

        try {
            $predictionStatus = $this->aiService->getPredictionStatus($generation->prediction_id);
            $status = strtolower($predictionStatus['status'] ?? 'processing');
            $httpStatus = $predictionStatus['http_status'] ?? 200;
            $rawBody = $predictionStatus['raw_body'] ?? json_encode($predictionStatus['raw'] ?? []);
            $outputUrl = $predictionStatus['output'] ?? null;

            // Structured polling log
            Log::info("[POLL]\n" .
                "- Local Generation ID: {$generation->id}\n" .
                "- Prediction ID: {$generation->prediction_id}\n" .
                "- HTTP Status: {$httpStatus}\n" .
                "- Raw Response Body: {$rawBody}\n" .
                "- Parsed API Status: {$status}\n" .
                "- Parsed Output URL: " . ($outputUrl ?: 'null'));

            // RACE-CONDITION PROTECTION: Check if generation was cancelled while polling was in flight
            $freshGeneration = Generation::find($id);
            if (!$freshGeneration || $freshGeneration->status === 'cancelled') {
                Log::info("[POLL IGNORED] Generation {$id} is cancelled. Discarding API poll update.");
                return response()->json([
                    'success' => true,
                    'generation' => $freshGeneration ?: $generation,
                ]);
            }

            if ($status === 'succeeded' && !empty($outputUrl)) {
                $localPath = null;

                try {
                    // Download and store locally
                    $localPath = $this->aiService->downloadAndStoreImage($outputUrl, $generation->output_format);
                } catch (Exception $dlEx) {
                    Log::warning("Could not cache image locally, using remote URL: " . $dlEx->getMessage());
                }

                // RACE-CONDITION CHECK: Check again after image download
                $freshGeneration = Generation::find($id);
                if ($freshGeneration && $freshGeneration->status === 'cancelled') {
                    Log::info("[SAVE IGNORED] Generation {$id} was cancelled during image download.");
                    return response()->json([
                        'success' => true,
                        'generation' => $freshGeneration,
                    ]);
                }

                $generation->update([
                    'status' => 'succeeded',
                    'remote_url' => $outputUrl,
                    'image_path' => $localPath,
                    'error_message' => null,
                ]);

                // Structured final log
                Log::info("[FINAL]\n" .
                    "- Local Generation ID: {$generation->id}\n" .
                    "- Status: succeeded\n" .
                    "- Output URL: {$outputUrl}\n" .
                    "- Downloaded Local File Path: " . ($localPath ?: 'none') . "\n" .
                    "- Database Status: " . $generation->fresh()->status);

            } elseif ($status === 'failed') {
                $errorMessage = $predictionStatus['error'] ?? 'Prediction failed on AI provider.';
                $generation->update([
                    'status' => 'failed',
                    'error_message' => $errorMessage,
                ]);

                // Structured final failed log
                Log::warning("[FINAL]\n" .
                    "- Local Generation ID: {$generation->id}\n" .
                    "- Status: failed\n" .
                    "- Error: {$errorMessage}\n" .
                    "- Database Status: " . $generation->fresh()->status);
            } else {
                // Keep updating intermediate status if still active and not cancelled
                $freshGeneration = Generation::find($id);
                if ($freshGeneration && $freshGeneration->status !== 'cancelled') {
                    $generation->update([
                        'status' => $status,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'generation' => $generation->fresh(),
            ]);
        } catch (Exception $e) {
            Log::warning("[POLL WARNING] for {$id}: " . $e->getMessage());

            return response()->json([
                'success' => true,
                'is_retrying' => true,
                'message' => 'Waiting for compute node...',
                'generation' => $generation->fresh(),
            ]);
        }
    }

    /**
     * Cancel an active generation.
     */
    public function cancel(string $id, Request $request): JsonResponse
    {
        $generation = Generation::find($id);

        if (!$generation) {
            return response()->json([
                'success' => false,
                'message' => 'Generation not found.',
            ], 404);
        }

        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : session()->getId());

        // Verify ownership (match user_id if present)
        if ($generation->user_id && $generation->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to cancel this generation.',
            ], 403);
        }

        // If already in a terminal state, return directly
        if (in_array($generation->status, ['succeeded', 'failed', 'cancelled'])) {
            return response()->json([
                'success' => true,
                'message' => "Generation is already {$generation->status}.",
                'generation' => $generation,
            ]);
        }

        // Attempt provider cancellation safely
        if (!empty($generation->prediction_id)) {
            try {
                $this->aiService->cancelPrediction($generation->prediction_id);
            } catch (Exception $e) {
                Log::info("Provider cancellation notice: " . $e->getMessage());
            }
        }

        // Update local generation status to cancelled
        $generation->update([
            'status' => 'cancelled',
            'error_message' => 'Generation was stopped by user.',
        ]);

        // Refund credits if not already refunded
        if ($generation->user_id) {
            $alreadyRefunded = CreditTransaction::where('reference_id', $generation->id)
                ->where('type', 'generation_refund')
                ->exists();
            if (!$alreadyRefunded) {
                $cost = (int) config('credits.costs.image_generation', 1);
                try {
                    $this->creditService->refundCredits(
                        user: $generation->user_id,
                        amount: $cost,
                        source: 'image_generation',
                        referenceId: $generation->id,
                        description: "Refunded {$cost} credits for cancelled generation {$generation->id}"
                    );
                } catch (Exception $refEx) {
                    Log::error('[CANCEL REFUND FAILED] ' . $refEx->getMessage());
                }
            }
        }

        Log::info("[CANCELLED]\n" .
            "- Local Generation ID: {$generation->id}\n" .
            "- Prediction ID: {$generation->prediction_id}\n" .
            "- Status: cancelled");

        return response()->json([
            'success' => true,
            'message' => 'Generation cancelled successfully.',
            'generation' => $generation->fresh(),
        ]);

    }

    /**
     * Get user generation history.
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

        $generations = Generation::where('user_id', $userId)
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
     * Download generated image file.
     */
    public function download(string $id)
    {
        $generation = Generation::findOrFail($id);

        if ($generation->image_path && Storage::disk('public')->exists($generation->image_path)) {
            $path = Storage::disk('public')->path($generation->image_path);
            $filename = 'ai-generation-' . substr($generation->id, 0, 8) . '.' . $generation->output_format;
            return response()->download($path, $filename);
        }

        if ($generation->remote_url) {
            return redirect($generation->remote_url);
        }

        abort(404, 'Image file not found.');
    }

    /**
     * Delete a generation.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : null);
        $generation = Generation::find($id);

        if (!$generation) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        if ($generation->user_id && $generation->user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $generation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Generation deleted successfully.',
        ]);
    }
}
