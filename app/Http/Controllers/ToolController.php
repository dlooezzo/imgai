<?php

namespace App\Http\Controllers;

use App\Models\Generation;
use App\Models\VideoGeneration;
use App\Services\Supabase\SupabaseService;
use Illuminate\Http\Request;

class ToolController extends Controller
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Redirect root URL directly to Studio Overview.
     */
    public function rootRedirect()
    {
        return redirect()->route('tools.overview');
    }

    /**
     * Display the Studio Overview introduction view.
     */
    public function overview(Request $request)
    {
        $supabaseConfig = [
            'url' => $this->supabase->getUrl(),
            'anonKey' => $this->supabase->getAnonKey(),
        ];

        $heroVideoUrl = \App\Models\SiteSetting::get('hero_showcase_video_url');
        $heroVideoPath = \App\Models\SiteSetting::get('hero_showcase_video_path');

        if (empty($heroVideoUrl) && !empty($heroVideoPath)) {
            $heroVideoUrl = asset('storage/' . $heroVideoPath);
        }

        // Always ensure a real, working showcase video is available
        if (empty($heroVideoUrl)) {
            $heroVideoUrl = asset('videos/hero-showcase.mp4');
        } elseif (request()->isSecure() && str_starts_with($heroVideoUrl, 'http://')) {
            // Prevent mixed content blocking on HTTPS
            $heroVideoUrl = 'https://' . substr($heroVideoUrl, 7);
        }

        $heroVideoPoster = asset('videos/hero-showcase-poster.jpg');
        $heroVideoTitle = \App\Models\SiteSetting::get('hero_showcase_title', 'Neural Frame Synthesis');
        $heroVideoCaption = \App\Models\SiteSetting::get('hero_showcase_caption', 'Spatial Diffusion • Volumetric Lighting • Temporal Consistency');
        $heroVideoScale = (int) \App\Models\SiteSetting::get('hero_showcase_video_scale', 200);

        return view('tools.index', [
            'activeTool' => 'overview',
            'supabaseConfig' => $supabaseConfig,
            'currentUser' => session('supabase_user'),
            'heroVideoUrl' => $heroVideoUrl,
            'heroVideoPoster' => $heroVideoPoster,
            'heroVideoTitle' => $heroVideoTitle,
            'heroVideoCaption' => $heroVideoCaption,
            'heroVideoScale' => $heroVideoScale,
        ]);
    }

    /**
     * Display the Image Generator interface.
     */
    public function index(Request $request)
    {
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : null);

        // Retrieve recent non-expired image generations for authenticated user only
        $generations = $userId ? Generation::where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->limit(24)
            ->get() : collect([]);

        $supabaseConfig = [
            'url' => $this->supabase->getUrl(),
            'anonKey' => $this->supabase->getAnonKey(),
        ];

        $toolArticle = \App\Models\ToolArticle::where('tool_key', 'image-generator')
            ->where('status', 'published')
            ->first();

        return view('tools.index', [
            'activeTool' => 'image-generator',
            'generations' => $generations,
            'toolArticle' => $toolArticle,
            'supabaseConfig' => $supabaseConfig,
            'currentUser' => session('supabase_user'),
        ]);
    }

    /**
     * Display the Pure Text-to-Video Generator interface.
     */
    public function videoIndex(Request $request)
    {
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : null);

        // Retrieve recent non-expired video generations for authenticated user only
        $videoGenerations = $userId ? VideoGeneration::where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->limit(24)
            ->get() : collect([]);

        $supabaseConfig = [
            'url' => $this->supabase->getUrl(),
            'anonKey' => $this->supabase->getAnonKey(),
        ];

        $toolArticle = \App\Models\ToolArticle::where('tool_key', 'video-generator')
            ->where('status', 'published')
            ->first();

        return view('tools.index', [
            'activeTool' => 'video-generator',
            'videoGenerations' => $videoGenerations,
            'toolArticle' => $toolArticle,
            'supabaseConfig' => $supabaseConfig,
            'currentUser' => session('supabase_user'),
        ]);
    }

    /**
     * Display the Image-to-Video Generator interface.
     */
    public function imageToVideoIndex(Request $request)
    {
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : null);

        // Retrieve recent non-expired image-to-video generations for authenticated user only
        $generations = $userId ? VideoGeneration::where('generation_type', 'image-to-video')
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->limit(24)
            ->get() : collect([]);

        $supabaseConfig = [
            'url' => $this->supabase->getUrl(),
            'anonKey' => $this->supabase->getAnonKey(),
        ];

        $toolArticle = \App\Models\ToolArticle::where('tool_key', 'image-to-video')
            ->where('status', 'published')
            ->first();

        return view('tools.index', [
            'activeTool' => 'image-to-video',
            'videoGenerations' => $generations,
            'toolArticle' => $toolArticle,
            'supabaseConfig' => $supabaseConfig,
            'currentUser' => session('supabase_user'),
        ]);
    }
}
