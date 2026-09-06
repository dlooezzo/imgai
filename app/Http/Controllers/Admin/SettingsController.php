<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class SettingsController extends Controller
{
    /**
     * Display the System Settings page organized by category.
     */
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'general');

        $settings = [
            'general' => [
                'App Name' => config('app.name', 'IMGAI Studio'),
                'App Environment' => config('app.env', 'production'),
                'App Debug' => config('app.debug') ? 'Enabled (true)' : 'Disabled (false)',
                'App URL' => config('app.url', 'http://localhost'),
                'Timezone' => config('app.timezone', 'UTC'),
                'Locale' => config('app.locale', 'en'),
                'Laravel Framework Version' => app()->version(),
                'PHP Runtime Version' => PHP_VERSION,
            ],
            'database' => [
                'Connection Driver' => config('database.default'),
                'Database Name' => config('database.connections.' . config('database.default') . '.database'),
                'Credit Transactions Table' => Schema::hasTable('credit_transactions') ? 'Active (Ready)' : 'Missing',
                'Paddle Transactions Table' => Schema::hasTable('transactions') ? 'Active (Ready)' : 'Missing',
                'Subscriptions Table' => Schema::hasTable('subscriptions') ? 'Active (Ready)' : 'Missing',
                'Pricing Plans Table' => Schema::hasTable('pricing_plans') ? 'Active (Ready)' : 'Missing',
            ],
            'authentication' => [
                'Auth Provider' => 'Supabase Auth (Cloud)',
                'Supabase Project URL' => config('services.supabase.url', env('SUPABASE_URL', 'Not set')),
                'Supabase Anon Key' => !empty(env('SUPABASE_ANON_KEY')) ? 'Configured (Masked)' : 'Missing',
                'Supabase Service Role' => !empty(env('SUPABASE_SERVICE_ROLE_KEY')) ? 'Configured (Masked)' : 'Missing',
                'Default User Role' => 'user (Normal privileges)',
                'Session Driver' => config('session.driver', 'database'),
                'Session Lifetime' => config('session.lifetime', 120) . ' minutes',
            ],
            'ai' => [
                'API Market Host' => 'prod.api.market',
                'API Market Key' => !empty(env('API_MARKET_KEY')) ? 'Configured (Masked)' : 'Missing',
                'Cinematic Image Model' => config('services.magicapi.version', env('MAGICAPI_MODEL_VERSION', '16e15e913fcc71c1a5defb335ea84739f99731fa1ee17995117c7d9adc6d176c')),
                'Hunyuan Video Model' => config('services.magicapi.video_version', env('MAGICAPI_VIDEO_VERSION', '6c9132aee14409cd6568d030453f1ba50f5f3412b844fe67f78a9eb62d55664f')),
                'Wan 2.2 Image-to-Video Model' => config('services.magicapi.image_to_video_version', env('MAGICAPI_IMAGE_TO_VIDEO_VERSION', 'c92ab4265c9b3b5ea9ac9a87df839ebfd662ee3a820d62c21305bf6501a73fe1')),
                'Max Image Resolution' => '2 Megapixels',
                'Default Video Duration / Frames' => '81 Frames @ 24 FPS',
            ],
            'storage' => [
                'Default Disk' => config('filesystems.default', 'local'),
                'Cloudflare R2 Bucket' => config('filesystems.disks.r2.bucket', env('R2_BUCKET', 'Not set')),
                'Cloudflare R2 Endpoint' => config('filesystems.disks.r2.endpoint', env('R2_ENDPOINT', 'Not set')),
                'Cloudflare R2 Region' => config('filesystems.disks.r2.region', env('R2_REGION', 'auto')),
                'Cloudflare R2 Public CDN URL' => config('filesystems.disks.r2.url', env('R2_PUBLIC_URL', 'Not set')),
                'R2 Credentials Status' => (!empty(env('R2_ACCESS_KEY_ID')) && !empty(env('R2_SECRET_ACCESS_KEY'))) ? 'Configured' : 'Missing',
                'Local Public Storage' => storage_path('app/public'),
            ],
            'security' => [
                'Admin Authorization Middleware' => 'App\Http\Middleware\EnsureAdmin',
                'Tool Authentication Middleware' => 'App\Http\Middleware\EnsureToolAuthenticated',
                'Server-Side Role Verification' => 'MySQL users.role === "admin"',
                'Public Registration Role Policy' => 'Forced default "user" role',
                'Trust Proxies' => 'Configured (Wildcard *)',
                'HTTPS / SSL' => request()->isSecure() ? 'Active (HTTPS)' : 'Standard (HTTP)',
            ],
        ];

        return view('admin.settings.index', [
            'tab' => $tab,
            'settings' => $settings,
        ]);
    }

    /**
     * Run pending database migrations safely with force.
     */
    public function runMigrations(Request $request)
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            return back()->with('success', 'Database migrations executed successfully: ' . (trim($output) ?: 'All tables up to date.'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Migration failed: ' . $e->getMessage());
        }
    }
}
