<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ToolArticle;
use App\Services\Seo\HtmlSanitizer;
use App\Services\Seo\SeoService;
use App\Services\Storage\R2StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ToolArticleController extends Controller
{
    /**
     * Supported AI tools.
     */
    protected array $supportedTools = [
        'image-generator' => [
            'key' => 'image-generator',
            'name' => 'Text-to-Image Generator',
            'model' => 'Wan 2.2 Cinematic (2MP)',
            'public_url' => '/tools/image-generator',
            'badge' => 'Text-to-Image',
            'default_title' => 'The Complete Guide to 2MP Photorealistic AI Image Generation',
            'default_seo_title' => 'Text-to-Image AI Generator — Wan 2.2 Cinematic 2MP | IMGAI',
            'default_meta_description' => 'Synthesize photorealistic 2MP ultra-high definition images from text prompts with precise lighting, rich texture, and custom aspect ratios on IMGAI.',
        ],
        'video-generator' => [
            'key' => 'video-generator',
            'name' => 'Text-to-Video Generator',
            'model' => 'Tencent Hunyuan-Video (24 FPS)',
            'public_url' => '/tools/video-generator',
            'badge' => 'Text-to-Video',
            'default_title' => 'Mastering Cinematic Text-to-Video Diffusion at 24 FPS',
            'default_seo_title' => 'Text-to-Video AI Generator — Hunyuan Diffusion 24FPS | IMGAI',
            'default_meta_description' => 'Generate fluid temporal cinematic motion video sequences from text prompts at 24 FPS with direct high-definition MP4 export on IMGAI AI Studio.',
        ],
        'image-to-video' => [
            'key' => 'image-to-video',
            'name' => 'Image to Video Generator',
            'model' => 'Wan 2.2 Motion Synthesis + Cloudflare R2',
            'public_url' => '/tools/image-to-video',
            'badge' => 'Image-to-Video',
            'default_title' => 'Transforming Static Artwork into 24 FPS Living Motion Sequences',
            'default_seo_title' => 'Image-to-Video AI Generator — Wan 2.2 Motion Studio | IMGAI',
            'default_meta_description' => 'Transform static pictures into living cinematic motion clips with camera dynamic controls and high-speed Cloudflare R2 cloud storage on IMGAI.',
        ],
    ];

    /**
     * List all AI Tool Articles.
     */
    public function index()
    {
        $articles = ToolArticle::all()->keyBy('tool_key');

        $toolList = [];
        foreach ($this->supportedTools as $key => $meta) {
            $article = $articles->get($key);
            $toolList[] = [
                'meta' => $meta,
                'article' => $article,
                'status' => $article ? $article->status : 'uncreated',
                'word_count' => $article ? $article->word_count : 0,
                'reading_time' => $article ? $article->reading_time : 0,
                'updated_at' => $article ? $article->updated_at : null,
            ];
        }

        $stats = [
            'total' => count($this->supportedTools),
            'published' => $articles->where('status', 'published')->count(),
            'drafts' => $articles->where('status', 'draft')->count(),
            'uncreated' => count($this->supportedTools) - $articles->count(),
        ];

        return view('admin.seo.articles.index', [
            'toolList' => $toolList,
            'stats' => $stats,
        ]);
    }

    /**
     * Show editor for an AI tool article.
     */
    public function edit(string $toolKey)
    {
        if (!isset($this->supportedTools[$toolKey])) {
            abort(404, 'Supported tool not found.');
        }

        $toolMeta = $this->supportedTools[$toolKey];
        $article = ToolArticle::firstOrNew(['tool_key' => $toolKey]);

        // If new, pre-fill intelligent defaults
        if (!$article->exists) {
            $article->title = $toolMeta['default_title'];
            $article->seo_title = $toolMeta['default_seo_title'];
            $article->meta_description = $toolMeta['default_meta_description'];
            $article->status = 'draft';
        }

        return view('admin.seo.articles.editor', [
            'toolKey' => $toolKey,
            'toolMeta' => $toolMeta,
            'article' => $article,
        ]);
    }

    /**
     * Update or create the AI tool article.
     */
    public function update(Request $request, string $toolKey): RedirectResponse
    {
        if (!isset($this->supportedTools[$toolKey])) {
            abort(404, 'Supported tool not found.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'seo_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'excerpt' => 'nullable|string|max:500',
            'content_html' => 'required|string',
            'featured_image' => 'nullable|url|max:255',
            'status' => 'required|in:draft,published',
        ]);

        // Sanitize rich HTML content
        $validated['content_html'] = HtmlSanitizer::sanitize($validated['content_html']);
        $validated['slug'] = Str::slug($validated['title']) ?: $toolKey;

        $article = ToolArticle::firstOrNew(['tool_key' => $toolKey]);
        
        $wasPublished = ($article->status === 'published');
        $isPublishing = ($validated['status'] === 'published');

        if (!$wasPublished && $isPublishing) {
            $validated['published_at'] = now();
        }

        $article->fill($validated);
        $article->save();

        // Clear SEO cache immediately
        app(SeoService::class)->clearCache();

        return redirect()->route('admin.seo.articles.index')
            ->with('success', "Article for '{$this->supportedTools[$toolKey]['name']}' saved successfully.");
    }

    /**
     * Toggle draft/published status.
     */
    public function togglePublish(Request $request, string $toolKey): JsonResponse|RedirectResponse
    {
        if (!isset($this->supportedTools[$toolKey])) {
            abort(404, 'Supported tool not found.');
        }

        $article = ToolArticle::where('tool_key', $toolKey)->firstOrFail();
        $article->status = ($article->status === 'published') ? 'draft' : 'published';
        
        if ($article->status === 'published' && !$article->published_at) {
            $article->published_at = now();
        }
        
        $article->save();
        app(SeoService::class)->clearCache();

        $msg = "Article for '{$this->supportedTools[$toolKey]['name']}' is now " . ucfirst($article->status) . ".";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'status' => $article->status,
                'message' => $msg,
            ]);
        }

        return redirect()->route('admin.seo.articles.index')->with('success', $msg);
    }

    /**
     * Preview article inside public tool layout simulation.
     */
    public function preview(string $toolKey)
    {
        if (!isset($this->supportedTools[$toolKey])) {
            abort(404, 'Supported tool not found.');
        }

        $toolMeta = $this->supportedTools[$toolKey];
        $article = ToolArticle::where('tool_key', $toolKey)->first();

        if (!$article) {
            return redirect()->route('admin.seo.articles.edit', $toolKey)
                ->with('error', 'Please create and save an article before previewing.');
        }

        return view('admin.seo.articles.preview', [
            'toolKey' => $toolKey,
            'toolMeta' => $toolMeta,
            'article' => $article,
        ]);
    }

    /**
     * Upload an article image to Cloudflare R2 and return HTML figure payload.
     */
    public function uploadImage(Request $request, R2StorageService $r2Service): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:10240', // Max 10MB
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:255',
        ]);

        try {
            $file = $request->file('image');
            $altText = trim((string) $request->input('alt_text', ''));
            if (empty($altText)) {
                $altText = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            }
            $caption = trim((string) $request->input('caption', ''));

            $uploadResult = $r2Service->uploadArticleImage($file);
            $url = $uploadResult['url'];

            // Construct safe semantic HTML
            if (!empty($caption)) {
                $html = '<figure class="article-figure"><img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($altText, ENT_QUOTES, 'UTF-8') . '" loading="lazy" class="article-img"><figcaption>' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</figcaption></figure>';
            } else {
                $html = '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($altText, ENT_QUOTES, 'UTF-8') . '" loading="lazy" class="article-img">';
            }

            return response()->json([
                'success' => true,
                'url' => $url,
                'alt' => $altText,
                'caption' => $caption,
                'html' => $html,
                'filename' => $uploadResult['filename'],
            ]);
        } catch (\Throwable $e) {
            Log::error("ToolArticleController uploadImage failed: {$e->getMessage()}", ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Image upload failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Duplicate article into a draft template.
     */
    public function duplicate(string $toolKey): RedirectResponse
    {
        if (!isset($this->supportedTools[$toolKey])) {
            abort(404, 'Supported tool not found.');
        }

        $article = ToolArticle::where('tool_key', $toolKey)->firstOrFail();
        $article->title = $article->title . ' (Copy)';
        $article->status = 'draft';
        $article->save();

        app(SeoService::class)->clearCache();

        return redirect()->route('admin.seo.articles.edit', $toolKey)
            ->with('success', "Article for '{$this->supportedTools[$toolKey]['name']}' duplicated as draft.");
    }

    /**
     * Delete an article.
     */
    public function destroy(string $toolKey): RedirectResponse
    {
        $article = ToolArticle::where('tool_key', $toolKey)->first();
        if ($article) {
            $article->delete();
            app(SeoService::class)->clearCache();
        }

        return redirect()->route('admin.seo.articles.index')
            ->with('success', "Article for '{$toolKey}' deleted.");
    }
}
