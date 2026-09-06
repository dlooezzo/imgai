<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    /**
     * Display a dedicated image management gallery.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $aspectRatio = $request->input('aspect_ratio');

        $query = Generation::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('prompt', 'like', "%{$search}%")
                  ->orWhere('user_id', 'like', "%{$search}%")
                  ->orWhere('prediction_id', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($aspectRatio) {
            $query->where('aspect_ratio', $aspectRatio);
        }

        $images = $query->orderByDesc('created_at')->paginate(24)->withQueryString();

        // User mapping
        $userIds = $images->pluck('user_id')->filter()->unique();
        $userMap = User::whereIn('id', $userIds)
            ->orWhereIn('email', $userIds)
            ->get()
            ->keyBy(function ($u) {
                return (string) $u->id;
            });

        $totalImages = Generation::count();
        $succeededImages = Generation::where('status', 'succeeded')->count();
        $processingImages = Generation::whereIn('status', ['starting', 'processing'])->count();
        $failedImages = Generation::where('status', 'failed')->count();

        return view('admin.images.index', [
            'images' => $images,
            'userMap' => $userMap,
            'search' => $search,
            'status' => $status,
            'aspectRatio' => $aspectRatio,
            'totalImages' => $totalImages,
            'succeededImages' => $succeededImages,
            'processingImages' => $processingImages,
            'failedImages' => $failedImages,
        ]);
    }

    /**
     * Delete an image generation.
     */
    public function destroy(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $image = Generation::find($id);

        if (!$image) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Image not found.'], 404);
            }
            return redirect()->back()->with('error', 'Image not found.');
        }

        $image->delete(); // Model booted event deletes physical file

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }

        return redirect()->back()->with('success', 'Image deleted successfully.');
    }
}
