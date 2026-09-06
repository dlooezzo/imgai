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

class ModelController extends Controller
{
    /**
     * Display AI Models configuration and status.
     */
    public function index(Request $request)
    {
        $models = [
            'image' => [
                'name' => SiteSetting::get('ai_model_image_name', 'Cinematic Text-to-Image (MagicAPI)'),
                'key' => 'image',
                'category' => 'Image Generation',
                'base_url' => SiteSetting::get('ai_model_image_url', config('services.magicapi.base_url', env('MAGICAPI_BASE_URL', 'https://prod.api.market/api/v1/magicapi/cinematic-text-to-image-generator'))),
                'version' => SiteSetting::get('ai_model_image_version', config('services.magicapi.version', env('MAGICAPI_MODEL_VERSION', '16e15e913fcc71c1a5defb335ea84739f99731fa1ee17995117c7d9adc6d176c'))),
                'output_format' => 'JPG / WebP (Up to 2MP)',
                'is_configured' => !empty(SiteSetting::get('api_market_key', config('services.magicapi.key', env('API_MARKET_KEY')))),
            ],
            'video' => [
                'name' => SiteSetting::get('ai_model_video_name', 'Tencent Hunyuan-Video (Text-to-Video)'),
                'key' => 'video',
                'category' => 'Video Generation',
                'base_url' => SiteSetting::get('ai_model_video_url', config('services.magicapi.video_base_url', env('MAGICAPI_VIDEO_BASE_URL', 'https://prod.api.market/api/v1/magicapi/hunyuan-video'))),
                'version' => SiteSetting::get('ai_model_video_version', config('services.magicapi.video_version', env('MAGICAPI_VIDEO_VERSION', '6c9132aee14409cd6568d030453f1ba50f5f3412b844fe67f78a9eb62d55664f'))),
                'output_format' => 'MP4 (720p / 480p, 24 FPS)',
                'is_configured' => !empty(SiteSetting::get('api_market_key', config('services.magicapi.key', env('API_MARKET_KEY')))),
            ],
            'image_to_video' => [
                'name' => SiteSetting::get('ai_model_i2v_name', 'Wan 2.2 Image-to-Video (MagicAPI)'),
                'key' => 'image_to_video',
                'category' => 'Image-to-Video',
                'base_url' => SiteSetting::get('ai_model_i2v_url', config('services.magicapi.image_to_video_base_url', env('MAGICAPI_IMAGE_TO_VIDEO_BASE_URL', 'https://prod.api.market/api/v1/magicapi/ultra-fast-text-to-image-image-to-video-api'))),
                'version' => SiteSetting::get('ai_model_i2v_version', config('services.magicapi.image_to_video_version', env('MAGICAPI_IMAGE_TO_VIDEO_VERSION', 'c92ab4265c9b3b5ea9ac9a87df839ebfd662ee3a820d62c21305bf6501a73fe1'))),
                'output_format' => 'MP4 (720p / 480p, 81 frames, 24 FPS)',
                'is_configured' => !empty(SiteSetting::get('api_market_key', config('services.magicapi.key', env('API_MARKET_KEY')))),
            ],
        ];

        return view('admin.models.index', [
            'models' => $models,
            'hasApiKey' => !empty(SiteSetting::get('api_market_key', config('services.magicapi.key', env('API_MARKET_KEY')))),
        ]);
    }

