<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImageGeneratorController;
use App\Http\Controllers\ImageToVideoController;
use App\Http\Controllers\ProfileController;
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
Route::get('/pricing', [\App\Http\Controllers\PricingController::class, 'index'])->name('pricing');
Route::get('/welcome', [\App\Http\Controllers\PricingController::class, 'welcome'])->name('welcome');

// Paddle Billing Webhooks
Route::post('/webhooks/paddle', [\App\Http\Controllers\PaddleWebhookController::class, 'handle'])->name('webhooks.paddle');


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
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    // Hero Showcase Management
    Route::prefix('hero-showcase')->name('hero-showcase.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\HeroShowcaseController::class, 'index'])->name('index');
        Route::post('/update', [\App\Http\Controllers\Admin\HeroShowcaseController::class, 'update'])->name('update');
        Route::post('/scale', [\App\Http\Controllers\Admin\HeroShowcaseController::class, 'updateScale'])->name('scale');
        Route::post('/reset', [\App\Http\Controllers\Admin\HeroShowcaseController::class, 'reset'])->name('reset');
    });

    // Users Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('show');
    });

    // Admin Role Management
    Route::prefix('admins')->name('admins.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\AdminManagementController::class, 'index'])->name('index');
        Route::post('/promote', [\App\Http\Controllers\Admin\AdminManagementController::class, 'promote'])->name('promote');
        Route::post('/demote', [\App\Http\Controllers\Admin\AdminManagementController::class, 'demote'])->name('demote');
    });

    // Unified Generations
    Route::prefix('generations')->name('generations.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\GenerationController::class, 'index'])->name('index');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\GenerationController::class, 'destroy'])->name('destroy');
    });

    // Images Gallery
    Route::prefix('images')->name('images.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ImageController::class, 'index'])->name('index');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\ImageController::class, 'destroy'])->name('destroy');
    });

    // Videos Gallery
    Route::prefix('videos')->name('videos.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\VideoController::class, 'index'])->name('index');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\VideoController::class, 'destroy'])->name('destroy');
    });

    // Site Branding & Identity
    Route::prefix('branding')->name('branding.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\BrandingController::class, 'index'])->name('index');
        Route::post('/update', [\App\Http\Controllers\Admin\BrandingController::class, 'update'])->name('update');
        Route::post('/upload-image', [\App\Http\Controllers\Admin\BrandingController::class, 'uploadImage'])->name('upload-image');
    });

    // Cloudflare R2 Storage Settings
    Route::prefix('storage')->name('storage.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\StorageController::class, 'index'])->name('index');
        Route::post('/update', [\App\Http\Controllers\Admin\StorageController::class, 'update'])->name('update');
        Route::post('/test', [\App\Http\Controllers\Admin\StorageController::class, 'testConnection'])->name('test');
    });

    // AI Models Settings
    Route::prefix('models')->name('models.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ModelController::class, 'index'])->name('index');
        Route::post('/update', [\App\Http\Controllers\Admin\ModelController::class, 'update'])->name('update');
        Route::post('/test', [\App\Http\Controllers\Admin\ModelController::class, 'testConnection'])->name('test');
    });

    // API Market Settings
    Route::prefix('api-market')->name('api-market.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ApiMarketController::class, 'index'])->name('index');
        Route::post('/update', [\App\Http\Controllers\Admin\ApiMarketController::class, 'update'])->name('update');
        Route::post('/test', [\App\Http\Controllers\Admin\ApiMarketController::class, 'testConnection'])->name('test');
    });

    // Supabase Auth Settings
    Route::prefix('supabase')->name('supabase.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SupabaseController::class, 'index'])->name('index');
        Route::post('/update', [\App\Http\Controllers\Admin\SupabaseController::class, 'update'])->name('update');
        Route::post('/test', [\App\Http\Controllers\Admin\SupabaseController::class, 'testConnection'])->name('test');
    });

    // System Logs
    Route::get('/logs', [\App\Http\Controllers\Admin\LogController::class, 'index'])->name('logs.index');

    // System Status Live Probes
    Route::prefix('system-status')->name('system-status.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SystemStatusController::class, 'index'])->name('index');
        Route::get('/live', [\App\Http\Controllers\Admin\SystemStatusController::class, 'liveCheck'])->name('live');
    });

    // CMS Pages Management
    Route::prefix('pages')->name('pages.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PageController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\PageController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\PageController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [\App\Http\Controllers\Admin\PageController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\Admin\PageController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\PageController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [\App\Http\Controllers\Admin\PageController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{id}/preview', [\App\Http\Controllers\Admin\PageController::class, 'preview'])->name('preview');
        Route::post('/upload-image', [\App\Http\Controllers\Admin\PageController::class, 'uploadImage'])->name('upload-image');
    });

    // SEO Management
    Route::prefix('seo')->name('seo.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SeoController::class, 'index'])->name('index');
        Route::post('/global', [\App\Http\Controllers\Admin\SeoController::class, 'updateGlobal'])->name('global');
        Route::post('/homepage', [\App\Http\Controllers\Admin\SeoController::class, 'updateHomepage'])->name('homepage');
        Route::post('/tools', [\App\Http\Controllers\Admin\SeoController::class, 'updateTools'])->name('tools');
        Route::post('/verification', [\App\Http\Controllers\Admin\SeoController::class, 'updateVerification'])->name('verification');
        Route::post('/robots', [\App\Http\Controllers\Admin\SeoController::class, 'updateRobotsTxt'])->name('robots');
        Route::post('/clear-cache', [\App\Http\Controllers\Admin\SeoController::class, 'clearCache'])->name('clear-cache');
        Route::post('/upload-image', [\App\Http\Controllers\Admin\SeoController::class, 'uploadImage'])->name('upload-image');

        // Tool Articles (SEO Content Editor for AI Tools)
        Route::prefix('articles')->name('articles.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ToolArticleController::class, 'index'])->name('index');
            Route::get('/{tool_key}/edit', [\App\Http\Controllers\Admin\ToolArticleController::class, 'edit'])->name('edit');
            Route::post('/{tool_key}/update', [\App\Http\Controllers\Admin\ToolArticleController::class, 'update'])->name('update');
            Route::post('/{tool_key}/toggle-publish', [\App\Http\Controllers\Admin\ToolArticleController::class, 'togglePublish'])->name('toggle-publish');
            Route::get('/{tool_key}/preview', [\App\Http\Controllers\Admin\ToolArticleController::class, 'preview'])->name('preview');
            Route::post('/{tool_key}/duplicate', [\App\Http\Controllers\Admin\ToolArticleController::class, 'duplicate'])->name('duplicate');
            Route::delete('/{tool_key}/destroy', [\App\Http\Controllers\Admin\ToolArticleController::class, 'destroy'])->name('destroy');
            Route::post('/upload-image', [\App\Http\Controllers\Admin\ToolArticleController::class, 'uploadImage'])->name('upload-image');
        });
    });

    // Billing & Pricing Plans Management
    Route::prefix('pricing')->name('pricing.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PricingPlanController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\PricingPlanController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\PricingPlanController::class, 'store'])->name('store');
        Route::get('/{plan}/edit', [\App\Http\Controllers\Admin\PricingPlanController::class, 'edit'])->name('edit');
        Route::put('/{plan}', [\App\Http\Controllers\Admin\PricingPlanController::class, 'update'])->name('update');
        Route::delete('/{plan}', [\App\Http\Controllers\Admin\PricingPlanController::class, 'destroy'])->name('destroy');
        Route::patch('/{plan}/toggle', [\App\Http\Controllers\Admin\PricingPlanController::class, 'toggle'])->name('toggle');
    });

    // Subscriptions Management
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('index');
    });

    // Transactions History
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TransactionController::class, 'index'])->name('index');
    });

    // Credit Ledger
    Route::prefix('credits')->name('credits.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\CreditTransactionController::class, 'index'])->name('index');
    });


    // System Settings
    Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/migrate', [\App\Http\Controllers\Admin\SettingsController::class, 'runMigrations'])->name('settings.migrate');
});

// Technical SEO Endpoints
Route::get('/sitemap.xml', [\App\Http\Controllers\PublicSeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [\App\Http\Controllers\PublicSeoController::class, 'robots'])->name('robots');

// Public Dynamic CMS Pages (Resolved when no static route matches)
Route::get('/{slug}', [\App\Http\Controllers\PublicPageController::class, 'show'])
    ->name('pages.show')
    ->where('slug', '[a-zA-Z0-9_-]+');



