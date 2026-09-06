<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VideoGeneration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    /**
     * Display a dedicated video management gallery.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $type = $request->input('type'); // 'text-to-video', 'image-to-video'
        $status = $request->input('status');

        $query = VideoGeneration::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('prompt', 'like', "%{$search}%")
                  ->orWhere('user_id', 'like', "%{$search}%")
                  ->orWhere('prediction_id', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->where('generation_type', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $videos = $query->orderByDesc('created_at')->paginate(16)->withQueryString();

        // User mapping
        $userIds = $videos->pluck('user_id')->filter()->unique();
        $userMap = User::whereIn('id', $userIds)
            ->orWhereIn('email', $userIds)
            ->get()
            ->keyBy(function ($u) {
                return (string) $u->id;
            });

        $totalVideos = VideoGeneration::count();
        $textToVideos = VideoGeneration::where('generation_type', 'text-to-video')->count();
        $imageToVideos = VideoGeneration::where('generation_type', 'image-to-video')->count();
        $succeededVideos = VideoGeneration::where('status', 'succeeded')->count();
        $processingVideos = VideoGeneration::whereIn('status', ['starting', 'processing'])->count();
        $failedVideos = VideoGeneration::where('status', 'failed')->count();

        return view('admin.videos.index', [
            'videos' => $videos,
            'userMap' => $userMap,
            'search' => $search,
            'type' => $type,
            'status' => $status,
            'totalVideos' => $totalVideos,
            'textToVideos' => $textToVideos,
            'imageToVideos' => $imageToVideos,
            'succeededVideos' => $succeededVideos,
            'processingVideos' => $processingVideos,
            'failedVideos' => $failedVideos,
        ]);
    }

    /**
     * Delete a video generation.
     */
    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $video = VideoGeneration::find($id);

        if (!$video) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Video not found.'], 404);
            }
            return redirect()->back()->with('error', 'Video not found.');
        }

        $video->delete(); // Model booted event deletes physical files from R2 and local disk

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Video deleted successfully.']);
        }

        return redirect()->back()->with('success', 'Video deleted successfully.');
    }
}
