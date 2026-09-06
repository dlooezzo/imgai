<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Generation;
use App\Models\User;
use App\Models\VideoGeneration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class GenerationController extends Controller
{
    /**
     * Display a unified list of all AI generations (Images and Videos).
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $type = $request->input('type'); // 'image', 'video', or null
        $status = $request->input('status'); // 'succeeded', 'processing', 'starting', 'failed', 'cancelled'

        $generations = collect();

        // 1. Query Images if applicable
        if (!$type || $type === 'image') {
            $imgQuery = Generation::query();

            if ($search !== '') {
                $imgQuery->where(function ($q) use ($search) {
                    $q->where('prompt', 'like', "%{$search}%")
                      ->orWhere('user_id', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%")
                      ->orWhere('prediction_id', 'like', "%{$search}%");
                });
            }

            if ($status) {
                $imgQuery->where('status', $status);
            }

            $images = $imgQuery->orderByDesc('created_at')->limit(100)->get()->map(function ($img) {
                return [
                    'id' => $img->id,
                    'type' => 'image',
                    'type_label' => 'Text-to-Image',
                    'user_id' => $img->user_id,
                    'prediction_id' => $img->prediction_id,
                    'prompt' => $img->prompt,
                    'model' => 'Cinematic 2MP',
                    'status' => $img->status,
                    'preview_url' => $img->image_url,
                    'aspect_ratio' => $img->aspect_ratio,
                    'error_message' => $img->error_message,
                    'created_at' => $img->created_at,
                    'raw' => $img,
                ];
            });

            $generations = $generations->concat($images);
        }

        // 2. Query Videos if applicable
        if (!$type || $type === 'video') {
            $vidQuery = VideoGeneration::query();

            if ($search !== '') {
                $vidQuery->where(function ($q) use ($search) {
                    $q->where('prompt', 'like', "%{$search}%")
                      ->orWhere('user_id', 'like', "%{$search}%")
                      ->orWhere('id', 'like', "%{$search}%")
                      ->orWhere('prediction_id', 'like', "%{$search}%");
                });
            }

            if ($status) {
                $vidQuery->where('status', $status);
            }

            $videos = $vidQuery->orderByDesc('created_at')->limit(100)->get()->map(function ($vid) {
                return [
                    'id' => $vid->id,
                    'type' => 'video',
                    'type_label' => ($vid->generation_type === 'image-to-video') ? 'Image-to-Video' : 'Text-to-Video',
                    'user_id' => $vid->user_id,
                    'prediction_id' => $vid->prediction_id,
                    'prompt' => $vid->prompt,
                    'model' => ($vid->generation_type === 'image-to-video') ? 'Wan 2.2' : 'Hunyuan Video',
                    'status' => $vid->status,
                    'preview_url' => $vid->video_url,
                    'aspect_ratio' => $vid->aspect_ratio,
                    'error_message' => $vid->error_message,
                    'created_at' => $vid->created_at,
                    'raw' => $vid,
                ];
            });

            $generations = $generations->concat($videos);
        }

        // Sort unified collection by created_at desc
        $sortedGenerations = $generations->sortByDesc('created_at')->values();

        // Paginate collection manually
        $page = (int) $request->input('page', 1);
        $perPage = 20;
        $items = $sortedGenerations->slice(($page - 1) * $perPage, $perPage)->values();
        $paginatedGenerations = new LengthAwarePaginator(
            $items,
            $sortedGenerations->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Fetch User models for these generations
        $userIds = $items->pluck('user_id')->filter()->unique();
        $userMap = User::whereIn('id', $userIds)
            ->orWhereIn('email', $userIds)
            ->get()
            ->keyBy(function ($u) {
                return (string) $u->id;
            });

        // Summary Statistics
        $totalImages = Generation::count();
        $totalVideos = VideoGeneration::count();
        $totalGenerations = $totalImages + $totalVideos;
        $succeededGenerations = Generation::where('status', 'succeeded')->count() + VideoGeneration::where('status', 'succeeded')->count();
        $failedGenerations = Generation::where('status', 'failed')->count() + VideoGeneration::where('status', 'failed')->count();

        return view('admin.generations.index', [
            'generations' => $paginatedGenerations,
            'userMap' => $userMap,
            'search' => $search,
            'type' => $type,
            'status' => $status,
            'totalGenerations' => $totalGenerations,
            'totalImages' => $totalImages,
            'totalVideos' => $totalVideos,
            'succeededGenerations' => $succeededGenerations,
            'failedGenerations' => $failedGenerations,
        ]);
    }

    /**
     * Remove a generation from the system.
     */
    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $type = $request->input('type');

        $deleted = false;

        if ($type === 'image' || !$type) {
            $image = Generation::find($id);
            if ($image) {
                $image->delete(); // Model booted event cleans up storage
                $deleted = true;
            }
        }

        if (!$deleted && ($type === 'video' || !$type)) {
            $video = VideoGeneration::find($id);
            if ($video) {
                $video->delete(); // Model booted event cleans up R2 & local storage
                $deleted = true;
            }
        }

        if (!$deleted) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Generation record not found.'], 404);
            }
            return redirect()->back()->with('error', 'Generation record not found.');
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Generation deleted successfully.']);
        }

        return redirect()->back()->with('success', 'Generation deleted successfully.');
    }
}
