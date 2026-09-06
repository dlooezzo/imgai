<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\ImageToVideoServiceInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageToVideoException extends Exception {}

class WanImageToVideoService implements ImageToVideoServiceInterface
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected string $defaultVersion;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) (config('services.magicapi.image_to_video_base_url') ?: env('MAGICAPI_IMAGE_TO_VIDEO_BASE_URL', 'https://prod.api.market/api/v1/magicapi/ultra-fast-text-to-image-image-to-video-api')), '/');
        $this->apiKey = config('services.magicapi.key') ?: env('API_MARKET_KEY');
        $this->defaultVersion = (string) (config('services.magicapi.image_to_video_version') ?: env('MAGICAPI_IMAGE_TO_VIDEO_VERSION', 'c92ab4265c9b3b5ea9ac9a87df839ebfd662ee3a820d62c21305bf6501a73fe1'));
    }

    /**
     * Submit an asynchronous Image-to-Video prediction to api.market (Wan 2.2).
     *
     * @throws ImageToVideoException
     */
    public function createPrediction(array $parameters): array
    {
        if (empty($this->apiKey) && !app()->environment('testing')) {
            Log::error('WanImageToVideoService: API Key is missing in configuration.');
            throw new ImageToVideoException('The Image-to-Video service is not configured with an API key.');
        }

        if (empty($parameters['image']) || !filter_var($parameters['image'], FILTER_VALIDATE_URL)) {
            throw new ImageToVideoException('A valid public HTTPS image URL is required for Image-to-Video generation.');
        }

        if (empty($parameters['prompt'])) {
            throw new ImageToVideoException('A motion prompt is required for Image-to-Video generation.');
        }

        $version = !empty($parameters['version']) ? $parameters['version'] : $this->defaultVersion;

        // Build input payload matching exact provider documentation
        $input = [
            'image' => $parameters['image'],
            'prompt' => (string) $parameters['prompt'],
            'resolution' => in_array($parameters['resolution'] ?? '', ['720p', '480p'], true) ? $parameters['resolution'] : '720p',
            'aspect_ratio' => in_array($parameters['aspect_ratio'] ?? '', ['16:9', '9:16'], true) ? $parameters['aspect_ratio'] : '16:9',
            'num_frames' => (int) ($parameters['num_frames'] ?? 81),
            'frames_per_second' => (int) ($parameters['frames_per_second'] ?? 24),
            'go_fast' => true,
        ];

        if (isset($parameters['seed']) && is_numeric($parameters['seed'])) {
            $input['seed'] = (int) $parameters['seed'];
        }

        $endpoint = "{$this->baseUrl}/predictions";

        try {
            Log::info('WanImageToVideoService: Creating prediction', [
                'endpoint' => $endpoint,
                'resolution' => $input['resolution'],
                'aspect_ratio' => $input['aspect_ratio'],
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-api-market-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->connectTimeout(10)->timeout(30)->post($endpoint, [
                'version' => $version,
                'input' => $input,
            ]);

            if (!$response->successful()) {
                $status = $response->status();
                $body = $response->json() ?? [];
                $errorMsg = $body['error']['message'] ?? $body['message'] ?? $body['error'] ?? 'Provider prediction creation failed.';

                Log::error("WanImageToVideoService API error (HTTP {$status}): {$errorMsg}", [
                    'status' => $status,
                    'body' => $body,
                ]);

                if ($status === 401 || $status === 403) {
                    throw new ImageToVideoException('Authentication failed with the Image-to-Video provider. Please check API credentials.');
                } elseif ($status === 429) {
                    throw new ImageToVideoException('Rate limit exceeded on Image-to-Video provider. Please wait a moment and try again.');
                } elseif ($status >= 500) {
                    throw new ImageToVideoException('The Image-to-Video provider is currently experiencing service disruption. Please try again later.');
                }

                throw new ImageToVideoException("Unable to start video generation: {$errorMsg}");
            }

            $data = $response->json();
            if (empty($data['id'])) {
                Log::error('WanImageToVideoService: Missing prediction ID in response', ['response' => $data]);
                throw new ImageToVideoException('Received invalid response from Image-to-Video provider.');
            }

            Log::info("WanImageToVideoService: Prediction created successfully with ID: {$data['id']}");

            return [
                'id' => $data['id'],
                'version' => $data['version'] ?? $version,
                'status' => $data['status'] ?? 'starting',
                'created_at' => $data['created_at'] ?? now()->toIso8601String(),
            ];
        } catch (ImageToVideoException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error("WanImageToVideoService connection exception: {$e->getMessage()}", ['exception' => $e]);
            throw new ImageToVideoException('Network error connecting to the Image-to-Video service. Please try again.');
        }
    }

    /**
     * Poll prediction status from api.market.
     *
     * @throws ImageToVideoException
     */
    public function getPredictionStatus(string $predictionId): array
    {
        $endpoint = "{$this->baseUrl}/predictions/{$predictionId}";

        try {
            $response = Http::withHeaders([
                'x-api-market-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->connectTimeout(8)->timeout(25)->get($endpoint);

            if (!$response->successful()) {
                $statusCode = $response->status();
                $body = $response->json() ?? [];
                $errorMsg = $body['error']['message'] ?? $body['message'] ?? "HTTP {$statusCode}";

                Log::warning("WanImageToVideoService status check HTTP {$statusCode}: {$errorMsg}", [
                    'prediction_id' => $predictionId,
                ]);

                // Transient gateway or routing errors (including early 404/429/500/502/503/504) -> keep polling
                return [
                    'id' => $predictionId,
                    'status' => 'processing',
                    'output' => null,
                    'error' => null,
                    'http_status' => $statusCode,
                ];
            }

            $data = $response->json();

            $rawStatus = strtolower(trim((string) ($data['status'] ?? 'processing')));
            $isSucceeded = in_array($rawStatus, ['succeeded', 'success', 'completed', 'done'], true);

            // Extract output video URL flexibly
            $outputUrl = null;
            if (!empty($data['output'])) {
                if (is_array($data['output'])) {
                    $outputUrl = $data['output'][0] ?? null;
                } elseif (is_string($data['output'])) {
                    $outputUrl = $data['output'];
                }
            } elseif (!empty($data['output_url'])) {
                $outputUrl = is_array($data['output_url']) ? ($data['output_url'][0] ?? null) : (string) $data['output_url'];
            } elseif (!empty($data['result'])) {
                $outputUrl = is_array($data['result']) ? ($data['result'][0] ?? null) : (string) $data['result'];
            } elseif (!empty($data['video'])) {
                $outputUrl = is_array($data['video']) ? ($data['video'][0] ?? null) : (string) $data['video'];
            } elseif (!empty($data['video_url'])) {
                $outputUrl = is_array($data['video_url']) ? ($data['video_url'][0] ?? null) : (string) $data['video_url'];
            } elseif (!empty($data['urls']) && is_array($data['urls'])) {
                $outputUrl = $data['urls']['get'] ?? $data['urls'][0] ?? null;
            } elseif (!empty($data['url'])) {
                $outputUrl = is_string($data['url']) ? $data['url'] : null;
            }

            // Log the full raw response any time the status is succeeded to diagnose output URL location
            if ($isSucceeded) {
                Log::info("WanImageToVideoService: provider returned succeeded for {$predictionId}", [
                    'raw_status' => $rawStatus,
                    'resolved_output_url' => $outputUrl,
                    'full_response_keys' => array_keys($data),
                    'full_response' => $data,
                ]);
            }

            $status = 'processing';

            if (in_array($rawStatus, ['succeeded', 'success', 'completed', 'done'], true)) {
                $status = 'succeeded';
            } elseif (in_array($rawStatus, ['failed', 'error', 'canceled', 'cancelled'], true)) {
                $status = 'failed';
            } elseif (in_array($rawStatus, ['starting', 'queued', 'pending'], true)) {
                $status = 'starting';
            } else {
                $status = 'processing';
            }

            return [
                'id' => $data['id'] ?? $predictionId,
                'status' => $status,
                'raw_status' => $rawStatus,
                'output' => $outputUrl,
                'error' => $data['error'] ?? null,
                'created_at' => $data['created_at'] ?? null,
                'started_at' => $data['started_at'] ?? null,
                'completed_at' => $data['completed_at'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning("WanImageToVideoService getPredictionStatus exception: {$e->getMessage()}", [
                'prediction_id' => $predictionId,
            ]);

            return [
                'id' => $predictionId,
                'status' => 'processing',
                'output' => null,
                'error' => null,
                'http_status' => 0,
            ];
        }
    }

    /**
     * Cancel an ongoing prediction if supported.
     */
    public function cancelPrediction(string $predictionId): bool
    {
        $endpoint = "{$this->baseUrl}/predictions/{$predictionId}/cancel";

        try {
            $response = Http::withHeaders([
                'x-api-market-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->connectTimeout(5)->timeout(10)->post($endpoint);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning("WanImageToVideoService cancelPrediction failed: {$e->getMessage()}");
            return false;
        }
    }
}
