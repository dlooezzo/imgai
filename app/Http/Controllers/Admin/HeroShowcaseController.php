<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HeroShowcaseController extends Controller
{
    /**
     * Display Hero Showcase video management page.
     */
    public function index(Request $request)
    {
        $currentVideoUrl = SiteSetting::get('hero_showcase_video_url', '');
        $currentVideoPath = SiteSetting::get('hero_showcase_video_path', '');

        // Resolve local asset URL if relative path
        if (empty($currentVideoUrl) && !empty($currentVideoPath)) {
            $currentVideoUrl = asset('storage/' . $currentVideoPath);
        }

        $currentVideoTitle = SiteSetting::get('hero_showcase_title', 'Neural Frame Synthesis');
        $currentVideoCaption = SiteSetting::get('hero_showcase_caption', 'Spatial Diffusion • Volumetric Lighting • Temporal Consistency');
        $currentVideoScale = (int) SiteSetting::get('hero_showcase_video_scale', 200);
        $updatedAt = SiteSetting::where('key', 'hero_showcase_video_url')->first()?->updated_at;

        return view('admin.hero-showcase.index', [
            'currentVideoUrl' => $currentVideoUrl,
            'currentVideoTitle' => $currentVideoTitle,
            'currentVideoCaption' => $currentVideoCaption,
            'currentVideoScale' => $currentVideoScale,
            'updatedAt' => $updatedAt,
        ]);
    }

    /**
     * Upload and publish a new Hero Showcase video.
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'video_file' => 'nullable|file|mimes:mp4,webm,mov,ogg,m4v,quicktime|max:102400', // up to 100MB
            'video_url' => 'nullable|url',
            'video_scale' => 'nullable|integer|min:50|max:300',
            'title' => 'nullable|string|max:150',
            'caption' => 'nullable|string|max:255',
        ]);

        $publishedUrl = null;

        try {
            // 1. If file uploaded
            if ($request->hasFile('video_file') && $request->file('video_file')->isValid()) {
                $file = $request->file('video_file');
                $extension = strtolower($file->getClientOriginalExtension()) ?: 'mp4';
                $filename = 'hero_showcase_' . time() . '_' . Str::random(8) . '.' . $extension;

                $hasR2 = !empty(config('filesystems.disks.r2.key')) && !empty(config('filesystems.disks.r2.bucket'));

                if ($hasR2) {
                    $r2Path = "showcase/{$filename}";
                    $contents = file_get_contents($file->getRealPath());
                    Storage::disk('r2')->put($r2Path, $contents, 'public');
                    
                    $r2PublicUrl = rtrim((string) config('filesystems.disks.r2.url', env('R2_PUBLIC_URL', '')), '/');
                    $publishedUrl = $r2PublicUrl ? ($r2PublicUrl . '/' . $r2Path) : Storage::disk('r2')->url($r2Path);
                    SiteSetting::set('hero_showcase_video_path', $r2Path);
                } else {
                    // Store on public disk
                    $path = $file->storeAs('showcase', $filename, 'public');
                    $baseUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
                    $publishedUrl = $baseUrl . '/storage/' . $path;

                    // Delete previous custom uploaded file if stored locally
                    $oldPath = SiteSetting::get('hero_showcase_video_path');
                    if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }

                    SiteSetting::set('hero_showcase_video_path', $path);
                }
            } elseif ($request->filled('video_url')) {
                $publishedUrl = trim($request->input('video_url'));
            }

            if ($publishedUrl) {
                SiteSetting::set('hero_showcase_video_url', $publishedUrl);
            }

            if ($request->has('title')) {
                SiteSetting::set('hero_showcase_title', $request->input('title') ?: 'Neural Frame Synthesis');
            }

            if ($request->has('caption')) {
                SiteSetting::set('hero_showcase_caption', $request->input('caption') ?: 'Spatial Diffusion • Volumetric Lighting • Temporal Consistency');
            }

            if ($request->filled('video_scale')) {
                SiteSetting::set('hero_showcase_video_scale', (int) $request->input('video_scale'));
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hero showcase video and settings successfully saved!',
                    'video_url' => SiteSetting::get('hero_showcase_video_url'),
                    'video_scale' => (int) SiteSetting::get('hero_showcase_video_scale', 200),
                ]);
            }

            return redirect()->back()->with('success', 'Hero showcase video successfully updated and published to the landing page!');
        } catch (Exception $e) {
            Log::error('Failed to update hero showcase video: ' . $e->getMessage());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to save showcase video: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error updating showcase video: ' . $e->getMessage());
        }
    }

    /**
     * Update video display scale setting directly.
     */
    public function updateScale(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'video_scale' => 'required|integer|min:50|max:300',
        ]);

        $scale = (int) $request->input('video_scale');
        SiteSetting::set('hero_showcase_video_scale', $scale);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Hero video size updated to {$scale}%!",
                'video_scale' => $scale,
                'calculated_max_width' => round(420 * ($scale / 100)),
            ]);
        }

        return redirect()->back()->with('success', "Hero video size updated to {$scale}%!");
    }

    /**
     * Reset to default procedural animation.
     */
    public function reset(): RedirectResponse
    {
        // Delete uploaded file if stored locally
        $oldPath = SiteSetting::get('hero_showcase_video_path');
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        SiteSetting::set('hero_showcase_video_url', '');
        SiteSetting::set('hero_showcase_video_path', '');
        SiteSetting::set('hero_showcase_title', 'Neural Frame Synthesis');
        SiteSetting::set('hero_showcase_caption', 'Spatial Diffusion • Volumetric Lighting • Temporal Consistency');
        SiteSetting::set('hero_showcase_video_scale', 200);

        return redirect()->back()->with('success', 'Hero showcase reset to default procedural animation and standard size.');
    }
}
