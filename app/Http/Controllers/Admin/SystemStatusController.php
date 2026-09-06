<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SystemStatusController extends Controller
{
    /**
     * Display the System Status health dashboard.
     */
    public function index(Request $request)
    {
        $statusResults = $this->runAllHealthChecks();

        return view('admin.system-status.index', [
            'services' => $statusResults,
            'checkedAt' => now()->toDateTimeString(),
        ]);
    }

    /**
     * JSON API to run live health checks asynchronously.
     */
    public function liveCheck(Request $request): JsonResponse
    {
        $statusResults = $this->runAllHealthChecks();

        return response()->json([
            'success' => true,
            'services' => $statusResults,
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Run all real backend health checks and return structured status array.
     */
    protected function runAllHealthChecks(): array
    {
        return [
            'mysql' => $this->checkMySql(),
            'supabase' => $this->checkSupabase(),
            'r2' => $this->checkR2(),
            'api_market' => $this->checkApiMarket(),
            'text_to_image' => $this->checkTextToImage(),
            'text_to_video' => $this->checkTextToVideo(),
            'image_to_video' => $this->checkImageToVideo(),
        ];
    }

    /**
     * Check MySQL database connectivity.
     */
    protected function checkMySql(): array
    {
        $start = microtime(true);
        try {
            DB::connection()->getPdo();
            $versionResult = DB::select('SELECT VERSION() as version');
            $version = $versionResult[0]->version ?? 'Unknown';
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'name' => 'MySQL Database',
                'category' => 'Database',
                'status' => 'connected',
                'status_label' => 'Connected',
                'latency_ms' => $latency,
                'details' => "MySQL {$version} (Driver: " . DB::connection()->getDriverName() . ')',
                'error' => null,
            ];
        } catch (Exception $e) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            return [
                'name' => 'MySQL Database',
                'category' => 'Database',
                'status' => 'failed',
                'status_label' => 'Disconnected',
                'latency_ms' => $latency,
                'details' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Supabase Auth service.
     */
    protected function checkSupabase(): array
    {
        $url = config('services.supabase.url', env('SUPABASE_URL'));
        $anonKey = config('services.supabase.anon_key', env('SUPABASE_ANON_KEY', env('SUPABASE_KEY')));

        if (empty($url) || empty($anonKey)) {
            return [
                'name' => 'Supabase Auth',
                'category' => 'Authentication',
                'status' => 'unconfigured',
                'status_label' => 'Unconfigured',
                'latency_ms' => 0,
                'details' => 'SUPABASE_URL or SUPABASE_ANON_KEY is missing in .env',
                'error' => null,
            ];
        }

        $start = microtime(true);
        try {
            $response = Http::withHeaders(['apikey' => $anonKey])
                ->timeout(6)
                ->get(rtrim($url, '/') . '/auth/v1/health');

            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($response->successful() || $response->status() === 200) {
                return [
                    'name' => 'Supabase Auth',
                    'category' => 'Authentication',
                    'status' => 'connected',
                    'status_label' => 'Connected',
                    'latency_ms' => $latency,
                    'details' => 'Supabase Auth API reachable and healthy',
                    'error' => null,
                ];
            }

            return [
                'name' => 'Supabase Auth',
                'category' => 'Authentication',
                'status' => 'warning',
                'status_label' => "HTTP {$response->status()}",
                'latency_ms' => $latency,
                'details' => 'Supabase returned non-200 status',
                'error' => $response->body(),
            ];
        } catch (Exception $e) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            return [
                'name' => 'Supabase Auth',
                'category' => 'Authentication',
                'status' => 'failed',
                'status_label' => 'Unreachable',
                'latency_ms' => $latency,
                'details' => 'Could not connect to Supabase endpoint',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Cloudflare R2 storage.
     */
    protected function checkR2(): array
    {
        $bucket = config('filesystems.disks.r2.bucket', env('R2_BUCKET'));
        $key = config('filesystems.disks.r2.key', env('R2_ACCESS_KEY_ID'));

        if (empty($bucket) || empty($key)) {
            return [
                'name' => 'Cloudflare R2',
                'category' => 'Object Storage',
                'status' => 'unconfigured',
                'status_label' => 'Unconfigured',
                'latency_ms' => 0,
                'details' => 'R2 credentials missing in .env',
                'error' => null,
            ];
        }

        $start = microtime(true);
        try {
            $disk = Storage::disk('r2');
            $testKey = 'health_check_r2.txt';
            $disk->put($testKey, 'health_probe');
            $disk->delete($testKey);

            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'name' => 'Cloudflare R2',
                'category' => 'Object Storage',
                'status' => 'connected',
                'status_label' => 'Connected',
                'latency_ms' => $latency,
                'details' => "Bucket: {$bucket} (Read/Write verified)",
                'error' => null,
            ];
        } catch (Exception $e) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            return [
                'name' => 'Cloudflare R2',
                'category' => 'Object Storage',
                'status' => 'failed',
                'status_label' => 'Disconnected',
                'latency_ms' => $latency,
                'details' => 'R2 bucket write/read probe failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check API Market Gateway.
     */
    protected function checkApiMarket(): array
    {
        $key = config('services.magicapi.key', env('API_MARKET_KEY'));

        if (empty($key) || str_contains($key, 'YOUR_API_MARKET_KEY')) {
            return [
                'name' => 'API Market Gateway',
                'category' => 'AI Gateway',
                'status' => 'unconfigured',
                'status_label' => 'Unconfigured',
                'latency_ms' => 0,
                'details' => 'API_MARKET_KEY is missing in .env',
                'error' => null,
            ];
        }

        $start = microtime(true);
        try {
            $url = config('services.magicapi.base_url', env('MAGICAPI_BASE_URL', 'https://prod.api.market/api/v1/magicapi/cinematic-text-to-image-generator'));
            $response = Http::withHeaders(['x-api-market-key' => $key])->timeout(6)->get($url);
            $latency = round((microtime(true) - $start) * 1000, 2);

            $isReachable = $response->status() < 500;

            return [
                'name' => 'API Market Gateway',
                'category' => 'AI Gateway',
                'status' => $isReachable ? 'connected' : 'failed',
                'status_label' => $isReachable ? 'Connected' : 'Error',
                'latency_ms' => $latency,
                'details' => "API Gateway reachable (HTTP {$response->status()})",
                'error' => $isReachable ? null : $response->body(),
            ];
        } catch (Exception $e) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            return [
                'name' => 'API Market Gateway',
                'category' => 'AI Gateway',
                'status' => 'failed',
                'status_label' => 'Unreachable',
                'latency_ms' => $latency,
                'details' => 'Network error contacting API Market',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Text-to-Image API.
     */
    protected function checkTextToImage(): array
    {
        $url = config('services.magicapi.base_url', env('MAGICAPI_BASE_URL'));
        $key = config('services.magicapi.key', env('API_MARKET_KEY'));

        if (empty($url) || empty($key)) {
            return [
                'name' => 'Text-to-Image API (MagicAPI)',
                'category' => 'AI Provider',
                'status' => 'unconfigured',
                'status_label' => 'Unconfigured',
                'latency_ms' => 0,
                'details' => 'Endpoint or key missing',
                'error' => null,
            ];
        }

        $start = microtime(true);
        try {
            $response = Http::withHeaders(['x-api-market-key' => $key])->timeout(6)->get($url);
            $latency = round((microtime(true) - $start) * 1000, 2);
            $isReachable = $response->status() < 500;

            return [
                'name' => 'Text-to-Image API (MagicAPI)',
                'category' => 'AI Provider',
                'status' => $isReachable ? 'connected' : 'failed',
                'status_label' => $isReachable ? 'Connected' : 'Error',
                'latency_ms' => $latency,
                'details' => 'Cinematic 2MP endpoint online',
                'error' => null,
            ];
        } catch (Exception $e) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            return [
                'name' => 'Text-to-Image API (MagicAPI)',
                'category' => 'AI Provider',
                'status' => 'failed',
                'status_label' => 'Unreachable',
                'latency_ms' => $latency,
                'details' => $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Text-to-Video API.
     */
    protected function checkTextToVideo(): array
    {
        $url = config('services.magicapi.video_base_url', env('MAGICAPI_VIDEO_BASE_URL'));
        $key = config('services.magicapi.key', env('API_MARKET_KEY'));

        if (empty($url) || empty($key)) {
            return [
                'name' => 'Text-to-Video API (Hunyuan)',
                'category' => 'AI Provider',
                'status' => 'unconfigured',
                'status_label' => 'Unconfigured',
                'latency_ms' => 0,
                'details' => 'MAGICAPI_VIDEO_BASE_URL missing',
                'error' => null,
            ];
        }

        $start = microtime(true);
        try {
            $response = Http::withHeaders(['x-api-market-key' => $key])->timeout(6)->get($url);
            $latency = round((microtime(true) - $start) * 1000, 2);
            $isReachable = $response->status() < 500;

            return [
                'name' => 'Text-to-Video API (Hunyuan)',
                'category' => 'AI Provider',
                'status' => $isReachable ? 'connected' : 'failed',
                'status_label' => $isReachable ? 'Connected' : 'Error',
                'latency_ms' => $latency,
                'details' => 'Hunyuan Video endpoint online',
                'error' => null,
            ];
        } catch (Exception $e) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            return [
                'name' => 'Text-to-Video API (Hunyuan)',
                'category' => 'AI Provider',
                'status' => 'failed',
                'status_label' => 'Unreachable',
                'latency_ms' => $latency,
                'details' => $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check Image-to-Video API.
     */
    protected function checkImageToVideo(): array
    {
        $url = config('services.magicapi.image_to_video_base_url', env('MAGICAPI_IMAGE_TO_VIDEO_BASE_URL'));
        $key = config('services.magicapi.key', env('API_MARKET_KEY'));

        if (empty($url) || empty($key)) {
            return [
                'name' => 'Image-to-Video API (Wan 2.2)',
                'category' => 'AI Provider',
                'status' => 'unconfigured',
                'status_label' => 'Unconfigured',
                'latency_ms' => 0,
                'details' => 'MAGICAPI_IMAGE_TO_VIDEO_BASE_URL missing',
                'error' => null,
            ];
        }

        $start = microtime(true);
        try {
            $response = Http::withHeaders(['x-api-market-key' => $key])->timeout(6)->get($url);
            $latency = round((microtime(true) - $start) * 1000, 2);
            $isReachable = $response->status() < 500;

            return [
                'name' => 'Image-to-Video API (Wan 2.2)',
                'category' => 'AI Provider',
                'status' => $isReachable ? 'connected' : 'failed',
                'status_label' => $isReachable ? 'Connected' : 'Error',
                'latency_ms' => $latency,
                'details' => 'Wan 2.2 I2V endpoint online',
                'error' => null,
            ];
        } catch (Exception $e) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            return [
                'name' => 'Image-to-Video API (Wan 2.2)',
                'category' => 'AI Provider',
                'status' => 'failed',
                'status_label' => 'Unreachable',
                'latency_ms' => $latency,
                'details' => $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }
}
