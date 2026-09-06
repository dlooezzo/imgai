<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\Seo\SeoService;
use App\Services\Storage\R2StorageService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BrandingController extends Controller
{
    protected SeoService $seoService;

    public function __construct(SeoService $seoService)
    {
        $this->seoService = $seoService;
    }

    /**
     * Display Branding, Logo & Technical SEO settings page.
     */
    public function index(Request $request)
    {
        $siteTitle = SiteSetting::get('site_title', config('app.name', 'IMGAI'));

        $settings = [
            // Core Branding & Identity
            'site_title' => $siteTitle,
            'site_tagline' => SiteSetting::get('site_tagline', 'AI Creative Studio'),
            'site_logo' => SiteSetting::get('site_logo', ''),
            'site_logo_icon' => SiteSetting::get('site_logo_icon', ''),
            'site_favicon' => SiteSetting::get('site_favicon', ''),
            'site_footer_text' => SiteSetting::get('site_footer_text', '© ' . date('Y') . ' IMGAI. All rights reserved.'),

            // Technical SEO Title & Metadata
            'seo_site_name' => SiteSetting::get('seo_site_name', $siteTitle . ' — AI Creative Studio'),
            'seo_title_format' => SiteSetting::get('seo_title_format', '%title% | %site_name%'),
            'seo_default_title' => SiteSetting::get('seo_default_title', 'IMGAI — Cinematic 2MP Image & Video AI Creation Studio'),
            'seo_homepage_title' => SiteSetting::get('seo_homepage_title', 'IMGAI — Cinematic 2MP Image & Video AI Studio Platform'),
            'seo_default_description' => SiteSetting::get('seo_default_description', 'Synthesize photorealistic 2MP cinematic imagery and fluid motion video sequences with Wan 2.2 and Tencent Hunyuan generative AI diffusion models.'),
            'seo_homepage_description' => SiteSetting::get('seo_homepage_description', 'Transform creative ideas into photorealistic 2MP visual assets and 24 FPS cinematic motion videos with Wan 2.2 and Hunyuan generative AI models.'),
            'seo_meta_keywords' => SiteSetting::get('seo_meta_keywords', 'AI image generator, text to image, AI video studio, photorealistic AI, Wan 2.2, Hunyuan'),
            'seo_default_robots' => SiteSetting::get('seo_default_robots', 'index, follow'),
            'seo_default_og_image' => SiteSetting::get('seo_default_og_image', ''),
            'seo_google_verification' => SiteSetting::get('seo_google_verification', ''),
            'seo_bing_verification' => SiteSetting::get('seo_bing_verification', ''),
        ];

        return view('admin.branding.index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update and save Website Title, Logo, and Technical SEO Title & Metadata.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            // Core Branding
            'site_title' => 'required|string|max:100',
            'site_tagline' => 'nullable|string|max:200',
            'site_logo' => 'nullable|string|max:1000',
            'site_logo_icon' => 'nullable|string|max:1000',
            'site_favicon' => 'nullable|string|max:1000',
            'site_footer_text' => 'nullable|string|max:500',

            // Technical SEO Title & Metadata
            'seo_site_name' => 'nullable|string|max:150',
            'seo_title_format' => 'nullable|string|max:100',
            'seo_default_title' => 'nullable|string|max:200',
            'seo_homepage_title' => 'nullable|string|max:200',
            'seo_default_description' => 'nullable|string|max:500',
            'seo_homepage_description' => 'nullable|string|max:500',
            'seo_meta_keywords' => 'nullable|string|max:500',
            'seo_default_robots' => 'nullable|string|max:50',
            'seo_default_og_image' => 'nullable|string|max:1000',
            'seo_google_verification' => 'nullable|string|max:255',
            'seo_bing_verification' => 'nullable|string|max:255',
        ]);

        // Save Core Branding
        SiteSetting::set('site_title', trim($validated['site_title']));
        SiteSetting::set('site_tagline', trim($validated['site_tagline'] ?? ''));
        SiteSetting::set('site_logo', trim($validated['site_logo'] ?? ''));
        SiteSetting::set('site_logo_icon', trim($validated['site_logo_icon'] ?? ''));
        SiteSetting::set('site_favicon', trim($validated['site_favicon'] ?? ''));
        SiteSetting::set('site_footer_text', trim($validated['site_footer_text'] ?? ''));

        // Save Technical SEO Title & Metadata
        if (array_key_exists('seo_site_name', $validated)) {
            SiteSetting::set('seo_site_name', trim($validated['seo_site_name'] ?? ''));
        }
        if (array_key_exists('seo_title_format', $validated)) {
            SiteSetting::set('seo_title_format', trim($validated['seo_title_format'] ?? '') ?: '%title% | %site_name%');
        }
        if (array_key_exists('seo_default_title', $validated)) {
            SiteSetting::set('seo_default_title', trim($validated['seo_default_title'] ?? ''));
        }
        if (array_key_exists('seo_homepage_title', $validated)) {
            SiteSetting::set('seo_homepage_title', trim($validated['seo_homepage_title'] ?? ''));
        }
        if (array_key_exists('seo_default_description', $validated)) {
            SiteSetting::set('seo_default_description', trim($validated['seo_default_description'] ?? ''));
        }
        if (array_key_exists('seo_homepage_description', $validated)) {
            SiteSetting::set('seo_homepage_description', trim($validated['seo_homepage_description'] ?? ''));
        }
        if (array_key_exists('seo_meta_keywords', $validated)) {
            SiteSetting::set('seo_meta_keywords', trim($validated['seo_meta_keywords'] ?? ''));
        }
        if (array_key_exists('seo_default_robots', $validated)) {
            SiteSetting::set('seo_default_robots', trim($validated['seo_default_robots'] ?? '') ?: 'index, follow');
        }
        if (array_key_exists('seo_default_og_image', $validated)) {
            SiteSetting::set('seo_default_og_image', trim($validated['seo_default_og_image'] ?? ''));
        }
        if (array_key_exists('seo_google_verification', $validated)) {
            SiteSetting::set('seo_google_verification', trim($validated['seo_google_verification'] ?? ''));
        }
        if (array_key_exists('seo_bing_verification', $validated)) {
            SiteSetting::set('seo_bing_verification', trim($validated['seo_bing_verification'] ?? ''));
        }

        config(['app.name' => trim($validated['site_title'])]);

        // Clear SEO cache to immediately apply Technical SEO Title & Metadata changes
        try {
            $this->seoService->clearCache();
        } catch (\Throwable $e) {
            Log::warning('Failed to clear SEO cache on branding update: ' . $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Website Branding, Logo, and Technical SEO Title & Metadata successfully saved!',
            ]);
        }

        return redirect()->back()->with('success', 'Website branding, logo, and Technical SEO Title & Metadata successfully updated and saved!');
    }

    /**
     * Direct image uploader for Logo, Favicon, and SEO Open Graph assets.
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
            Log::error('Branding image upload failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 422);
        }
    }
}
