<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicPageController extends Controller
{
    /**
     * Display a published CMS page.
     */
    public function show(Request $request, string $slug)
    {
        $page = Page::where('slug', $slug)->first();

        if (!$page) {
            abort(404, 'Page not found.');
        }

        // Check publishing rules: only published pages are public
        if ($page->status !== 'published') {
            $isAdmin = (Auth::check() && Auth::user()->isAdmin());
            if (!$isAdmin) {
                abort(404, 'Page not found.');
            }
        }

        // Fetch dynamic navigation pages
        $navPages = Page::published()->inNavigation()->orderBy('navigation_order')->get();

        return view('pages.show', [
            'page' => $page,
            'navPages' => $navPages,
            'isAdminPreview' => ($page->status !== 'published'),
            'currentUser' => session('supabase_user'),
        ]);
    }
}
