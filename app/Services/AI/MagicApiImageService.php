<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\ImageGenerationInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MagicApiImageService implements ImageGenerationInterface
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected string $defaultVersion;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.magicapi.base_url') ?: env('MAGICAPI_BASE_URL', 'https://prod.api.market/api/v1/magicapi/cinematic-text-to-image-generator'), '/');
        $this->apiKey = config('services.magicapi.key') ?: env('API_MARKET_KEY');
        $this->defaultVersion = config('services.magicapi.version') ?: env('MAGICAPI_MODEL_VERSION', '16e15e913fcc71c1a5defb335ea84739f99731fa1ee17995117c7d9adc6d176c');
    }

    /**
     * Submit an asynchronous text-to-image prediction request.
     */
    public function createPrediction(array $parameters): array
    {
        $version = !empty($parameters['version']) ? $parameters['version'] : $this->defaultVersion;

        $input = [
            'prompt' => $parameters['prompt'],
            'aspect_ratio' => $parameters['aspect_ratio'] ?? '1:1',
            'megapixels' => (int) ($parameters['megapixels'] ?? 2),
            'output_format' => strtolower($parameters['output_format'] ?? 'jpg'),
            'juiced' => (bool) ($parameters['juiced'] ?? false),
        ];

        if (isset($parameters['seed']) && is_numeric($parameters['seed'])) {
            $input['seed'] = (int) $parameters['seed'];
        }

        if ($input['output_format'] === 'jpg' && isset($parameters['output_quality'])) {
            $input['output_quality'] = (int) $parameters['output_quality'];
        }

        // Check if API Key is set
        if (empty($this->apiKey) || str_contains($this->apiKey, 'YOUR_API_MARKET_KEY')) {
            Log::warning('MagicApiImageService: API Key is missing or using placeholder. Simulating prediction.');
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
                $errorMessage = $errorData['error']['message'] ?? ($errorData['message'] ?? $rawBody);

                Log::error("MagicAPI prediction creation failed with HTTP {$statusCode}", [
                    'status' => $statusCode,
                    'error' => $errorMessage,
                    'response' => $rawBody,
                ]);

                throw new Exception("Image API Error ({$statusCode}): {$errorMessage}");
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
            Log::error('MagicAPI connection error in createPrediction: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch status and output of an existing prediction.
     */
    public function getPredictionStatus(string $predictionId): array
    {
        // Handle mock simulation ID
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

                // For temporary rate-limiting (429) or server maintenance (503/500/502/504), keep state as processing
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

                throw new Exception("API Status Error ({$statusCode}): {$errorMessage}");
            }

            $data = $response->json();

            // Extract output image URL (handles string or array)
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
            // Polling timeout / cURL error 28 / connection reset: do NOT fail generation
            Log::warning("MagicAPI connection timeout notice for prediction {$predictionId}: " . $e->getMessage());
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
                Log::warning("MagicAPI timeout notice for prediction {$predictionId}: " . $e->getMessage());
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

            Log::error("MagicAPI exception checking status for {$predictionId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Attempt to cancel an active prediction on the provider.
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
            Log::info("Provider-side cancellation not supported or completed locally for {$predictionId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Download the remote image and store it in server local storage.
     */
    public function downloadAndStoreImage(string $remoteUrl, string $format = 'jpg'): string
    {
        try {
            $response = Http::timeout(60)->retry(3, 1000)->get($remoteUrl);

            if ($response->failed()) {
                throw new Exception("Download failed with HTTP status: " . $response->status());
            }

            $extension = in_array(strtolower($format), ['png', 'jpg', 'jpeg', 'webp']) ? strtolower($format) : 'jpg';
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            $filename = 'generations/' . Str::uuid() . '.' . $extension;

            Storage::disk('public')->put($filename, $response->body());

            return $filename;
        } catch (Exception $e) {
            Log::error('Failed to download and store image locally: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mock simulation for testing when key is absent
     */
    protected function simulateCreatePrediction(string $version, array $input): array
    {
        $mockId = 'mock_' . Str::random(16);
        session()->put("mock_pred_{$mockId}", [
            'created_at' => time(),
            'input' => $input,
            'aspect_ratio' => $input['aspect_ratio'] ?? '1:1',
            'format' => $input['output_format'] ?? 'jpg',
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
     * Mock prediction status helper
     */
    protected function simulatePredictionStatus(string $predictionId): array
    {
        $mock = session()->get("mock_pred_{$predictionId}", ['created_at' => time() - 3, 'format' => 'jpg', 'aspect_ratio' => '1:1']);
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

        $ratio = $mock['aspect_ratio'] ?? '1:1';
        $width = 1024;
        $height = 1024;
        if ($ratio === '16:9') { $width = 1280; $height = 720; }
        elseif ($ratio === '9:16') { $width = 720; $height = 1280; }
        elseif ($ratio === '4:3') { $width = 1024; $height = 768; }
        elseif ($ratio === '3:4') { $width = 768; $height = 1024; }
        elseif ($ratio === '21:9') { $width = 1344; $height = 576; }

        $outputUrl = "https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w={$width}&h={$height}&q=80";

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
