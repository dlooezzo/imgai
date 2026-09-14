<?php

use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\AiCreditPricingController;
use App\Http\Controllers\Admin\ApiMarketController;
use App\Http\Controllers\Admin\BrandingSeoController;
use App\Http\Controllers\Admin\CreditTransactionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GenerationController;
use App\Http\Controllers\Admin\HeroShowcaseController;
use App\Http\Controllers\Admin\ImageController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\ModelController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PricingPlanController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StorageController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SupabaseController;
use App\Http\Controllers\Admin\SystemStatusController;
use App\Http\Controllers\Admin\ToolArticleController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImageGeneratorController;
use App\Http\Controllers\ImageToVideoController;
use App\Http\Controllers\PaddleWebhookController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\PublicSeoController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\VideoGeneratorController;
use Illuminate\Support\Facades\Route;

// Direct root redirect to /tools per requirements
Route::get('/', [ToolController::class, 'rootRedirect'])->name('home');

// Tools Main Workspace (Publicly Accessible Views)
Route::prefix('tools')->name('tools.')->group(function () {
    // Studio Overview View
    Route::get('/overview', [ToolController::class, 'overview'])->name('overview');

    // Image Generator View
    Route::get('/', [ToolController::class, 'index'])->name('index');
    Route::get('/image-generator', [ToolController::class, 'index'])->name('image.index');

    // Video Generator View (Pure Text-to-Video)
    Route::get('/video-generator', [ToolController::class, 'videoIndex'])->name('video.index');

    // Image-to-Video Generator View
    Route::get('/image-to-video', [ToolController::class, 'imageToVideoIndex'])->name('image-to-video.index');

    // Image Generator Endpoints
    Route::prefix('image-generator')->name('image.')->group(function () {
        Route::post('/generate', [ImageGeneratorController::class, 'generate'])->middleware('tool.auth')->name('generate');
        Route::get('/status/{id}', [ImageGeneratorController::class, 'status'])->name('status');
        Route::post('/cancel/{id}', [ImageGeneratorController::class, 'cancel'])->middleware('tool.auth')->name('cancel');
        Route::get('/history', [ImageGeneratorController::class, 'history'])->name('history');
        Route::get('/download/{id}', [ImageGeneratorController::class, 'download'])->name('download');
        Route::delete('/delete/{id}', [ImageGeneratorController::class, 'destroy'])->middleware('tool.auth')->name('destroy');
    });

    // Video Generator Endpoints (Pure Text-to-Video)
    Route::prefix('video-generator')->name('video.')->group(function () {
        Route::post('/generate', [VideoGeneratorController::class, 'generate'])->middleware('tool.auth')->name('generate');
        Route::get('/status/{id}', [VideoGeneratorController::class, 'status'])->name('status');
        Route::post('/cancel/{id}', [VideoGeneratorController::class, 'cancel'])->middleware('tool.auth')->name('cancel');
        Route::get('/history', [VideoGeneratorController::class, 'history'])->name('history');
        Route::get('/download/{id}', [VideoGeneratorController::class, 'download'])->name('download');
        Route::delete('/delete/{id}', [VideoGeneratorController::class, 'destroy'])->middleware('tool.auth')->name('destroy');
    });

    // Image-to-Video Generator Endpoints
    Route::prefix('image-to-video')->name('image-to-video.')->group(function () {
        Route::post('/generate', [ImageToVideoController::class, 'generate'])->middleware('tool.auth')->name('generate');
        Route::get('/status/{id}', [ImageToVideoController::class, 'status'])->name('status');
        Route::post('/cancel/{id}', [ImageToVideoController::class, 'cancel'])->middleware('tool.auth')->name('cancel');
        Route::get('/history', [ImageToVideoController::class, 'history'])->name('history');
        Route::get('/download/{id}', [ImageToVideoController::class, 'download'])->name('download');
        Route::delete('/delete/{id}', [ImageToVideoController::class, 'destroy'])->middleware('tool.auth')->name('destroy');
    });
});

// Public Pricing & Subscription
Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');
Route::get('/welcome', [PricingController::class, 'welcome'])->name('welcome');

