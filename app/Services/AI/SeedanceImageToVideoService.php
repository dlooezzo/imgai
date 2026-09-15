<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\ImageToVideoServiceInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\SiteSetting;

/**
 * SeedanceImageToVideoService
 *
 * Integrates with BytePlus Seedance 1.0 Pro Fast – Image to Video via API.market.
 *
 * API Endpoints (from official OpenAPI spec):
 *   POST /image-to-video-pro-fast/run     — Submit a generation job
 *   GET  /image-to-video-pro-fast/status/{job_id} — Poll job status
 *
 * Authentication: x-api-market-key header
 *
 * All endpoint paths, parameter names, and response fields are sourced exclusively
 * from the official API documentation. No parameters are invented or assumed.
 */
class SeedanceImageToVideoService implements ImageToVideoServiceInterface
{
    protected string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            (string) SiteSetting::get('ai_model_i2v_url', config('services.magicapi.seedance_image_to_video_base_url', env('SEEDANCE_IMAGE_TO_VIDEO_BASE_URL', 'https://prod.api.market/api/v1/byteplus/seedance-image-to-video-pro-fast'))),
            '/'
        );
        $this->apiKey = config('services.magicapi.key') ?: env('API_MARKET_KEY');
    }

    /**
     * Submit an asynchronous Image-to-Video generation request (BytePlus Seedance 1.0 Pro Fast).
     *
     * API: POST /image-to-video-pro-fast/run
     *
     * Required input fields (from API spec):
     *   - prompt       (string, required)
     *   - image_url    (string, required) — publicly accessible HTTPS URL
     *   - resolution   (string, enum: 480p|720p|1080p, required)
     *   - ratio        (string, enum: 21:9|16:9|4:3|1:1|3:4|9:16|9:21|adaptive, required)
     *   - duration     (integer, enum: 2-12, required)
     *
     * Optional input fields (from API spec):
     *   - watermark      (boolean)
     *   - seed           (integer, -1 for random or fixed integer)
     *   - camerafixed    (boolean)
     *
     */
    public function createPrediction(array $parameters): array
    {
        if (empty($this->apiKey) || str_contains($this->apiKey, 'YOUR_API_MARKET_KEY')) {
            throw new ImageToVideoException('The Image-to-Video service is not configured with an API key.');
        }

        if (empty($parameters['image_url']) || ! filter_var($parameters['image_url'], FILTER_VALIDATE_URL)) {
            throw new ImageToVideoException('A valid public image URL is required for Image-to-Video generation.');
        }

        if (empty($parameters['prompt'])) {
            throw new ImageToVideoException('A motion prompt is required for Image-to-Video generation.');
        }

        // Build input payload — only API-documented parameters
        $input = [
            'prompt'     => $parameters['prompt'],
            'image_url'  => $parameters['image_url'],
            'resolution' => $parameters['resolution'] ?? '720p',
            'ratio'      => $parameters['ratio'] ?? '16:9',
            'duration'   => (int) ($parameters['duration'] ?? 5),
        ];

        // Optional: watermark (boolean)
        if (isset($parameters['watermark'])) {
            $input['watermark'] = (bool) $parameters['watermark'];
        }

        // Optional: seed (integer, -1 for random)
        if (isset($parameters['seed']) && is_numeric($parameters['seed'])) {
            $input['seed'] = (int) $parameters['seed'];
        }

        // Optional: camerafixed (boolean)
        if (isset($parameters['camerafixed'])) {
            $input['camerafixed'] = (bool) $parameters['camerafixed'];
        }

        $endpoint = "{$this->baseUrl}/image-to-video-pro-fast/run";

        try {
            $response = Http::withHeaders([
                'Content-Type'     => 'application/json',
                'x-api-market-key' => $this->apiKey,
                'Accept'           => 'application/json',
            ])->connectTimeout(10)->timeout(30)->retry(3, 1000, function ($exception, $request) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                    ($exception->response && in_array($exception->response->status(), [429, 500, 502, 503, 504]));
            })->post($endpoint, [
                'input' => $input,
            ]);

            $statusCode = $response->status();
            $rawBody    = $response->body();

            if ($response->failed()) {
                $errorData    = $response->json();
                $errorMessage = $errorData['error']['message']
                    ?? ($errorData['message']
                    ?? ($errorData['detail']
                    ?? "HTTP {$statusCode}: {$rawBody}"));

                Log::error("SeedanceImageToVideoService: Job submission failed HTTP {$statusCode}", [
                    'status'   => $statusCode,
                    'error'    => $errorMessage,
                ]);

                throw new Exception("Seedance API Error ({$statusCode}): {$errorMessage}");
            }

            $data = $response->json();

            if (empty($data['id'])) {
                throw new ImageToVideoException('Seedance API returned no job_id.');
            }

            // Response from API: { "id": "cgt-...", "status": "submitted" }
            return [
                'id'             => $data['id'],
                'prediction_id'  => $data['id'],
                'status'         => strtolower($data['status'] ?? 'submitted'),
                'version'        => 'seedance-1.0-pro-fast',
                'raw'           => $data,
                'raw_body'      => $rawBody,
                'http_status'   => $statusCode,
            ];

        } catch (ImageToVideoException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('SeedanceImageToVideoService connection error in createPrediction: ' . $e->getMessage());
            throw new ImageToVideoException('Network error connecting to the Image-to-Video service: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Fetch the status and output of a submitted Seedance Image-to-Video job.
     *
     * API: GET /image-to-video-pro-fast/status/{job_id}
     *
     * Response when succeeded:
     * {
     *   "id": "cgt-...",
     *   "status": "succeeded",
     *   "output": { "video_url": "https://..." },
     *   ...
     * }
     */
    public function getPredictionStatus(string $predictionId): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('API Market key is not configured in .env (API_MARKET_KEY).');
        }

        $endpoint = "{$this->baseUrl}/image-to-video-pro-fast/status/{$predictionId}";

        try {
            $response = Http::withHeaders([
                'x-api-market-key' => $this->apiKey,
                'Accept'           => 'application/json',
            ])->connectTimeout(5)->timeout(15)->retry(2, 500, function ($exception, $request) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                    ($exception->response && in_array($exception->response->status(), [429, 500, 502, 503, 504]));
            })->get($endpoint);

            $statusCode = $response->status();
            $rawBody    = $response->body();

            // Transient server errors — continue polling
            if (in_array($statusCode, [429, 500, 502, 503, 504])) {
                Log::warning("SeedanceImageToVideoService: Transient HTTP {$statusCode} on status poll for {$predictionId}. Will retry.");
                return [
                    'id'          => $predictionId,
                    'status'      => 'processing',
                    'output'      => null,
                    'error'       => null,
                    'http_status' => $statusCode,
                    'raw_body'    => $rawBody,
                    'raw'         => ['transient_error' => "HTTP {$statusCode}"],
                ];
            }

            if ($response->failed()) {
                $errorData    = $response->json();
                $errorMessage = $errorData['error']['message']
                    ?? ($errorData['message']
                    ?? "HTTP {$statusCode}");

                Log::error("SeedanceImageToVideoService: Status poll failed HTTP {$statusCode} for {$predictionId}", [
                    'error' => $errorMessage,
                ]);

                throw new Exception("Seedance Status API Error ({$statusCode}): {$errorMessage}");
            }

            $data = $response->json();

            // Extract video_url from output object (as per API spec: output.video_url)
            $outputUrl = null;
            if (!empty($data['output']) && is_array($data['output'])) {
                $outputUrl = $data['output']['video_url'] ?? null;
            }

            $status = strtolower(trim((string) ($data['status'] ?? '')));
            if ($status === 'in_progress') {
                $status = 'processing';
            }

            if (! in_array($status, ['submitted', 'processing', 'succeeded', 'failed'], true)) {
                throw new Exception('Seedance API returned an unsupported status: '.($data['status'] ?? 'missing'));
            }

            Log::info("SeedanceImageToVideoService: Status poll for {$predictionId}", [
                'status'     => $status,
                'output_url' => $outputUrl,
                'http_status'=> $statusCode,
            ]);

            return [
                'id'          => $data['id'] ?? $predictionId,
                'status'      => $status,
                'output'      => $outputUrl,
                'error'       => $data['error'] ?? null,
                'http_status' => $statusCode,
                'raw_body'    => $rawBody,
                'raw'         => $data,
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning("SeedanceImageToVideoService: Connection timeout polling {$predictionId}: " . $e->getMessage());
            return [
                'id'          => $predictionId,
                'status'      => 'processing',
                'output'      => null,
                'error'       => null,
                'http_status' => 0,
                'raw_body'    => '{"transient_timeout": true}',
                'raw'         => ['transient_timeout' => $e->getMessage()],
            ];
        } catch (Exception $e) {
            if (
                str_contains($e->getMessage(), 'cURL error 28') ||
                str_contains($e->getMessage(), 'timed out') ||
                str_contains($e->getMessage(), 'Connection reset')
            ) {
                Log::warning("SeedanceImageToVideoService: Timeout notice for {$predictionId}: " . $e->getMessage());
                return [
                    'id'          => $predictionId,
                    'status'      => 'processing',
                    'output'      => null,
                    'error'       => null,
                    'http_status' => 0,
                    'raw_body'    => '{"transient_timeout": true}',
                    'raw'         => ['transient_timeout' => $e->getMessage()],
                ];
            }
            Log::error("SeedanceImageToVideoService: Exception polling {$predictionId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Download and cache video locally from remote URL.
     */
    public function downloadAndStoreVideo(string $remoteUrl): string
    {
        try {
            $response = Http::timeout(180)->retry(3, 2000)->get($remoteUrl);

            if ($response->failed()) {
                throw new Exception("Video download failed with HTTP status: " . $response->status());
            }

            $filename = 'videos/' . Str::uuid() . '.mp4';
            Storage::disk('public')->put($filename, $response->body());

            return $filename;
        } catch (Exception $e) {
            Log::error('SeedanceImageToVideoService: Failed to download and store video locally: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cancel an active prediction (Seedance API does not document a cancel endpoint,
     * so we log and return true for graceful local cancellation).
     */
    public function cancelPrediction(string $predictionId): bool
    {
        Log::info("SeedanceImageToVideoService: Local cancellation requested for {$predictionId}. Seedance API has no cancel endpoint.");
        return true;
    }

}