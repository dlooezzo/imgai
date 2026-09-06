<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    /**
     * Display a listing of all CMS pages.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $navigation = $request->input('navigation');
        $sort = $request->input('sort', 'navigation_order');

        $query = Page::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        if ($status && in_array($status, ['published', 'draft'], true)) {
            $query->where('status', $status);
        }

        if ($navigation === 'in_nav') {
            $query->where('show_in_navigation', true);
        } elseif ($navigation === 'not_in_nav') {
            $query->where('show_in_navigation', false);
        }

        if ($sort === 'title') {
            $query->orderBy('title', 'asc');
        } elseif ($sort === 'updated') {
            $query->orderByDesc('updated_at');
        } else {
            $query->orderBy('navigation_order', 'asc')->orderByDesc('updated_at');
        }

        $pages = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Page::count(),
            'published' => Page::where('status', 'published')->count(),
            'draft' => Page::where('status', 'draft')->count(),
            'in_navigation' => Page::where('show_in_navigation', true)->count(),
        ];

        return view('admin.pages.index', [
            'pages' => $pages,
            'search' => $search,
            'status' => $status,
            'navigation' => $navigation,
            'sort' => $sort,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new CMS page.
     */
    public function create()
    {
        $maxOrder = Page::max('navigation_order') ?? 0;

        return view('admin.pages.create', [
            'nextOrder' => $maxOrder + 1,
        ]);
    }

    /**
     * Store a newly created CMS page in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        // Sanitize slug
        if ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->input('slug'))]);
        } elseif ($request->filled('title')) {
            $request->merge(['slug' => Str::slug($request->input('title'))]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published',
            'show_in_navigation' => 'nullable|boolean',
            'navigation_label' => 'nullable|string|max:100',
            'navigation_order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|url|max:255',
            'robots_directive' => 'nullable|string|max:50',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|url|max:255',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string|max:500',
            'twitter_image' => 'nullable|url|max:255',
        ]);

        $validated['show_in_navigation'] = $request->has('show_in_navigation');
        $validated['navigation_order'] = (int) ($validated['navigation_order'] ?? 0);
        $validated['robots_directive'] = $validated['robots_directive'] ?? 'index, follow';
        $validated['created_by'] = auth()->id();

        $page = Page::create($validated);
        app(\App\Services\Seo\SeoService::class)->clearCache();

        return redirect()->route('admin.pages.index')
            ->with('success', "Page '{$page->title}' (/{$page->slug}) created successfully.");
    }

    /**
     * Show the form for editing the specified CMS page.
     */
    public function edit(string $id)
    {
        $page = Page::findOrFail($id);

        return view('admin.pages.edit', [
            'page' => $page,
        ]);
    }

    /**
     * Update the specified CMS page in storage.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $page = Page::findOrFail($id);

        // Sanitize slug
        if ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->input('slug'))]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('pages', 'slug')->ignore($page->id),
            ],
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published',
            'show_in_navigation' => 'nullable|boolean',
            'navigation_label' => 'nullable|string|max:100',
            'navigation_order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|url|max:255',
            'robots_directive' => 'nullable|string|max:50',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|url|max:255',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string|max:500',
            'twitter_image' => 'nullable|url|max:255',
        ]);

        $validated['show_in_navigation'] = $request->has('show_in_navigation');
        $validated['navigation_order'] = (int) ($validated['navigation_order'] ?? 0);
        $validated['robots_directive'] = $validated['robots_directive'] ?? 'index, follow';

        $page->update($validated);
        app(\App\Services\Seo\SeoService::class)->clearCache();

        return redirect()->route('admin.pages.index')
            ->with('success', "Page '{$page->title}' updated successfully.");
    }

    /**
     * Remove the specified CMS page from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $page = Page::findOrFail($id);
        $title = $page->title;
        $page->delete();
        app(\App\Services\Seo\SeoService::class)->clearCache();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Page '{$title}' deleted successfully.",
            ]);
        }

        return redirect()->route('admin.pages.index')
            ->with('success', "Page '{$title}' deleted successfully.");
    }

    /**
     * Toggle page published/draft status.
     */
    public function toggleStatus(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $page = Page::findOrFail($id);
        $page->status = ($page->status === 'published') ? 'draft' : 'published';
        $page->save();
        app(\App\Services\Seo\SeoService::class)->clearCache();

        $statusLabel = ucfirst($page->status);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Page '{$page->title}' is now {$statusLabel}.",
                'status' => $page->status,
            ]);
        }

        return redirect()->back()
            ->with('success', "Page '{$page->title}' status changed to {$statusLabel}.");
    }

    /**
     * Preview a page (accessible by Admin even if draft).
     */
    public function preview(string $id)
    {
        $page = Page::findOrFail($id);
        $navPages = Page::published()->inNavigation()->orderBy('navigation_order')->get();

        return view('pages.show', [
            'page' => $page,
            'navPages' => $navPages,
            'isAdminPreview' => true,
        ]);
    }

    /**
     * Upload an image for page content to Cloudflare R2 / public storage.
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