// Paddle Billing Webhooks
Route::post('/webhooks/paddle', [PaddleWebhookController::class, 'handle'])->name('webhooks.paddle');

// Dedicated Media Library (Requires Authentication)
Route::get('/library', [ProfileController::class, 'library'])->middleware('tool.auth')->name('library.index');

// User Dashboard & Profile (Requires Authentication)
Route::prefix('profile')->name('profile.')->middleware('tool.auth')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])->name('index');
    Route::post('/update', [ProfileController::class, 'update'])->name('update');
    Route::post('/password', [ProfileController::class, 'updatePassword'])->name('password');
    Route::post('/2fa', [ProfileController::class, 'toggle2FA'])->name('2fa');
    Route::post('/clear-history', [ProfileController::class, 'clearHistory'])->name('clear-history');
});

// Supabase Authentication Synchronization
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/sync-session', [AuthController::class, 'syncSession'])->name('sync');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// Admin Control Center Routes (Protected by admin.auth)
Route::prefix('admin')->name('admin.')->middleware(['admin.auth'])->group(function () {
    // Overview Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Hero Showcase Management
    Route::prefix('hero-showcase')->name('hero-showcase.')->group(function () {
        Route::get('/', [HeroShowcaseController::class, 'index'])->name('index');
        Route::post('/update', [HeroShowcaseController::class, 'update'])->name('update');
        Route::post('/scale', [HeroShowcaseController::class, 'updateScale'])->name('scale');
        Route::post('/reset', [HeroShowcaseController::class, 'reset'])->name('reset');
    });

    // Users Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/{id}', [UserController::class, 'show'])->name('show');
    });

    // Admin Role Management
    Route::prefix('admins')->name('admins.')->group(function () {
        Route::get('/', [AdminManagementController::class, 'index'])->name('index');
        Route::post('/promote', [AdminManagementController::class, 'promote'])->name('promote');
        Route::post('/demote', [AdminManagementController::class, 'demote'])->name('demote');
    });

    // Unified Generations
    Route::prefix('generations')->name('generations.')->group(function () {
        Route::get('/', [GenerationController::class, 'index'])->name('index');
        Route::delete('/{id}', [GenerationController::class, 'destroy'])->name('destroy');
    });

    // Images Gallery
    Route::prefix('images')->name('images.')->group(function () {
        Route::get('/', [ImageController::class, 'index'])->name('index');
        Route::delete('/{id}', [ImageController::class, 'destroy'])->name('destroy');
    });

    // Videos Gallery
    Route::prefix('videos')->name('videos.')->group(function () {
        Route::get('/', [VideoController::class, 'index'])->name('index');
        Route::delete('/{id}', [VideoController::class, 'destroy'])->name('destroy');
    });

    // Unified Site Branding & SEO Management
    Route::prefix('branding-seo')->name('branding-seo.')->group(function () {
        Route::get('/', [BrandingSeoController::class, 'index'])->name('index');
        Route::post('/update', [BrandingSeoController::class, 'update'])->name('update');
        Route::post('/upload-image', [BrandingSeoController::class, 'uploadImage'])->name('upload-image');
        Route::post('/clear-cache', [BrandingSeoController::class, 'clearCache'])->name('clear-cache');
    });

    // Backwards Compatibility & Smooth 301 Redirects for Legacy Branding & SEO Routes
    Route::prefix('branding')->name('branding.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.branding-seo.index', [], 301);
        })->name('index');
        Route::post('/update', [BrandingSeoController::class, 'update'])->name('update');
        Route::post('/upload-image', [BrandingSeoController::class, 'uploadImage'])->name('upload-image');
    });

    // Cloudflare R2 Storage Settings
    Route::prefix('storage')->name('storage.')->group(function () {
        Route::get('/', [StorageController::class, 'index'])->name('index');
        Route::post('/update', [StorageController::class, 'update'])->name('update');
        Route::post('/test', [StorageController::class, 'testConnection'])->name('test');
    });

    // AI Models Settings
    Route::prefix('models')->name('models.')->group(function () {
        Route::get('/', [ModelController::class, 'index'])->name('index');
        Route::post('/update', [ModelController::class, 'update'])->name('update');
        Route::post('/test', [ModelController::class, 'testConnection'])->name('test');
    });

    // API Market Settings
    Route::prefix('api-market')->name('api-market.')->group(function () {
        Route::get('/', [ApiMarketController::class, 'index'])->name('index');
        Route::post('/update', [ApiMarketController::class, 'update'])->name('update');
        Route::post('/test', [ApiMarketController::class, 'testConnection'])->name('test');
    });

    // Supabase Auth Settings
    Route::prefix('supabase')->name('supabase.')->group(function () {
        Route::get('/', [SupabaseController::class, 'index'])->name('index');
        Route::post('/update', [SupabaseController::class, 'update'])->name('update');
        Route::post('/test', [SupabaseController::class, 'testConnection'])->name('test');
    });

    // System Logs
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

    // System Status Live Probes
    Route::prefix('system-status')->name('system-status.')->group(function () {
        Route::get('/', [SystemStatusController::class, 'index'])->name('index');
        Route::get('/live', [SystemStatusController::class, 'liveCheck'])->name('live');
    });

    // CMS Pages Management
    Route::prefix('pages')->name('pages.')->group(function () {
        Route::get('/', [PageController::class, 'index'])->name('index');
        Route::get('/create', [PageController::class, 'create'])->name('create');
        Route::post('/', [PageController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [PageController::class, 'edit'])->name('edit');
        Route::put('/{id}', [PageController::class, 'update'])->name('update');
        Route::delete('/{id}', [PageController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [PageController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{id}/preview', [PageController::class, 'preview'])->name('preview');
        Route::post('/upload-image', [PageController::class, 'uploadImage'])->name('upload-image');
    });

    // SEO Management & Tool Articles
    Route::prefix('seo')->name('seo.')->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.branding-seo.index', [], 301);
        })->name('index');
        Route::post('/global', [BrandingSeoController::class, 'update'])->name('global');
        Route::post('/homepage', [BrandingSeoController::class, 'update'])->name('homepage');
        Route::post('/tools', [BrandingSeoController::class, 'update'])->name('tools');
        Route::post('/verification', [BrandingSeoController::class, 'update'])->name('verification');
        Route::post('/robots', [BrandingSeoController::class, 'update'])->name('robots');
        Route::post('/clear-cache', [BrandingSeoController::class, 'clearCache'])->name('clear-cache');
        Route::post('/upload-image', [BrandingSeoController::class, 'uploadImage'])->name('upload-image');

        // Tool Articles (SEO Content Editor for AI Tools)
        Route::prefix('articles')->name('articles.')->group(function () {
            Route::get('/', [ToolArticleController::class, 'index'])->name('index');
            Route::get('/{tool_key}/edit', [ToolArticleController::class, 'edit'])->name('edit');
            Route::post('/{tool_key}/update', [ToolArticleController::class, 'update'])->name('update');
            Route::post('/{tool_key}/toggle-publish', [ToolArticleController::class, 'togglePublish'])->name('toggle-publish');
            Route::get('/{tool_key}/preview', [ToolArticleController::class, 'preview'])->name('preview');
            Route::post('/{tool_key}/duplicate', [ToolArticleController::class, 'duplicate'])->name('duplicate');
            Route::delete('/{tool_key}/destroy', [ToolArticleController::class, 'destroy'])->name('destroy');
            Route::post('/upload-image', [ToolArticleController::class, 'uploadImage'])->name('upload-image');
        });
    });

    // Billing & Pricing Plans Management
    Route::prefix('pricing')->name('pricing.')->group(function () {
        Route::get('/', [PricingPlanController::class, 'index'])->name('index');
        Route::get('/create', [PricingPlanController::class, 'create'])->name('create');
        Route::post('/', [PricingPlanController::class, 'store'])->name('store');
        Route::get('/{plan}/edit', [PricingPlanController::class, 'edit'])->name('edit');
        Route::put('/{plan}', [PricingPlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [PricingPlanController::class, 'destroy'])->name('destroy');
        Route::patch('/{plan}/toggle', [PricingPlanController::class, 'toggle'])->name('toggle');
    });

    // Subscriptions Management
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
    });

    // Transactions History
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
    });

    // Credit Ledger
    Route::prefix('credits')->name('credits.')->group(function () {
        Route::get('/', [CreditTransactionController::class, 'index'])->name('index');
    });

    // AI Credit Pricing (Centralized, DB-backed)
    Route::prefix('credit-pricing')->name('credit-pricing.')->group(function () {
        Route::get('/', [AiCreditPricingController::class, 'index'])->name('index');
        Route::post('/update', [AiCreditPricingController::class, 'update'])->name('update');
    });

    // System Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/migrate', [SettingsController::class, 'runMigrations'])->name('settings.migrate');
});

