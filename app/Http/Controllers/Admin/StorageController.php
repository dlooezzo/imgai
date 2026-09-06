<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\VideoGeneration;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StorageController extends Controller
{
    /**
     * Display Cloudflare R2 Storage configuration and metrics.
     */
    public function index(Request $request)
    {
        $bucket = SiteSetting::get('r2_bucket', config('filesystems.disks.r2.bucket', env('R2_BUCKET', '')));
        $endpoint = SiteSetting::get('r2_endpoint', config('filesystems.disks.r2.endpoint', env('R2_ENDPOINT', '')));
        $publicUrl = SiteSetting::get('r2_public_url', config('filesystems.disks.r2.url', env('R2_PUBLIC_URL', '')));
        $region = SiteSetting::get('r2_region', config('filesystems.disks.r2.region', env('R2_REGION', 'auto')));

        $rawKey = SiteSetting::get('r2_access_key_id', config('filesystems.disks.r2.key', env('R2_ACCESS_KEY_ID', '')));
        $rawSecret = SiteSetting::get('r2_secret_access_key', config('filesystems.disks.r2.secret', env('R2_SECRET_ACCESS_KEY', '')));

        $isConfigured = !empty($bucket) && !empty($endpoint) && !empty($rawKey) && !empty($rawSecret);

        // Count R2 objects tracked in database
        $r2VideoCount = VideoGeneration::whereNotNull('video_path')
            ->where(function ($q) {
                $q->where('video_path', 'like', 'users/%')
                  ->orWhere('video_path', 'like', '%image-to-video%');
            })->count();

        $r2SourceImageCount = VideoGeneration::whereNotNull('source_image_path')
            ->where('source_image_path', 'like', 'users/%')
            ->count();

        $totalR2Files = $r2VideoCount + $r2SourceImageCount;

        return view('admin.storage.index', [
            'bucket' => $bucket,
            'endpoint' => $endpoint,
            'publicUrl' => $publicUrl,
            'region' => $region,
            'accessKeyId' => $rawKey,
            'secretAccessKey' => $rawSecret,
            'isConfigured' => $isConfigured,
            'r2VideoCount' => $r2VideoCount,
            'r2SourceImageCount' => $r2SourceImageCount,
            'totalR2Files' => $totalR2Files,
        ]);
    }

    /**
     * Save updated Cloudflare R2 credentials and endpoints.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'r2_bucket' => 'nullable|string|max:150',
            'r2_endpoint' => 'nullable|string|max:255',
            'r2_public_url' => 'nullable|string|max:255',
            'r2_region' => 'nullable|string|max:50',
            'r2_access_key_id' => 'nullable|string|max:255',
            'r2_secret_access_key' => 'nullable|string|max:255',
        ]);

        SiteSetting::set('r2_bucket', trim($validated['r2_bucket'] ?? ''));
        SiteSetting::set('r2_endpoint', rtrim(trim($validated['r2_endpoint'] ?? ''), '/'));
        SiteSetting::set('r2_public_url', rtrim(trim($validated['r2_public_url'] ?? ''), '/'));
        SiteSetting::set('r2_region', trim($validated['r2_region'] ?? 'auto') ?: 'auto');
        
        if (!empty($validated['r2_access_key_id'])) {
            SiteSetting::set('r2_access_key_id', trim($validated['r2_access_key_id']));
        }
        if (!empty($validated['r2_secret_access_key'])) {
            SiteSetting::set('r2_secret_access_key', trim($validated['r2_secret_access_key']));
        }

        // Apply to runtime config immediately
        config([
            'filesystems.disks.r2.bucket' => SiteSetting::get('r2_bucket'),
            'filesystems.disks.r2.endpoint' => SiteSetting::get('r2_endpoint'),
            'filesystems.disks.r2.url' => SiteSetting::get('r2_public_url'),
            'filesystems.disks.r2.region' => SiteSetting::get('r2_region'),
            'filesystems.disks.r2.key' => SiteSetting::get('r2_access_key_id'),
            'filesystems.disks.r2.secret' => SiteSetting::get('r2_secret_access_key'),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cloudflare R2 storage settings successfully saved!',
            ]);
        }

        return redirect()->back()->with('success', 'Cloudflare R2 storage parameters successfully updated and saved!');
    }

    /**
     * Test live connection to Cloudflare R2.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $bucket = SiteSetting::get('r2_bucket', config('filesystems.disks.r2.bucket', env('R2_BUCKET', '')));
            $endpoint = SiteSetting::get('r2_endpoint', config('filesystems.disks.r2.endpoint', env('R2_ENDPOINT', '')));
            $key = SiteSetting::get('r2_access_key_id', config('filesystems.disks.r2.key', env('R2_ACCESS_KEY_ID', '')));
            $secret = SiteSetting::get('r2_secret_access_key', config('filesystems.disks.r2.secret', env('R2_SECRET_ACCESS_KEY', '')));

            if (empty($bucket) || empty($endpoint) || empty($key) || empty($secret)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cloudflare R2 credentials are incomplete. Please provide Bucket, Endpoint, Access Key ID, and Secret Access Key.',
                    'latency_ms' => 0,
                ], 422);
            }

            // Real probe by checking disk existence / writing and deleting a temporary probe
            $disk = Storage::disk('r2');
            $testPath = 'system-health-probe.txt';
            $disk->put($testPath, 'IMGAI R2 Connection Test at ' . now()->toIso8601String());
            $exists = $disk->exists($testPath);
            $disk->delete($testPath);

            $latency = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'success' => true,
                'message' => "Cloudflare R2 connected successfully! Bucket: {$bucket}",
                'bucket' => $bucket,
                'latency_ms' => $latency,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (Exception $e) {
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            Log::error('R2 Connection Test failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Cloudflare R2 connection failed: ' . $e->getMessage(),
                'latency_ms' => $latency,
            ], 500);
        }
    }
}
