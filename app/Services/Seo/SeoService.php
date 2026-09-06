<?php

namespace App\Services\Seo;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeoService
{
    const CACHE_KEY_SETTINGS = 'site_seo_settings';
    const CACHE_KEY_SITEMAP = 'site_sitemap_xml';

    /**
     * Get the standardized production base URL dynamically.
     */
    public function getBaseUrl(): string
    {
        // 1. Check SiteSetting for custom site_url if configured in database
        if (class_exists(SiteSetting::class) && Schema::hasTable('site_settings')) {
            $siteUrl = SiteSetting::get('site_url');
            if (!empty($siteUrl)) {
                return rtrim($siteUrl, '/');
            }
        }

        // 2. Check config('app.url')
        $url = config('app.url');
        if (!empty($url) && !in_array($url, ['http://localhost', 'https://localhost', 'http://127.0.0.1'])) {
            return rtrim($url, '/');
        }

        // 3. In HTTP request context, cleanly derive from the verified request
        if (function_exists('request') && request() && !app()->runningInConsole()) {
            return rtrim(request()->getSchemeAndHttpHost(), '/');
        }

        // 4. Default fallback using Laravel's url helper
        return rtrim($url ?: url('/'), '/');
    }

    /**
     * Retrieve all global SEO settings with caching.
     */
    public function getGlobalSettings(): array
    {
        return Cache::remember(self::CACHE_KEY_SETTINGS, 86400, function () {
            $appUrl = $this->getBaseUrl();

            return [
                'site_name' => SiteSetting::get('seo_site_name', SiteSetting::get('site_title', config('app.name', 'IMGAI'))),
                'title_format' => SiteSetting::get('seo_title_format', '%title% | %site_name%'),
                'default_title' => SiteSetting::get('seo_default_title', 'IMGAI — Cinematic 2MP Image & Video AI Creation Studio'),
                'default_description' => SiteSetting::get('seo_default_description', 'Synthesize photorealistic 2MP cinematic imagery and fluid motion video sequences with Wan 2.2 and Tencent Hunyuan generative AI diffusion models.'),
                'meta_keywords' => SiteSetting::get('seo_meta_keywords', 'AI image generator, text to image, AI video studio, photorealistic AI, Wan 2.2, Hunyuan'),
                'default_og_image' => SiteSetting::get('seo_default_og_image', $appUrl . '/images/og-showcase-preview.jpg'),
                'default_twitter_image' => SiteSetting::get('seo_default_twitter_image', $appUrl . '/images/twitter-card-preview.jpg'),
                'default_robots' => SiteSetting::get('seo_default_robots', 'index, follow'),
                'google_verification' => SiteSetting::get('seo_google_verification', ''),
                'bing_verification' => SiteSetting::get('seo_bing_verification', ''),
                'locale' => SiteSetting::get('seo_locale', 'en_US'),
                
                // Homepage overrides (Optimized 50-60 title, 140-160 desc)
                'homepage_title' => SiteSetting::get('seo_homepage_title', 'IMGAI — Cinematic 2MP Image & Video AI Studio Platform'),
                'homepage_description' => SiteSetting::get('seo_homepage_description', 'Transform creative ideas into photorealistic 2MP visual assets and 24 FPS cinematic motion videos with Wan 2.2 and Hunyuan generative AI models.'),
                'homepage_og_image' => SiteSetting::get('seo_homepage_og_image', ''),
                'homepage_robots' => SiteSetting::get('seo_homepage_robots', 'index, follow'),

                // Tool specific overrides (Optimized 50-60 title, 140-160 desc)
                'tool_image_title' => SiteSetting::get('seo_tool_image_title', 'Text-to-Image AI Generator — Wan 2.2 Cinematic 2MP | IMGAI'),
                'tool_image_description' => SiteSetting::get('seo_tool_image_description', 'Synthesize photorealistic 2MP ultra-high definition images from text prompts with precise lighting, rich texture, and custom aspect ratios on IMGAI.'),
                'tool_image_og_image' => SiteSetting::get('seo_tool_image_og_image', ''),

                'tool_video_title' => SiteSetting::get('seo_tool_video_title', 'Text-to-Video AI Generator — Hunyuan Diffusion 24FPS | IMGAI'),
                'tool_video_description' => SiteSetting::get('seo_tool_video_description', 'Generate fluid temporal cinematic motion video sequences from text prompts at 24 FPS with direct high-definition MP4 export on IMGAI AI Studio.'),
                'tool_video_og_image' => SiteSetting::get('seo_tool_video_og_image', ''),

                'tool_i2v_title' => SiteSetting::get('seo_tool_i2v_title', 'Image-to-Video AI Generator — Wan 2.2 Motion Studio | IMGAI'),
                'tool_i2v_description' => SiteSetting::get('seo_tool_i2v_description', 'Transform static pictures into living cinematic motion clips with camera dynamic controls and high-speed Cloudflare R2 cloud storage on IMGAI.'),
                'tool_i2v_og_image' => SiteSetting::get('seo_tool_i2v_og_image', ''),

                // Custom Robots.txt
                'robots_txt_custom' => SiteSetting::get('seo_robots_txt_custom', ''),
            ];
        });
    }

