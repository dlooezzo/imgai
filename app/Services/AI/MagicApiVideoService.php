<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\VideoGenerationInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MagicApiVideoService implements VideoGenerationInterface
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected string $defaultVersion;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) (config('services.magicapi.video_base_url') ?: env('MAGICAPI_VIDEO_BASE_URL')), '/');
        $this->apiKey = config('services.magicapi.key') ?: env('API_MARKET_KEY');
        $this->defaultVersion = (string) (config('services.magicapi.video_version') ?: env('MAGICAPI_VIDEO_VERSION', '6c9132aee14409cd6568d030453f1ba50f5f3412b844fe67f78a9eb62d55664f'));
    }

    /**
     * Submit an asynchronous pure text-to-video prediction request (Tencent Hunyuan-Video).
     */
    public function createPrediction(array $parameters): array
    {
        $version = !empty($parameters['version']) ? $parameters['version'] : $this->defaultVersion;

        // Compute width and height based on aspect ratio
        $width = (int) ($parameters['width'] ?? 864);
        $height = (int) ($parameters['height'] ?? 480);

        if (!empty($parameters['aspect_ratio'])) {
            switch ($parameters['aspect_ratio']) {
                case '16:9':
                    $width = 864;
                    $height = 480;
                    break;
                case '9:16':
                    $width = 480;
                    $height = 864;
                    break;
                case '4:3':
                    $width = 768;
                    $height = 576;
                    break;
                case '21:9':
                    $width = 1024;
                    $height = 432;
                    break;
                case '1:1':
                default:
                    $width = 512;
                    $height = 512;
                    break;
            }
        }

        // Pure Text-to-Video payload schema for tencent/hunyuan-video
        $input = [
            'prompt' => $parameters['prompt'],
            'width' => $width,
            'height' => $height,
            'fps' => (int) ($parameters['frame_rate'] ?? 24),
        ];

        if (isset($parameters['steps']) && is_numeric($parameters['steps'])) {
            $input['infer_steps'] = (int) $parameters['steps'];
        }

        if (isset($parameters['guidance_scale']) && is_numeric($parameters['guidance_scale'])) {
            $input['guidance_scale'] = (float) $parameters['guidance_scale'];
        }

        if (isset($parameters['video_length']) && is_numeric($parameters['video_length'])) {
            $input['video_length'] = (int) $parameters['video_length'];
        }

        if (isset($parameters['seed']) && is_numeric($parameters['seed'])) {
            $input['seed'] = (int) $parameters['seed'];
        }

        // Simulation if API key is missing
        if (empty($this->apiKey) || str_contains($this->apiKey, 'YOUR_API_MARKET_KEY')) {
            Log::warning('MagicApiVideoService: API Key is missing or placeholder. Simulating video prediction.');
            return $this->simulateCreatePrediction($version, $input);
        }

        $endpoint = "{$this->baseUrl}/predictions";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-api-market-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->connectTimeout(5)->timeout(30)->retry(3, 1000, function ($exception, $request) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                    ($exception->response && in_array($exception->response->status(), [429, 500, 502, 503, 504]));
            })->post($endpoint, [
                'version' => $version,
                'input' => $input,
            ]);

            $statusCode = $response->status();
            $rawBody = $response->body();

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? ($errorData['message'] ?? ($errorData['detail'] ?? $rawBody));

                Log::error("MagicAPI video prediction creation failed with HTTP {$statusCode}", [
                    'status' => $statusCode,
                    'error' => $errorMessage,
                    'response' => $rawBody,
                ]);

                throw new Exception("Video API Error ({$statusCode}): {$errorMessage}");
            }

            $data = $response->json();

            return [
                'prediction_id' => $data['id'] ?? (string) Str::uuid(),
                'status' => strtolower($data['status'] ?? 'starting'),
                'version' => $data['version'] ?? $version,
                'raw' => $data,
                'raw_body' => $rawBody,
                'http_status' => $statusCode,
            ];
        } catch (Exception $e) {
            Log::error('MagicAPI connection error in createPrediction (video): ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch status and output of an existing video prediction.
     */
    public function getPredictionStatus(string $predictionId): array
    {
        if (str_starts_with($predictionId, 'mock_')) {
            return $this->simulatePredictionStatus($predictionId);
        }

        if (empty($this->apiKey) || str_contains($this->apiKey, 'YOUR_API_MARKET_KEY')) {
            throw new Exception('API Market key is not configured in .env (API_MARKET_KEY).');
        }

        $endpoint = "{$this->baseUrl}/predictions/{$predictionId}";

        try {
            $response = Http::withHeaders([
                'x-api-market-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->connectTimeout(5)->timeout(10)->retry(2, 500, function ($exception, $request) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                    ($exception->response && in_array($exception->response->status(), [429, 500, 502, 503, 504]));
            })->get($endpoint);

            $statusCode = $response->status();
            $rawBody = $response->body();

            if ($response->failed()) {
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? ($errorData['message'] ?? "HTTP {$statusCode}");

                // Transient errors: continue polling
                if (in_array($statusCode, [429, 500, 502, 503, 504])) {
                    return [
                        'id' => $predictionId,
                        'status' => 'processing',
                        'output' => null,
                        'error' => null,
                        'http_status' => $statusCode,
                        'raw_body' => $rawBody,
                        'raw' => ['transient_error' => "HTTP {$statusCode}: {$errorMessage}"],
                    ];
                }

                throw new Exception("Video API Status Error ({$statusCode}): {$errorMessage}");
            }

            $data = $response->json();

            // Extract output video URL
            $outputUrl = null;
            if (!empty($data['output'])) {
                if (is_array($data['output'])) {
                    $outputUrl = $data['output'][0] ?? null;
                } else {
                    $outputUrl = (string) $data['output'];
                }
            } elseif (!empty($data['result'])) {
                $outputUrl = is_array($data['result']) ? ($data['result'][0] ?? null) : (string) $data['result'];
            }

            $status = strtolower(trim($data['status'] ?? 'processing'));

            return [
                'id' => $data['id'] ?? $predictionId,
                'status' => $status,
                'output' => $outputUrl,
                'error' => $data['error'] ?? null,
                'http_status' => $statusCode,
                'raw_body' => $rawBody,
                'raw' => $data,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning("MagicAPI video connection timeout notice for prediction {$predictionId}: " . $e->getMessage());
            return [
                'id' => $predictionId,
                'status' => 'processing',
                'output' => null,
                'error' => null,
                'http_status' => 0,
                'raw_body' => '{"transient_timeout": true}',
                'raw' => ['transient_timeout' => $e->getMessage()],
            ];
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'cURL error 28') || str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'Connection reset')) {
                Log::warning("MagicAPI video timeout notice for prediction {$predictionId}: " . $e->getMessage());
                return [
                    'id' => $predictionId,
                    'status' => 'processing',
                    'output' => null,
                    'error' => null,
                    'http_status' => 0,
                    'raw_body' => '{"transient_timeout": true}',
                    'raw' => ['transient_timeout' => $e->getMessage()],
                ];
            }

            Log::error("MagicAPI video exception checking status for {$predictionId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Download the remote generated video and store it in server local storage.
     */
    public function downloadAndStoreVideo(string $remoteUrl): string
    {
        try {
            $response = Http::timeout(120)->retry(3, 1500)->get($remoteUrl);

            if ($response->failed()) {
                throw new Exception("Video download failed with HTTP status: " . $response->status());
            }

            $filename = 'videos/' . Str::uuid() . '.mp4';
            Storage::disk('public')->put($filename, $response->body());

            return $filename;
        } catch (Exception $e) {
            Log::error('Failed to download and store video locally: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Attempt to cancel an active video prediction on the provider.
     */
    public function cancelPrediction(string $predictionId): bool
    {
        if (str_starts_with($predictionId, 'mock_') || empty($this->apiKey)) {
            return true;
        }

        try {
            $endpoint = "{$this->baseUrl}/predictions/{$predictionId}/cancel";
            $response = Http::withHeaders([
                'x-api-market-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->connectTimeout(5)->timeout(10)->post($endpoint);

            return $response->successful();
        } catch (Exception $e) {
            Log::info("Provider-side video cancellation notice for {$predictionId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mock simulation for testing when key is absent
     */
    protected function simulateCreatePrediction(string $version, array $input): array
    {
        $mockId = 'mock_vid_' . Str::random(16);
        session()->put("mock_pred_{$mockId}", [
            'created_at' => time(),
            'input' => $input,
        ]);

        return [
            'prediction_id' => $mockId,
            'status' => 'starting',
            'version' => $version,
            'raw' => ['mock' => true],
            'raw_body' => '{"mock": true}',
            'http_status' => 201,
        ];
    }

    /**
     * Mock video prediction status helper
     */
    protected function simulatePredictionStatus(string $predictionId): array
    {
        $mock = session()->get("mock_pred_{$predictionId}", ['created_at' => time() - 3]);
        $elapsed = time() - ($mock['created_at'] ?? time());

        if ($elapsed < 3) {
            return [
                'id' => $predictionId,
                'status' => 'processing',
                'output' => null,
                'error' => null,
                'http_status' => 200,
                'raw_body' => '{"status":"processing","output":null}',
                'raw' => ['mock' => true, 'elapsed' => $elapsed],
            ];
        }

        $outputUrl = "https://replicate.delivery/xezq/fPeeEckco8DfnQ8zUUpbawLy0ezXMEP5if95pXKyAYFKOf58JA/HunhuyanVideo_00001.mp4";

        return [
            'id' => $predictionId,
            'status' => 'succeeded',
            'output' => $outputUrl,
            'error' => null,
            'http_status' => 200,
            'raw_body' => '{"status":"succeeded","output":"' . $outputUrl . '"}',
            'raw' => ['mock' => true],
        ];
    }
}
