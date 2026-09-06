<?php

namespace App\Providers;

use App\Services\AI\Contracts\ImageGenerationInterface;
use App\Services\AI\Contracts\VideoGenerationInterface;
use App\Services\AI\MagicApiImageService;
use App\Services\AI\MagicApiVideoService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ImageGenerationInterface::class, function ($app) {
            return new MagicApiImageService();
        });

        $this->app->singleton(VideoGenerationInterface::class, function ($app) {
            return new MagicApiVideoService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Dynamically override runtime configurations from persistent database SiteSettings
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('site_settings')) {
                // Canonical Site URL from Database
                if ($customSiteUrl = \App\Models\SiteSetting::get('site_url')) {
                    config(['app.url' => rtrim($customSiteUrl, '/')]);
                }

                // Site Branding
                if ($siteTitle = \App\Models\SiteSetting::get('site_title')) {
                    config(['app.name' => $siteTitle]);
                }

                // Cloudflare R2 Storage Overrides
                if ($bucket = \App\Models\SiteSetting::get('r2_bucket')) {
                    config(['filesystems.disks.r2.bucket' => $bucket]);
                }
                if ($endpoint = \App\Models\SiteSetting::get('r2_endpoint')) {
                    config(['filesystems.disks.r2.endpoint' => $endpoint]);
                }
                if ($r2Url = \App\Models\SiteSetting::get('r2_public_url')) {
                    config(['filesystems.disks.r2.url' => $r2Url]);
                }
                if ($r2Region = \App\Models\SiteSetting::get('r2_region')) {
                    config(['filesystems.disks.r2.region' => $r2Region]);
                }
                if ($r2Key = \App\Models\SiteSetting::get('r2_access_key_id')) {
                    config(['filesystems.disks.r2.key' => $r2Key]);
                }
                if ($r2Secret = \App\Models\SiteSetting::get('r2_secret_access_key')) {
                    config(['filesystems.disks.r2.secret' => $r2Secret]);
                }

                // API Market & AI Model Gateways
                if ($apiMarketKey = \App\Models\SiteSetting::get('api_market_key')) {
                    config(['services.magicapi.key' => $apiMarketKey]);
                }
                if ($imgUrl = \App\Models\SiteSetting::get('ai_model_image_url', \App\Models\SiteSetting::get('api_market_base_url'))) {
                    config(['services.magicapi.base_url' => $imgUrl]);
                }
                if ($imgVer = \App\Models\SiteSetting::get('ai_model_image_version')) {
                    config(['services.magicapi.version' => $imgVer]);
                }
                if ($vidUrl = \App\Models\SiteSetting::get('ai_model_video_url', \App\Models\SiteSetting::get('api_market_video_base_url'))) {
                    config(['services.magicapi.video_base_url' => $vidUrl]);
                }
                if ($vidVer = \App\Models\SiteSetting::get('ai_model_video_version')) {
                    config(['services.magicapi.video_version' => $vidVer]);
                }
                if ($i2vUrl = \App\Models\SiteSetting::get('ai_model_i2v_url', \App\Models\SiteSetting::get('api_market_i2v_base_url'))) {
                    config(['services.magicapi.image_to_video_base_url' => $i2vUrl]);
                }
                if ($i2vVer = \App\Models\SiteSetting::get('ai_model_i2v_version')) {
                    config(['services.magicapi.image_to_video_version' => $i2vVer]);
                }

                // Supabase Authentication
                if ($sbUrl = \App\Models\SiteSetting::get('supabase_url')) {
                    config(['services.supabase.url' => $sbUrl]);
                }
                if ($sbAnon = \App\Models\SiteSetting::get('supabase_anon_key')) {
                    config(['services.supabase.anon_key' => $sbAnon]);
                }
                if ($sbService = \App\Models\SiteSetting::get('supabase_service_role_key')) {
                    config(['services.supabase.service_key' => $sbService]);
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore during initial boot / migration phases
        }

        // 2. Canonical Application Root URL Configuration
        // Enforces the verified application URL across routes, assets, and emails,
        // and provides strict protection against arbitrary HTTP Host Header injection attacks.
        $canonicalAppUrl = config('app.url');
        if ($canonicalAppUrl && !in_array($canonicalAppUrl, ['http://localhost', 'https://localhost', 'http://127.0.0.1'])) {
            URL::forceRootUrl(rtrim($canonicalAppUrl, '/'));
            if (str_starts_with($canonicalAppUrl, 'https://')) {
                URL::forceScheme('https');
            } elseif (str_starts_with($canonicalAppUrl, 'http://')) {
                URL::forceScheme('http');
            }
        } elseif (!$this->app->runningInConsole() && request()) {
            // Fallback only when not configured in .env or database
            $scheme = request()->isSecure() ? 'https' : 'http';
            $host = preg_replace('/[^a-zA-Z0-9.:-]/', '', request()->getHttpHost());
            URL::forceRootUrl($scheme . '://' . $host);
        }
    }
}