    /**
     * Clear SEO-related cached settings and sitemaps.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_SETTINGS);
        Cache::forget(self::CACHE_KEY_SITEMAP);
    }

    /**
     * Build clean canonical HTTPS URL from path (strips query parameters and trailing slashes).
     */
    public function buildCanonicalUrl(string $path = ''): string
    {
        $appUrl = $this->getBaseUrl();
        $cleanPath = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?? '', '/');
        $cleanPath = rtrim($cleanPath, '/');

        return ($cleanPath === '' || $cleanPath === '/') ? $appUrl : $appUrl . $cleanPath;
    }

    /**
     * Resolve unified SEO metadata for a CMS Page.
     */
    public function resolveForPage(Page $page, bool $isAdminPreview = false): array
    {
        $globals = $this->getGlobalSettings();
        $appUrl = $this->getBaseUrl();

        $title = $page->meta_title ?: $page->title;
        $formattedTitle = !empty($page->meta_title) ? $page->meta_title : "{$title} — {$globals['site_name']}";
        $description = $page->meta_description ?: ($page->excerpt ?: Str::limit(strip_tags($page->content), 155));
        $canonical = $page->canonical_url ?: $this->buildCanonicalUrl($page->slug);

        $robots = ($isAdminPreview || !$page->isPublished())
            ? 'noindex, nofollow'
            : ($page->robots_directive ?: $globals['default_robots']);

        $ogTitle = $page->og_title ?: $title;
        $ogDesc = $page->og_description ?: $description;
        $ogImage = $page->og_image ?: $globals['default_og_image'];

        $twTitle = $page->twitter_title ?: $ogTitle;
        $twDesc = $page->twitter_description ?: $ogDesc;
        $twImage = $page->twitter_image ?: ($page->og_image ?: $globals['default_twitter_image']);

        $breadcrumbs = [
            ['name' => 'Home', 'url' => $appUrl],
            ['name' => $page->title, 'url' => $canonical],
        ];

        return [
            'title' => $formattedTitle,
            'description' => $description,
            'keywords' => $globals['meta_keywords'] ?? '',
            'canonical' => $canonical,
            'robots' => $robots,
            'og' => [
                'title' => $ogTitle,
                'description' => $ogDesc,
                'url' => $canonical,
                'image' => $ogImage,
                'type' => 'article',
                'site_name' => $globals['site_name'],
                'locale' => $globals['locale'],
            ],
            'twitter' => [
                'card' => 'summary_large_image',
                'title' => $twTitle,
                'description' => $twDesc,
                'image' => $twImage,
            ],
            'verification' => [
                'google' => $globals['google_verification'],
                'bing' => $globals['bing_verification'],
            ],
            'breadcrumbs' => $breadcrumbs,
            'structured_data' => $this->buildArticleJsonLd($page, $formattedTitle, $description, $canonical, $ogImage, $breadcrumbs),
        ];
    }

    /**
     * Resolve unified SEO metadata for AI Tool or Studio Overview.
     */
    public function resolveForTool(string $toolKey = 'overview'): array
    {
        $globals = $this->getGlobalSettings();
        $appUrl = $this->getBaseUrl();

        $toolMap = [
            'overview' => [
                'title' => $globals['homepage_title'],
                'description' => $globals['homepage_description'],
                'path' => '/',
                'og_image' => $globals['homepage_og_image'] ?: $globals['default_og_image'],
                'crumb' => 'Studio Overview',
                'is_tool' => false,
            ],
            'image-generator' => [
                'title' => $globals['tool_image_title'],
                'description' => $globals['tool_image_description'],
                'path' => '/tools/image-generator',
                'og_image' => $globals['tool_image_og_image'] ?: $globals['default_og_image'],
                'crumb' => 'Text-to-Image Studio',
                'is_tool' => true,
            ],
            'video-generator' => [
                'title' => $globals['tool_video_title'],
                'description' => $globals['tool_video_description'],
                'path' => '/tools/video-generator',
                'og_image' => $globals['tool_video_og_image'] ?: $globals['default_og_image'],
                'crumb' => 'Text-to-Video Studio',
                'is_tool' => true,
            ],
            'image-to-video' => [
                'title' => $globals['tool_i2v_title'],
                'description' => $globals['tool_i2v_description'],
                'path' => '/tools/image-to-video',
                'og_image' => $globals['tool_i2v_og_image'] ?: $globals['default_og_image'],
                'crumb' => 'Image-to-Video Studio',
                'is_tool' => true,
            ],
            'profile' => [
                'title' => 'User Account & API Keys — IMGAI',
                'description' => 'Manage your IMGAI studio account, authentication credentials, and generation usage.',
                'path' => '/profile',
                'og_image' => $globals['default_og_image'],
                'crumb' => 'Account Profile',
                'robots' => 'noindex, nofollow',
                'is_tool' => false,
            ],
            'library' => [
                'title' => 'Media Creation Library — IMGAI',
                'description' => 'Private repository of generated cinematic images and videos.',
                'path' => '/library',
                'og_image' => $globals['default_og_image'],
                'crumb' => 'Media Library',
                'robots' => 'noindex, nofollow',
                'is_tool' => false,
            ],
        ];

        $current = $toolMap[$toolKey] ?? $toolMap['overview'];
        $canonical = $this->buildCanonicalUrl($current['path']);
        $robots = $current['robots'] ?? $globals['default_robots'];

        // Check if a published ToolArticle exists for this tool
        $article = null;
        if ($current['is_tool'] && Schema::hasTable('tool_articles')) {
            $article = \App\Models\ToolArticle::where('tool_key', $toolKey)
                ->where('status', 'published')
                ->first();

            if ($article) {
                if (!empty($article->seo_title)) {
                    $current['title'] = $article->seo_title;
                }
                if (!empty($article->meta_description)) {
                    $current['description'] = $article->meta_description;
                } elseif (!empty($article->excerpt)) {
                    $current['description'] = $article->excerpt;
                }
                if (!empty($article->featured_image)) {
                    $current['og_image'] = $article->featured_image;
                }
            }
        }

        $breadcrumbs = [
            ['name' => 'Home', 'url' => $appUrl],
            ['name' => 'Tools', 'url' => $appUrl . '/tools'],
            ['name' => $current['crumb'], 'url' => $canonical],
        ];

        if ($current['is_tool']) {
            $structuredData = $this->buildToolAppJsonLd($current['title'], $current['description'], $canonical, $current['og_image'], $breadcrumbs);
            if ($article) {
                $structuredData = array_merge($structuredData, $this->buildArticleJsonLdForTool($article, $current['title'], $current['description'], $canonical, $current['og_image'], $breadcrumbs));
            }
        } else {
            $structuredData = $this->buildWebSiteJsonLd($current['title'], $current['description'], $canonical, $current['og_image'], $breadcrumbs);
        }

        return [
            'title' => $current['title'],
            'description' => $current['description'],
            'keywords' => $globals['meta_keywords'] ?? '',
            'canonical' => $canonical,
            'robots' => $robots,
            'og' => [
                'title' => $current['title'],
                'description' => $current['description'],
                'url' => $canonical,
                'image' => $current['og_image'],
                'type' => $article ? 'article' : 'website',
                'site_name' => $globals['site_name'],
                'locale' => $globals['locale'],
            ],
            'twitter' => [
                'card' => 'summary_large_image',
                'title' => $current['title'],
                'description' => $current['description'],
                'image' => $current['og_image'],
            ],
            'verification' => [
                'google' => $globals['google_verification'],
                'bing' => $globals['bing_verification'],
            ],
            'breadcrumbs' => $breadcrumbs,
            'structured_data' => $structuredData,
        ];
    }

    /**
     * Generate dynamic XML Sitemap containing all public indexable URLs.
     */
    public function generateSitemapXml(): string
    {
        return Cache::remember(self::CACHE_KEY_SITEMAP, 3600, function () {
            $appUrl = $this->getBaseUrl();
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            // 1. Homepage
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($appUrl, ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
            $xml .= "    <changefreq>daily</changefreq>\n";
            $xml .= "    <priority>1.0</priority>\n";
            $xml .= "  </url>\n";

            // 2. Public Tool Landing Pages
            $tools = [
                ['path' => '/tools/overview', 'freq' => 'daily', 'priority' => '0.9'],
                ['path' => '/tools/image-generator', 'freq' => 'weekly', 'priority' => '0.9'],
                ['path' => '/tools/video-generator', 'freq' => 'weekly', 'priority' => '0.9'],
                ['path' => '/tools/image-to-video', 'freq' => 'weekly', 'priority' => '0.9'],
            ];

            foreach ($tools as $tool) {
                $xml .= "  <url>\n";
                $xml .= "    <loc>" . htmlspecialchars($appUrl . $tool['path'], ENT_XML1, 'UTF-8') . "</loc>\n";
                $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
                $xml .= "    <changefreq>" . $tool['freq'] . "</changefreq>\n";
                $xml .= "    <priority>" . $tool['priority'] . "</priority>\n";
                $xml .= "  </url>\n";
            }

            // 3. Published Indexable CMS Pages
            if (Schema::hasTable('pages')) {
                $pages = Page::where('status', 'published')
                    ->where(function ($q) {
                        $q->whereNull('robots_directive')
                          ->orWhere('robots_directive', 'not like', '%noindex%');
                    })
                    ->orderBy('navigation_order', 'asc')
                    ->get();

                foreach ($pages as $page) {
                    $pageUrl = $page->canonical_url ?: ($appUrl . '/' . ltrim($page->slug, '/'));
                    $lastMod = $page->updated_at ? $page->updated_at->format('Y-m-d') : date('Y-m-d');

                    $xml .= "  <url>\n";
                    $xml .= "    <loc>" . htmlspecialchars($pageUrl, ENT_XML1, 'UTF-8') . "</loc>\n";
                    $xml .= "    <lastmod>" . $lastMod . "</lastmod>\n";
                    $xml .= "    <changefreq>weekly</changefreq>\n";
                    $xml .= "    <priority>" . ($page->show_in_navigation ? '0.8' : '0.6') . "</priority>\n";
                    $xml .= "  </url>\n";
                }
            }

            $xml .= '</urlset>';
            return $xml;
        });
    }

    /**
     * Generate dynamic robots.txt content with production domain sitemap.
     */
    public function generateRobotsTxt(): string
    {
        $globals = $this->getGlobalSettings();
        $appUrl = $this->getBaseUrl();

        if (!empty($globals['robots_txt_custom'])) {
            $custom = trim($globals['robots_txt_custom']);
            if (!str_contains($custom, 'Sitemap:')) {
                $custom .= "\n\nSitemap: {$appUrl}/sitemap.xml";
            }
            return $custom;
        }

        $lines = [
            "# IMGAI Studio Search Engine Robots Directives",
            "User-agent: *",
            "Disallow: /admin/",
            "Disallow: /profile",
            "Disallow: /library",
            "Disallow: /auth/",
            "Disallow: /logout",
            "Disallow: /up",
            "Allow: /",
            "Allow: /tools",
            "Allow: /about",
            "Allow: /pricing",
            "Allow: /faq",
            "Allow: /contact",
            "Allow: /terms",
            "Allow: /privacy",
            "",
            "Sitemap: {$appUrl}/sitemap.xml",
        ];

        return implode("\n", $lines);
    }

    /**
     * Perform Technical SEO Audit of public indexable pages.
     */
    public function getSeoAudit(): array
    {
        $appUrl = $this->getBaseUrl();
        $auditList = [];

        // 1. Audit Homepage
        $homeSeo = $this->resolveForTool('overview');
        $auditList[] = $this->auditSingleItem('Homepage (/)', $appUrl, $homeSeo['title'], $homeSeo['description'], $homeSeo['canonical'], $homeSeo['og']['image'], $homeSeo['robots']);

        // 2. Audit Tools
        $toolKeys = [
            'image-generator' => 'Text-to-Image Tool',
            'video-generator' => 'Text-to-Video Tool',
            'image-to-video' => 'Image-to-Video Tool',
        ];

        foreach ($toolKeys as $key => $label) {
            $seo = $this->resolveForTool($key);
            $auditList[] = $this->auditSingleItem($label, $seo['canonical'], $seo['title'], $seo['description'], $seo['canonical'], $seo['og']['image'], $seo['robots']);
        }

        // 3. Audit CMS Pages
        if (Schema::hasTable('pages')) {
            $pages = Page::all();
            foreach ($pages as $page) {
                $seo = $this->resolveForPage($page, false);
                $auditList[] = $this->auditSingleItem(
                    "Page: {$page->title} (/{$page->slug})",
                    $seo['canonical'],
                    $seo['title'],
                    $seo['description'],
                    $seo['canonical'],
                    $seo['og']['image'],
                    $seo['robots'],
                    $page->status
                );
            }
        }

        return $auditList;
    }

    /**
     * Audit single page criteria against optimal technical thresholds.
     */
    protected function auditSingleItem(string $name, string $url, ?string $title, ?string $desc, ?string $canonical, ?string $ogImage, string $robots, string $status = 'published'): array
    {
        $issues = [];
        $titleLen = mb_strlen($title ?? '');
        $descLen = mb_strlen($desc ?? '');

        // Title checks (Recommended: 45-65 chars)
        if (empty($title)) {
            $issues[] = ['type' => 'error', 'msg' => 'Missing SEO Title tag'];
        } elseif ($titleLen < 30) {
            $issues[] = ['type' => 'warning', 'msg' => "Title is short ({$titleLen} chars, recommended: 50-60)"];
        } elseif ($titleLen > 70) {
            $issues[] = ['type' => 'warning', 'msg' => "Title exceeds 70 chars ({$titleLen} chars)"];
        }

        // Description checks (Recommended: 130-165 chars)
        if (empty($desc)) {
            $issues[] = ['type' => 'error', 'msg' => 'Missing Meta Description'];
        } elseif ($descLen < 80) {
            $issues[] = ['type' => 'warning', 'msg' => "Description is short ({$descLen} chars, recommended: 140-160)"];
        } elseif ($descLen > 180) {
            $issues[] = ['type' => 'warning', 'msg' => "Description exceeds 180 chars ({$descLen} chars)"];
        }

        // Canonical check
        if (empty($canonical)) {
            $issues[] = ['type' => 'error', 'msg' => 'Missing Canonical URL'];
        }

        // Social Image check
        if (empty($ogImage)) {
            $issues[] = ['type' => 'warning', 'msg' => 'Missing Open Graph / Social Image'];
        }

        // Status calculation
        $hasError = collect($issues)->contains('type', 'error');
        $hasWarning = collect($issues)->contains('type', 'warning');

        $healthStatus = 'Good';
        if ($hasError) {
            $healthStatus = 'Needs Attention';
        } elseif ($hasWarning) {
            $healthStatus = 'Warning';
        }

        return [
            'name' => $name,
            'url' => $url,
            'status' => $status,
            'health' => $healthStatus,
            'title' => $title,
            'title_len' => $titleLen,
            'description' => $desc,
            'desc_len' => $descLen,
            'canonical' => $canonical,
            'og_image' => $ogImage,
            'robots' => $robots,
            'issues' => $issues,
        ];
    }

    /**
     * JSON-LD builder for WebSite schema.
     */
    protected function buildWebSiteJsonLd(string $title, string $desc, string $url, string $image, array $breadcrumbs): array
    {
        $globals = $this->getGlobalSettings();
        $appUrl = $this->getBaseUrl();

        $schemas = [];

        // Organization
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $globals['site_name'],
            'url' => $appUrl,
            'logo' => $image ?: ($appUrl . '/images/logo.png'),
        ];

        // WebSite
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $globals['site_name'],
            'url' => $appUrl,
            'description' => $globals['default_description'],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $globals['site_name'],
            ],
        ];

        // BreadcrumbList
        if (!empty($breadcrumbs)) {
            $items = [];
            foreach ($breadcrumbs as $index => $crumb) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $crumb['name'],
                    'item' => $crumb['url'],
                ];
            }
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $items,
            ];
        }

        return $schemas;
    }

    /**
     * JSON-LD builder for AI Tool WebApplication schema.
     */
    protected function buildToolAppJsonLd(string $title, string $desc, string $url, string $image, array $breadcrumbs): array
    {
        $globals = $this->getGlobalSettings();
        $appUrl = $this->getBaseUrl();

        $schemas = [];

        // Organization
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $globals['site_name'],
            'url' => $appUrl,
            'logo' => $image ?: ($appUrl . '/images/logo.png'),
        ];

        // WebApplication / SoftwareApplication
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => $title,
            'description' => $desc,
            'url' => $url,
            'applicationCategory' => 'MultimediaApplication',
            'operatingSystem' => 'All',
            'browserRequirements' => 'Requires JavaScript. Requires HTML5.',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
            ],
            'provider' => [
                '@type' => 'Organization',
                'name' => $globals['site_name'],
                'url' => $appUrl,
            ],
        ];

        // BreadcrumbList
        if (!empty($breadcrumbs)) {
            $items = [];
            foreach ($breadcrumbs as $index => $crumb) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $crumb['name'],
                    'item' => $crumb['url'],
                ];
            }
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $items,
            ];
        }

        return $schemas;
    }

    /**
     * JSON-LD builder for Article / WebPage schema.
     */
    protected function buildArticleJsonLd(Page $page, string $title, string $desc, string $url, string $image, array $breadcrumbs): array
    {
        $globals = $this->getGlobalSettings();
        $appUrl = $this->getBaseUrl();

        $schemas = [];

        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $page->title,
            'description' => $desc,
            'url' => $url,
            'image' => $image,
            'datePublished' => $page->created_at ? $page->created_at->toIso8601String() : date('c'),
            'dateModified' => $page->updated_at ? $page->updated_at->toIso8601String() : date('c'),
            'author' => [
                '@type' => 'Organization',
                'name' => $globals['site_name'],
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $globals['site_name'],
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $image,
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $url,
            ],
        ];

        if (!empty($breadcrumbs)) {
            $items = [];
            foreach ($breadcrumbs as $index => $crumb) {
                $items[] = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $crumb['name'],
                    'item' => $crumb['url'],
                ];
            }
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => $items,
            ];
        }

        return $schemas;
    }

    /**
     * JSON-LD builder for Tool Article schema.
     */
    protected function buildArticleJsonLdForTool(\App\Models\ToolArticle $article, string $title, string $desc, string $url, string $image, array $breadcrumbs): array
    {
        $globals = $this->getGlobalSettings();
        $schemas = [];

        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $desc,
            'url' => $url,
            'image' => $image,
            'datePublished' => $article->published_at ? $article->published_at->toIso8601String() : ($article->created_at ? $article->created_at->toIso8601String() : date('c')),
            'dateModified' => $article->updated_at ? $article->updated_at->toIso8601String() : date('c'),
            'author' => [
                '@type' => 'Organization',
                'name' => $globals['site_name'],
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $globals['site_name'],
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $image,
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $url,
            ],
        ];

        return $schemas;
    }
}