    /**
     * Update and persist AI Model endpoints, names, and versions.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'ai_model_image_name' => 'nullable|string|max:150',
            'ai_model_image_url' => 'nullable|string|max:255',
            'ai_model_image_version' => 'nullable|string|max:255',

            'ai_model_video_name' => 'nullable|string|max:150',
            'ai_model_video_url' => 'nullable|string|max:255',
            'ai_model_video_version' => 'nullable|string|max:255',

            'ai_model_i2v_name' => 'nullable|string|max:150',
            'ai_model_i2v_url' => 'nullable|string|max:255',
            'ai_model_i2v_version' => 'nullable|string|max:255',
        ]);

        if (isset($validated['ai_model_image_name'])) {
            SiteSetting::set('ai_model_image_name', trim($validated['ai_model_image_name']));
        }
        if (isset($validated['ai_model_image_url'])) {
            $url = rtrim(trim($validated['ai_model_image_url']), '/');
            SiteSetting::set('ai_model_image_url', $url);
            config(['services.magicapi.base_url' => $url]);
        }
        if (isset($validated['ai_model_image_version'])) {
            $ver = trim($validated['ai_model_image_version']);
            SiteSetting::set('ai_model_image_version', $ver);
            config(['services.magicapi.version' => $ver]);
        }

        if (isset($validated['ai_model_video_name'])) {
            SiteSetting::set('ai_model_video_name', trim($validated['ai_model_video_name']));
        }
        if (isset($validated['ai_model_video_url'])) {
            $url = rtrim(trim($validated['ai_model_video_url']), '/');
            SiteSetting::set('ai_model_video_url', $url);
            config(['services.magicapi.video_base_url' => $url]);
        }
        if (isset($validated['ai_model_video_version'])) {
            $ver = trim($validated['ai_model_video_version']);
            SiteSetting::set('ai_model_video_version', $ver);
            config(['services.magicapi.video_version' => $ver]);
        }

        if (isset($validated['ai_model_i2v_name'])) {
            SiteSetting::set('ai_model_i2v_name', trim($validated['ai_model_i2v_name']));
        }
        if (isset($validated['ai_model_i2v_url'])) {
            $url = rtrim(trim($validated['ai_model_i2v_url']), '/');
            SiteSetting::set('ai_model_i2v_url', $url);
            config(['services.magicapi.image_to_video_base_url' => $url]);
        }
        if (isset($validated['ai_model_i2v_version'])) {
            $ver = trim($validated['ai_model_i2v_version']);
            SiteSetting::set('ai_model_i2v_version', $ver);
            config(['services.magicapi.image_to_video_version' => $ver]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'AI Generation Models configuration successfully saved!',
            ]);
        }

        return redirect()->back()->with('success', 'AI Generation Models and endpoints successfully updated and saved!');
    }

    /**
     * Test connection to a specific AI model endpoint.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $modelKey = $request->input('model', 'image');
        $apiKey = SiteSetting::get('api_market_key', config('services.magicapi.key', env('API_MARKET_KEY')));

        if (empty($apiKey) || str_contains($apiKey, 'YOUR_API_MARKET_KEY')) {
            return response()->json([
                'success' => false,
                'message' => 'API_MARKET_KEY is missing or unconfigured. Please set it in API Market Settings.',
                'latency_ms' => 0,
            ], 422);
        }

        $urlMap = [
            'image' => SiteSetting::get('ai_model_image_url', config('services.magicapi.base_url', env('MAGICAPI_BASE_URL', 'https://prod.api.market/api/v1/magicapi/cinematic-text-to-image-generator'))),
            'video' => SiteSetting::get('ai_model_video_url', config('services.magicapi.video_base_url', env('MAGICAPI_VIDEO_BASE_URL', 'https://prod.api.market/api/v1/magicapi/hunyuan-video'))),
            'image_to_video' => SiteSetting::get('ai_model_i2v_url', config('services.magicapi.image_to_video_base_url', env('MAGICAPI_IMAGE_TO_VIDEO_BASE_URL', 'https://prod.api.market/api/v1/magicapi/ultra-fast-text-to-image-image-to-video-api'))),
        ];

        $targetUrl = $urlMap[$modelKey] ?? $urlMap['image'];

        $startTime = microtime(true);

        try {
            // Send lightweight handshake / predictions probe
            $response = Http::withHeaders([
                'x-api-market-key' => $apiKey,
                'Accept' => 'application/json',
            ])->timeout(10)->get($targetUrl);

            $latency = round((microtime(true) - $startTime) * 1000, 2);

            // Even if the endpoint expects POST and returns 404 or 405 on GET, the server connection is reachable
            $isReachable = $response->status() < 500;

            return response()->json([
                'success' => $isReachable,
                'status_code' => $response->status(),
                'message' => $isReachable ? "Endpoint is reachable (HTTP {$response->status()})." : "Endpoint returned error (HTTP {$response->status()}).",
                'latency_ms' => $latency,
                'url' => $targetUrl,
            ]);
        } catch (Exception $e) {
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            Log::error("Model test connection failed for {$modelKey}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'latency_ms' => $latency,
            ], 500);
        }
    }
}
