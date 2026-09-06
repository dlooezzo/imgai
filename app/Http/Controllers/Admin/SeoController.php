<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Services\Seo\SeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SeoController extends Controller
{
    protected SeoService $seoService;

    public function __construct(SeoService $seoService)
    {
        $this->seoService = $seoService;
    }

    /**
     * Display SEO Management Dashboard.
     */
    public function index(Request $request)
    {
        $settings = $this->seoService->getGlobalSettings();
        $auditReport = $this->seoService->getSeoAudit();

        // Calculate real statistical data
        $publishedCount = Schema::hasTable('pages') ? Page::where('status', 'published')->count() : 0;
        $noindexCount = Schema::hasTable('pages') ? Page::where('robots_directive', 'like', '%noindex%')->count() : 0;
        $indexableCount = $publishedCount - $noindexCount + 4; // 4 core indexable tools + homepage

        $missingTitle = Schema::hasTable('pages') ? Page::where('status', 'published')->where(function ($q) {
            $q->whereNull('meta_title')->orWhere('meta_title', '');
        })->count() : 0;

        $missingDesc = Schema::hasTable('pages') ? Page::where('status', 'published')->where(function ($q) {
            $q->whereNull('meta_description')->orWhere('meta_description', '');
        })->count() : 0;

        $missingCanonical = Schema::hasTable('pages') ? Page::where('status', 'published')->where(function ($q) {
            $q->whereNull('canonical_url')->orWhere('canonical_url', '');
        })->count() : 0;

        $missingOgImage = Schema::hasTable('pages') ? Page::where('status', 'published')->where(function ($q) {
            $q->whereNull('og_image')->orWhere('og_image', '');
        })->count() : 0;

        $stats = [
            'total_published' => $publishedCount,
            'total_indexable' => max(0, $indexableCount),
            'total_noindex' => $noindexCount,
            'missing_title' => $missingTitle,
            'missing_desc' => $missingDesc,
            'missing_canonical' => $missingCanonical,
            'missing_og_image' => $missingOgImage,
            'sitemap_status' => 'Dynamic & Active',
            'robots_status' => !empty($settings['robots_txt_custom']) ? 'Custom Configured' : 'Auto-Optimized',
            'google_verified' => !empty($settings['google_verification']),
            'bing_verified' => !empty($settings['bing_verification']),
        ];

        return view('admin.seo.index', [
            'settings' => $settings,
            'stats' => $stats,
            'auditReport' => $auditReport,
        ]);
    }

    /**
     * Update Global SEO Settings.
     */
    public function updateGlobal(Request $request): RedirectResponse
    {
        $request->validate([
            'site_name' => 'required|string|max:100',
            'title_format' => 'nullable|string|max:100',
            'default_title' => 'required|string|max:150',
            'default_description' => 'required|string|max:300',
            'default_og_image' => 'nullable|url|max:255',
            'default_twitter_image' => 'nullable|url|max:255',
            'default_robots' => 'required|in:index, follow,noindex, follow,index, nofollow,noindex, nofollow',
        ]);

        SiteSetting::set('seo_site_name', $request->input('site_name'));
        SiteSetting::set('seo_title_format', $request->input('title_format') ?: '%title% | %site_name%');
        SiteSetting::set('seo_default_title', $request->input('default_title'));
        SiteSetting::set('seo_default_description', $request->input('default_description'));
        SiteSetting::set('seo_default_og_image', $request->input('default_og_image'));
        SiteSetting::set('seo_default_twitter_image', $request->input('default_twitter_image'));
        SiteSetting::set('seo_default_robots', $request->input('default_robots'));

        $this->seoService->clearCache();

        return redirect()->route('admin.seo.index', ['tab' => 'global'])
            ->with('success', 'Global SEO identity and default metadata updated successfully.');
    }

    /**
     * Update Homepage SEO settings.
     */
    public function updateHomepage(Request $request): RedirectResponse
    {
        $request->validate([
            'homepage_title' => 'required|string|max:150',
            'homepage_description' => 'required|string|max:300',
            'homepage_og_image' => 'nullable|url|max:255',
            'homepage_robots' => 'required|in:index, follow,noindex, follow,index, nofollow,noindex, nofollow',
        ]);

        SiteSetting::set('seo_homepage_title', $request->input('homepage_title'));
        SiteSetting::set('seo_homepage_description', $request->input('homepage_description'));
        SiteSetting::set('seo_homepage_og_image', $request->input('homepage_og_image'));
        SiteSetting::set('seo_homepage_robots', $request->input('homepage_robots'));

        $this->seoService->clearCache();

        return redirect()->route('admin.seo.index', ['tab' => 'homepage'])
            ->with('success', 'Homepage SEO configuration updated successfully.');
    }

    /**
     * Update AI Tools SEO settings.
     */
    public function updateTools(Request $request): RedirectResponse
    {
        $request->validate([
            'tool_image_title' => 'required|string|max:150',
            'tool_image_description' => 'required|string|max:300',
            'tool_image_og_image' => 'nullable|url|max:255',

            'tool_video_title' => 'required|string|max:150',
            'tool_video_description' => 'required|string|max:300',
            'tool_video_og_image' => 'nullable|url|max:255',

            'tool_i2v_title' => 'required|string|max:150',
            'tool_i2v_description' => 'required|string|max:300',
            'tool_i2v_og_image' => 'nullable|url|max:255',
        ]);

        SiteSetting::set('seo_tool_image_title', $request->input('tool_image_title'));
        SiteSetting::set('seo_tool_image_description', $request->input('tool_image_description'));
        SiteSetting::set('seo_tool_image_og_image', $request->input('tool_image_og_image'));

        SiteSetting::set('seo_tool_video_title', $request->input('tool_video_title'));
        SiteSetting::set('seo_tool_video_description', $request->input('tool_video_description'));
        SiteSetting::set('seo_tool_video_og_image', $request->input('tool_video_og_image'));

        SiteSetting::set('seo_tool_i2v_title', $request->input('tool_i2v_title'));
        SiteSetting::set('seo_tool_i2v_description', $request->input('tool_i2v_description'));
        SiteSetting::set('seo_tool_i2v_og_image', $request->input('tool_i2v_og_image'));

        $this->seoService->clearCache();

        return redirect()->route('admin.seo.index', ['tab' => 'tools'])
            ->with('success', 'AI Tools SEO configurations updated successfully.');
    }

    /**
     * Update Search Engine Verification settings.
     */
    public function updateVerification(Request $request): RedirectResponse
    {
        $request->validate([
            'google_verification' => 'nullable|string|max:255',
            'bing_verification' => 'nullable|string|max:255',
        ]);

        SiteSetting::set('seo_google_verification', trim($request->input('google_verification') ?? ''));
        SiteSetting::set('seo_bing_verification', trim($request->input('bing_verification') ?? ''));

        $this->seoService->clearCache();

        return redirect()->route('admin.seo.index', ['tab' => 'verification'])
            ->with('success', 'Search engine verification codes updated successfully.');
    }

    /**
     * Update Custom Robots.txt directives.
     */
    public function updateRobotsTxt(Request $request): RedirectResponse
    {
        $request->validate([
            'robots_txt_custom' => 'nullable|string|max:5000',
        ]);

        SiteSetting::set('seo_robots_txt_custom', $request->input('robots_txt_custom'));

        $this->seoService->clearCache();

        return redirect()->route('admin.seo.index', ['tab' => 'robots'])
            ->with('success', 'Robots.txt configuration updated successfully.');
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

    /**
     * Upload an image for Open Graph or Twitter cards to Cloudflare R2 / public storage.
     */
    public function uploadImage(Request $request, \App\Services\Storage\R2StorageService $r2Service): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:10240',
        ]);

        try {
            $file = $request->file('image');
            $uploadResult = $r2Service->uploadArticleImage($file);

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
}
