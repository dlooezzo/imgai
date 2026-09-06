<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiMarketController extends Controller
{
    /**
     * Display API Market settings and configuration overview.
     */
    public function index(Request $request)
    {
        $rawKey = SiteSetting::get('api_market_key', config('services.magicapi.key', env('API_MARKET_KEY', '')));
        $isConfigured = !empty($rawKey) && !str_contains($rawKey, 'YOUR_API_MARKET_KEY');

        $imgUrl = SiteSetting::get('api_market_base_url', SiteSetting::get('ai_model_image_url', config('services.magicapi.base_url', env('MAGICAPI_BASE_URL', ''))));
        $vidUrl = SiteSetting::get('api_market_video_base_url', SiteSetting::get('ai_model_video_url', config('services.magicapi.video_base_url', env('MAGICAPI_VIDEO_BASE_URL', ''))));
        $i2vUrl = SiteSetting::get('api_market_i2v_base_url', SiteSetting::get('ai_model_i2v_url', config('services.magicapi.image_to_video_base_url', env('MAGICAPI_IMAGE_TO_VIDEO_BASE_URL', ''))));

        $providers = [
            [
                'name' => 'Cinematic 2MP Text-to-Image',
                'key' => 'api_market_base_url',
                'endpoint' => $imgUrl,
                'version' => SiteSetting::get('ai_model_image_version', config('services.magicapi.version', env('MAGICAPI_MODEL_VERSION', ''))),
                'type' => 'Text-to-Image',
                'status' => $isConfigured ? 'Active' : 'Unconfigured',
            ],
            [
                'name' => 'Tencent Hunyuan-Video',
                'key' => 'api_market_video_base_url',
                'endpoint' => $vidUrl,
                'version' => SiteSetting::get('ai_model_video_version', config('services.magicapi.video_version', env('MAGICAPI_VIDEO_VERSION', ''))),
                'type' => 'Text-to-Video',
                'status' => $isConfigured ? 'Active' : 'Unconfigured',
            ],
            [
                'name' => 'Wan 2.2 Image-to-Video',
                'key' => 'api_market_i2v_base_url',
                'endpoint' => $i2vUrl,
                'version' => SiteSetting::get('ai_model_i2v_version', config('services.magicapi.image_to_video_version', env('MAGICAPI_IMAGE_TO_VIDEO_VERSION', ''))),
                'type' => 'Image-to-Video',
                'status' => $isConfigured ? 'Active' : 'Unconfigured',
            ],
        ];

        return view('admin.api-market.index', [
            'apiKey' => $rawKey,
            'isConfigured' => $isConfigured,
            'imgUrl' => $imgUrl,
            'vidUrl' => $vidUrl,
            'i2vUrl' => $i2vUrl,
            'providers' => $providers,
        ]);
    }

    /**
     * Update and persist API Market Gateway credentials and routes.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'api_market_key' => 'nullable|string|max:255',
            'api_market_base_url' => 'nullable|string|max:255',
            'api_market_video_base_url' => 'nullable|string|max:255',
            'api_market_i2v_base_url' => 'nullable|string|max:255',
        ]);

        if (isset($validated['api_market_key'])) {
            $key = trim($validated['api_market_key']);
            SiteSetting::set('api_market_key', $key);
            config(['services.magicapi.key' => $key]);
        }

        if (isset($validated['api_market_base_url'])) {
            $url = rtrim(trim($validated['api_market_base_url']), '/');
            SiteSetting::set('api_market_base_url', $url);
            SiteSetting::set('ai_model_image_url', $url);
            config(['services.magicapi.base_url' => $url]);
        }

        if (isset($validated['api_market_video_base_url'])) {
            $url = rtrim(trim($validated['api_market_video_base_url']), '/');
            SiteSetting::set('api_market_video_base_url', $url);
            SiteSetting::set('ai_model_video_url', $url);
            config(['services.magicapi.video_base_url' => $url]);
        }

        if (isset($validated['api_market_i2v_base_url'])) {
            $url = rtrim(trim($validated['api_market_i2v_base_url']), '/');
            SiteSetting::set('api_market_i2v_base_url', $url);
            SiteSetting::set('ai_model_i2v_url', $url);
            config(['services.magicapi.image_to_video_base_url' => $url]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'API Market Gateway settings successfully saved!',
            ]);
        }

        return redirect()->back()->with('success', 'API Market Gateway configuration successfully updated and saved!');
    }

    /**
     * Test API Market authentication and connectivity.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $apiKey = SiteSetting::get('api_market_key', config('services.magicapi.key', env('API_MARKET_KEY')));

        if (empty($apiKey) || str_contains($apiKey, 'YOUR_API_MARKET_KEY')) {
            return response()->json([
                'success' => false,
                'message' => 'API_MARKET_KEY is not configured. Please input your valid key and save.',
                'latency_ms' => 0,
            ], 422);
        }

        $testUrl = SiteSetting::get('api_market_base_url', config('services.magicapi.base_url', env('MAGICAPI_BASE_URL', 'https://prod.api.market/api/v1/magicapi/cinematic-text-to-image-generator')));

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders([
                'x-api-market-key' => $apiKey,
                'Accept' => 'application/json',
            ])->timeout(10)->get($testUrl);

            $latency = round((microtime(true) - $startTime) * 1000, 2);

            $isReachable = $response->status() < 500;

            return response()->json([
                'success' => $isReachable,
                'message' => $isReachable ? "API Market gateway reachable (HTTP {$response->status()})." : "API Market returned HTTP {$response->status()}.",
                'latency_ms' => $latency,
                'status_code' => $response->status(),
            ]);
        } catch (Exception $e) {
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            Log::error('API Market test failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to reach API Market: ' . $e->getMessage(),
                'latency_ms' => $latency,
            ], 500);
        }
    }
}
