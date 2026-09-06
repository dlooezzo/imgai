<?php

namespace App\Http\Controllers;

use App\Services\Seo\SeoService;
use Illuminate\Http\Response;

class PublicSeoController extends Controller
{
    protected SeoService $seoService;

    public function __construct(SeoService $seoService)
    {
        $this->seoService = $seoService;
    }

    /**
     * Return dynamic XML sitemap.
     */
    public function sitemap(): Response
    {
        $xml = $this->seoService->generateSitemapXml();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Robots-Tag' => 'noindex', // Sitemap itself doesn't need to be indexed as a webpage
        ]);
    }

    /**
     * Return dynamic robots.txt.
     */
    public function robots(): Response
    {
        $content = $this->seoService->generateRobotsTxt();

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
