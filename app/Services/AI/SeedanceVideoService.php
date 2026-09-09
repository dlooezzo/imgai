<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\VideoGenerationInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * SeedanceVideoService
 *
 * Integrates with Seedance 1.5 Pro via API.market for Text-to-Video Audio generation.
 *
 * API Endpoints (from official OpenAPI spec):
 *   POST /text-to-video-1-5-pro/run     — Submit a generation job
 *   GET  /text-to-video-1-5-pro/status/{job_id} — Poll job status
 *
 * Authentication: x-api-market-key header
 *
 * All endpoint paths, parameter names, and response fields are sourced exclusively
 * from the official API documentation. No parameters are invented or assumed.
 */
class SeedanceVideoService implements VideoGenerationInterface
{
    protected string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            (string) (config('services.magicapi.seedance_video_base_url') ?: env('SEEDANCE_VIDEO_BASE_URL', 'https://prod.api.market/api/v1/byteplus/seedance-text-to-video-1-5-pro')),
            '/'
        );
        $this->apiKey = config('services.magicapi.key') ?: env('API_MARKET_KEY');
    }

    /**
     * Submit an asynchronous text-to-video generation request (Seedance 1.5 Pro).
     *
     * API: POST /text-to-video-1-5-pro/run
     *
     * Required input fields (from API spec):
     *   - prompt      (string, required)
     *   - resolution  (string, enum: 480p|720p|1080p, required)
     *   - ratio       (string, enum: 21:9|16:9|4:3|1:1|3:4|9:16|9:21|adaptive, required)
     *   - duration    (integer, enum: 4-12, required)
     *
     * Optional input fields (from API spec):
     *   - watermark      (boolean)
     *   - seed           (integer)
     *   - camerafixed    (boolean)
     *   - generate_audio (boolean)
     */
    public function createPrediction(array $parameters): array
    {
        // Build input payload — only API-documented parameters
        $input = [
            'prompt'     => $parameters['prompt'],
            'resolution' => $parameters['resolution'] ?? '720p',
            'ratio'      => $parameters['ratio'] ?? '16:9',
            'duration'   => (int) ($parameters['duration'] ?? 5),
        ];

        // Optional: generate_audio (boolean) — defaults false per API spec and our UX default
        if (isset($parameters['generate_audio'])) {
            $input['generate_audio'] = (bool) $parameters['generate_audio'];
        } else {
            $input['generate_audio'] = false;
        }

        // Optional: seed (integer)
        if (isset($parameters['seed']) && is_numeric($parameters['seed']) && (int) $parameters['seed'] >= 0) {
            $input['seed'] = (int) $parameters['seed'];
        }

        // Optional: camerafixed (boolean)
        if (isset($parameters['camerafixed'])) {
            $input['camerafixed'] = (bool) $parameters['camerafixed'];
        }

        // Optional: watermark (boolean)
        if (isset($parameters['watermark'])) {
            $input['watermark'] = (bool) $parameters['watermark'];
        }

        // Simulation if API key is missing/placeholder
        if (empty($this->apiKey) || str_contains($this->apiKey, 'YOUR_API_MARKET_KEY')) {
            Log::warning('SeedanceVideoService: API Key is missing or placeholder. Simulating prediction.');
            return $this->simulateCreatePrediction($input);
        }

        $endpoint = "{$this->baseUrl}/text-to-video-1-5-pro/run";

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

                Log::error("SeedanceVideoService: Job submission failed HTTP {$statusCode}", [
                    'status'   => $statusCode,
                    'error'    => $errorMessage,
                    'response' => $rawBody,
                    'input'    => $input,
                ]);

                throw new Exception("Seedance API Error ({$statusCode}): {$errorMessage}");
            }

            $data = $response->json();

            // Response from API: { "id": "cgt-...", "status": "submitted" }
            return [
                'prediction_id' => $data['id'] ?? (string) Str::uuid(),
                'status'        => strtolower($data['status'] ?? 'submitted'),
                'version'       => 'seedance-1.5-pro',
                'raw'           => $data,
                'raw_body'      => $rawBody,
                'http_status'   => $statusCode,
            ];

        } catch (Exception $e) {
            Log::error('SeedanceVideoService connection error in createPrediction: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch the status and output of a submitted Seedance job.
     *
     * API: GET /text-to-video-1-5-pro/status/{job_id}
     *
     * Response when succeeded:
     * {
     *   "id": "cgt-...",
     *   "status": "succeeded",
     *   "output": { "video_url": "https://..." },
     *   "usage": { "completion_tokens": 108900, "total_tokens": 108900 },
     *   "created_at": 1771224109,
     *   "updated_at": 1771224158
     * }
     */
    public function getPredictionStatus(string $predictionId): array
    {
        if (str_starts_with($predictionId, 'mock_seedance_')) {
            return $this->simulatePredictionStatus($predictionId);
        }

        if (empty($this->apiKey) || str_contains($this->apiKey, 'YOUR_API_MARKET_KEY')) {
            throw new Exception('API Market key is not configured in .env (API_MARKET_KEY).');
        }

        $endpoint = "{$this->baseUrl}/text-to-video-1-5-pro/status/{$predictionId}";

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
                Log::warning("SeedanceVideoService: Transient HTTP {$statusCode} on status poll for {$predictionId}. Will retry.");
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

                Log::error("SeedanceVideoService: Status poll failed HTTP {$statusCode} for {$predictionId}", [
                    'error' => $errorMessage,
                ]);

                throw new Exception("Seedance Status API Error ({$statusCode}): {$errorMessage}");
            }

            $data = $response->json();

            // Extract video_url from output object (as per API spec: output.video_url)
            $outputUrl = null;
            if (!empty($data['output']) && is_array($data['output'])) {
                $outputUrl = $data['output']['video_url'] ?? null;
            } elseif (!empty($data['output']) && is_string($data['output'])) {
                // Defensive: handle if output is returned as direct string
                $outputUrl = $data['output'];
            }

            $status = strtolower(trim($data['status'] ?? 'processing'));

            Log::info("SeedanceVideoService: Status poll for {$predictionId}", [
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
            Log::warning("SeedanceVideoService: Connection timeout polling {$predictionId}: " . $e->getMessage());
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
                Log::warning("SeedanceVideoService: Timeout notice for {$predictionId}: " . $e->getMessage());
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
            Log::error("SeedanceVideoService: Exception polling {$predictionId}: " . $e->getMessage());
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
            Log::error('SeedanceVideoService: Failed to download and store video locally: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cancel an active prediction (Seedance API does not document a cancel endpoint,
     * so we log and return true for graceful local cancellation).
     */
    public function cancelPrediction(string $predictionId): bool
    {
        Log::info("SeedanceVideoService: Local cancellation requested for {$predictionId}. Seedance API has no cancel endpoint.");
        return true;
    }

    /**
     * Mock simulation when API key is absent (for development/testing).
     */
    protected function simulateCreatePrediction(array $input): array
    {
        $mockId = 'mock_seedance_' . Str::random(16);
        session()->put("mock_seedance_{$mockId}", [
            'created_at' => time(),
            'input'      => $input,
        ]);

        return [
            'prediction_id' => $mockId,
            'status'        => 'submitted',
            'version'       => 'seedance-1.5-pro',
            'raw'           => ['mock' => true],
            'raw_body'      => '{"mock": true}',
            'http_status'   => 200,
        ];
    }

    /**
     * Mock status simulation for development.
     */
    protected function simulatePredictionStatus(string $predictionId): array
    {
        $mock    = session()->get("mock_seedance_{$predictionId}", ['created_at' => time() - 5]);
        $elapsed = time() - ($mock['created_at'] ?? time());

        if ($elapsed < 5) {
            return [
                'id'          => $predictionId,
                'status'      => 'processing',
                'output'      => null,
                'error'       => null,
                'http_status' => 200,
                'raw_body'    => '{"status":"processing"}',
                'raw'         => ['mock' => true, 'elapsed' => $elapsed],
            ];
        }

        $outputUrl = 'https://blog.api.market/wp-content/uploads/2026/02/seedream-text-to-video.mp4';

        return [
            'id'          => $predictionId,
            'status'      => 'succeeded',
            'output'      => $outputUrl,
            'error'       => null,
            'http_status' => 200,
            'raw_body'    => '{"status":"succeeded","output":{"video_url":"' . $outputUrl . '"}}',
            'raw'         => ['mock' => true],
        ];
    }
}
