<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Services\Seo\SeoService;
use App\Services\Storage\R2StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BrandingSeoController extends Controller
{
    protected SeoService $seoService;

    public function __construct(SeoService $seoService)
    {
        $this->seoService = $seoService;
    }

    /**
     * Display unified Branding & SEO Management view.
     */
    public function index(Request $request)
    {
        $seoSettings = $this->seoService->getGlobalSettings();
        $auditReport = $this->seoService->getSeoAudit();

        // Core Branding & Identity
        $siteTitle = SiteSetting::get('site_title', config('app.name', 'IMGAI'));

        $settings = [
            // 1. Brand Identity
            'site_title' => $siteTitle,
            'site_tagline' => SiteSetting::get('site_tagline', 'AI Creative Studio'),
            'site_logo' => SiteSetting::get('site_logo', ''),
            'site_logo_icon' => SiteSetting::get('site_logo_icon', ''),
            'site_favicon' => SiteSetting::get('site_favicon', ''),
            'site_footer_text' => SiteSetting::get('site_footer_text', '© ' . date('Y') . ' IMGAI. All rights reserved.'),

            // 2. Homepage SEO
            'seo_homepage_title' => SiteSetting::get('seo_homepage_title', $seoSettings['homepage_title']),
            'seo_homepage_description' => SiteSetting::get('seo_homepage_description', $seoSettings['homepage_description']),
            'seo_homepage_og_image' => SiteSetting::get('seo_homepage_og_image', $seoSettings['homepage_og_image']),
            'seo_homepage_robots' => SiteSetting::get('seo_homepage_robots', $seoSettings['homepage_robots']),

            // 3. Global SEO
            'seo_default_title' => SiteSetting::get('seo_default_title', $seoSettings['default_title']),
            'seo_site_name' => SiteSetting::get('seo_site_name', $seoSettings['site_name']),
            'seo_title_format' => SiteSetting::get('seo_title_format', $seoSettings['title_format']),
            'seo_default_description' => SiteSetting::get('seo_default_description', $seoSettings['default_description']),
            'seo_meta_keywords' => SiteSetting::get('seo_meta_keywords', $seoSettings['meta_keywords']),
            'seo_default_robots' => SiteSetting::get('seo_default_robots', $seoSettings['default_robots']),
            'seo_default_og_image' => SiteSetting::get('seo_default_og_image', $seoSettings['default_og_image']),
            'seo_default_twitter_image' => SiteSetting::get('seo_default_twitter_image', $seoSettings['default_twitter_image']),

            // 4. AI Tools SEO
            'seo_tool_image_title' => SiteSetting::get('seo_tool_image_title', $seoSettings['tool_image_title']),
            'seo_tool_image_description' => SiteSetting::get('seo_tool_image_description', $seoSettings['tool_image_description']),
            'seo_tool_image_og_image' => SiteSetting::get('seo_tool_image_og_image', $seoSettings['tool_image_og_image']),

            'seo_tool_video_title' => SiteSetting::get('seo_tool_video_title', $seoSettings['tool_video_title']),
            'seo_tool_video_description' => SiteSetting::get('seo_tool_video_description', $seoSettings['tool_video_description']),
            'seo_tool_video_og_image' => SiteSetting::get('seo_tool_video_og_image', $seoSettings['tool_video_og_image']),

            'seo_tool_i2v_title' => SiteSetting::get('seo_tool_i2v_title', $seoSettings['tool_i2v_title']),
            'seo_tool_i2v_description' => SiteSetting::get('seo_tool_i2v_description', $seoSettings['tool_i2v_description']),
            'seo_tool_i2v_og_image' => SiteSetting::get('seo_tool_i2v_og_image', $seoSettings['tool_i2v_og_image']),

            // 5. Search Engine Verification
            'seo_google_verification' => SiteSetting::get('seo_google_verification', $seoSettings['google_verification']),
            'seo_bing_verification' => SiteSetting::get('seo_bing_verification', $seoSettings['bing_verification']),

            // 6. Sitemap & Robots
            'seo_robots_txt_custom' => SiteSetting::get('seo_robots_txt_custom', $seoSettings['robots_txt_custom']),
        ];

        // Technical SEO Stats
        $publishedCount = Schema::hasTable('pages') ? Page::where('status', 'published')->count() : 0;
        $noindexCount = Schema::hasTable('pages') ? Page::where('robots_directive', 'like', '%noindex%')->count() : 0;
        $indexableCount = $publishedCount - $noindexCount + 4; // 4 core indexable tools + homepage

        $stats = [
            'total_published' => $publishedCount,
            'total_indexable' => max(0, $indexableCount),
            'total_noindex' => $noindexCount,
            'sitemap_status' => 'Dynamic & Active',
            'robots_status' => !empty($settings['seo_robots_txt_custom']) ? 'Custom Configured' : 'Auto-Optimized',
            'google_verified' => !empty($settings['seo_google_verification']),
            'bing_verified' => !empty($settings['seo_bing_verification']),
        ];

        return view('admin.branding_seo.index', [
            'settings' => $settings,
            'stats' => $stats,
            'auditReport' => $auditReport,
        ]);
    }

    /**
     * Update all Branding & SEO settings cleanly in a single unified action.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            // 1. Brand Identity
            'site_title' => 'required|string|max:100',
            'site_tagline' => 'nullable|string|max:200',
            'site_logo' => 'nullable|string|max:1000',
            'site_logo_icon' => 'nullable|string|max:1000',
            'site_favicon' => 'nullable|string|max:1000',
            'site_footer_text' => 'nullable|string|max:500',

            // 2. Homepage SEO
            'seo_homepage_title' => 'nullable|string|max:200',
            'seo_homepage_description' => 'nullable|string|max:500',
            'seo_homepage_og_image' => 'nullable|string|max:1000',
            'seo_homepage_robots' => 'nullable|string|max:50',

            // 3. Global SEO
            'seo_default_title' => 'nullable|string|max:200',
            'seo_site_name' => 'nullable|string|max:150',
            'seo_title_format' => 'nullable|string|max:100',
            'seo_default_description' => 'nullable|string|max:500',
            'seo_meta_keywords' => 'nullable|string|max:500',
            'seo_default_robots' => 'nullable|string|max:50',
            'seo_default_og_image' => 'nullable|string|max:1000',
            'seo_default_twitter_image' => 'nullable|string|max:1000',

            // 4. AI Tools SEO
            'seo_tool_image_title' => 'nullable|string|max:200',
            'seo_tool_image_description' => 'nullable|string|max:500',
            'seo_tool_image_og_image' => 'nullable|string|max:1000',

            'seo_tool_video_title' => 'nullable|string|max:200',
            'seo_tool_video_description' => 'nullable|string|max:500',
            'seo_tool_video_og_image' => 'nullable|string|max:1000',

            'seo_tool_i2v_title' => 'nullable|string|max:200',
            'seo_tool_i2v_description' => 'nullable|string|max:500',
            'seo_tool_i2v_og_image' => 'nullable|string|max:1000',

            // 5. Search Engine Verification
            'seo_google_verification' => 'nullable|string|max:255',
            'seo_bing_verification' => 'nullable|string|max:255',

            // 6. Sitemap & Robots
            'seo_robots_txt_custom' => 'nullable|string|max:10000',
        ]);

        // 1. Save Brand Identity
        SiteSetting::set('site_title', trim($validated['site_title']));
        SiteSetting::set('site_tagline', trim($validated['site_tagline'] ?? ''));
        SiteSetting::set('site_logo', trim($validated['site_logo'] ?? ''));
        SiteSetting::set('site_logo_icon', trim($validated['site_logo_icon'] ?? ''));
        SiteSetting::set('site_favicon', trim($validated['site_favicon'] ?? ''));
        SiteSetting::set('site_footer_text', trim($validated['site_footer_text'] ?? ''));

        // 2. Save Homepage SEO
        SiteSetting::set('seo_homepage_title', trim($validated['seo_homepage_title'] ?? ''));
        SiteSetting::set('seo_homepage_description', trim($validated['seo_homepage_description'] ?? ''));
        SiteSetting::set('seo_homepage_og_image', trim($validated['seo_homepage_og_image'] ?? ''));
        SiteSetting::set('seo_homepage_robots', trim($validated['seo_homepage_robots'] ?? 'index, follow'));

        // 3. Save Global SEO
        SiteSetting::set('seo_default_title', trim($validated['seo_default_title'] ?? ''));
        SiteSetting::set('seo_site_name', trim($validated['seo_site_name'] ?? ''));
        SiteSetting::set('seo_title_format', trim($validated['seo_title_format'] ?? '') ?: '%title% | %site_name%');
        SiteSetting::set('seo_default_description', trim($validated['seo_default_description'] ?? ''));
        SiteSetting::set('seo_meta_keywords', trim($validated['seo_meta_keywords'] ?? ''));
        SiteSetting::set('seo_default_robots', trim($validated['seo_default_robots'] ?? 'index, follow'));
        SiteSetting::set('seo_default_og_image', trim($validated['seo_default_og_image'] ?? ''));
        SiteSetting::set('seo_default_twitter_image', trim($validated['seo_default_twitter_image'] ?? ''));

        // 4. Save AI Tools SEO
        SiteSetting::set('seo_tool_image_title', trim($validated['seo_tool_image_title'] ?? ''));
        SiteSetting::set('seo_tool_image_description', trim($validated['seo_tool_image_description'] ?? ''));
        SiteSetting::set('seo_tool_image_og_image', trim($validated['seo_tool_image_og_image'] ?? ''));

        SiteSetting::set('seo_tool_video_title', trim($validated['seo_tool_video_title'] ?? ''));
        SiteSetting::set('seo_tool_video_description', trim($validated['seo_tool_video_description'] ?? ''));
        SiteSetting::set('seo_tool_video_og_image', trim($validated['seo_tool_video_og_image'] ?? ''));

        SiteSetting::set('seo_tool_i2v_title', trim($validated['seo_tool_i2v_title'] ?? ''));
        SiteSetting::set('seo_tool_i2v_description', trim($validated['seo_tool_i2v_description'] ?? ''));
        SiteSetting::set('seo_tool_i2v_og_image', trim($validated['seo_tool_i2v_og_image'] ?? ''));

        // 5. Save Search Engine Verification
        SiteSetting::set('seo_google_verification', trim($validated['seo_google_verification'] ?? ''));
        SiteSetting::set('seo_bing_verification', trim($validated['seo_bing_verification'] ?? ''));

        // 6. Save Robots.txt
        if (array_key_exists('seo_robots_txt_custom', $validated)) {
            SiteSetting::set('seo_robots_txt_custom', $validated['seo_robots_txt_custom'] ?? '');
        }

        // Apply app name in runtime
        config(['app.name' => trim($validated['site_title'])]);

        // Invalidate SEO cache immediately
        try {
            $this->seoService->clearCache();
        } catch (\Throwable $e) {
            Log::warning('Failed to clear SEO cache on Branding & SEO update: ' . $e->getMessage());
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'All Branding & SEO settings have been successfully updated!',
            ]);
        }

        $activeTab = $request->input('active_tab', 'identity');

        return redirect()->route('admin.branding-seo.index', ['tab' => $activeTab])
            ->with('success', 'All Branding & SEO settings successfully updated!');
    }

    /**
     * Upload an image asset (Logo, Favicon, Social Share OG, Tool Card).
     */
    public function uploadImage(Request $request, R2StorageService $r2Service): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,webp,gif,svg,ico|max:10240',
        ]);

        try {
            $file = $request->file('image');
            $uploadResult = $r2Service->uploadBrandingAsset($file);

            return response()->json([
                'success' => true,
                'url' => $uploadResult['url'],
                'filename' => $uploadResult['filename'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Clear SEO Cache.
     */
    public function clearCache(Request $request): JsonResponse|RedirectResponse
    {
        $this->seoService->clearCache();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'SEO cache and XML sitemap successfully invalidated.',
            ]);
        }

        return redirect()->back()->with('success', 'SEO cache and XML sitemap successfully invalidated.');
    }
}