// Seedance API Diagnostic Route (TEMPORARY - for production debugging)
Route::prefix('diagnostic')->name('diagnostic.')->group(function () {
    Route::get('/seedance-test', function () {
        $baseUrl = config('services.magicapi.seedance_video_base_url') ?: env('SEEDANCE_VIDEO_BASE_URL');
        $apiKey = config('services.magicapi.key') ?: env('API_MARKET_KEY');
        
        if (empty($baseUrl) || empty($apiKey) || str_contains($apiKey, 'YOUR_API_MARKET_KEY')) {
            return response()->json([
                'success' => false,
                'error' => 'Configuration missing',
                'details' => [
                    'base_url' => $baseUrl ? 'SET' : 'MISSING',
                    'api_key' => $apiKey && !str_contains($apiKey, 'YOUR_API_MARKET_KEY') ? 'SET (masked: '.substr($apiKey, 0, 8).'...)' : 'MISSING',
                ],
            ], 500);
        }
        
        // Test submit
        $parameters = [
            'prompt'         => 'A person walking slowly through a beautiful city street at sunset, cinematic camera movement.',
            'resolution'     => '480p',
            'ratio'          => '16:9',
            'duration'       => 5,
            'generate_audio' => false,
        ];
        
        $service = new \App\Services\AI\SeedanceVideoService();
        
        try {
            $submitResult = $service->createPrediction($parameters);
            
            if (empty($submitResult['prediction_id'])) {
                return response()->json([
                    'success' => false,
                    'stage' => 'submit',
                    'error' => 'No prediction_id returned',
                    'response' => $submitResult,
                ], 500);
            }
            
            $jobId = $submitResult['prediction_id'];
            
            // Poll status a few times
            $polls = [];
            for ($i = 1; $i <= 6; $i++) {  // 6 polls x 10s = 60s max
                $statusResult = $service->getPredictionStatus($jobId);
                $polls[] = [
                    'poll' => $i,
                    'http_status' => $statusResult['http_status'],
                    'status' => $statusResult['status'],
                    'output_url' => $statusResult['output'],
                    'error' => $statusResult['error'],
                    'raw_status' => $statusResult['raw']['status'] ?? 'missing',
                    'raw_output' => $statusResult['raw']['output'] ?? 'missing',
                ];
                
                if (in_array($statusResult['status'], ['failed', 'error'], true)) {
                    break;
                }
                if ($statusResult['status'] === 'succeeded' && !empty($statusResult['output'])) {
                    break;
                }
                if ($i < 6) sleep(10);
            }
            
            return response()->json([
                'success' => true,
                'submit' => $submitResult,
                'polls' => $polls,
                'final_status' => end($polls)['status'] ?? 'unknown',
                'video_url_received' => !empty(end($polls)['output_url']),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    })->name('seedance-test');
});

// Technical SEO Endpoints
Route::get('/sitemap.xml', [PublicSeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [PublicSeoController::class, 'robots'])->name('robots');

// Public Dynamic CMS Pages (Resolved when no static route matches)
Route::get('/{slug}', [PublicPageController::class, 'show'])
    ->name('pages.show')
    ->where('slug', '[a-zA-Z0-9_-]+');
