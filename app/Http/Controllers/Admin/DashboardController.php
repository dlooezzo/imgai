<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Generation;
use App\Models\User;
use App\Models\VideoGeneration;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    /**
     * Display the Admin Overview Dashboard.
     */
    public function index(Request $request)
    {
        $today = now()->startOfDay();

        // 1. Statistics
        $totalUsers = User::count();
        $totalAdmins = User::where('role', 'admin')->count();
        $totalImages = Generation::count();
        $totalVideos = VideoGeneration::count();
        $totalGenerations = $totalImages + $totalVideos;

        $imagesToday = Generation::where('created_at', '>=', $today)->count();
        $videosToday = VideoGeneration::where('created_at', '>=', $today)->count();
        $generationsToday = $imagesToday + $videosToday;

        $activeImages = Generation::whereIn('status', ['starting', 'processing'])->count();
        $activeVideos = VideoGeneration::whereIn('status', ['starting', 'processing'])->count();
        $activeGenerations = $activeImages + $activeVideos;

        $failedImages = Generation::where('status', 'failed')->count();
        $failedVideos = VideoGeneration::where('status', 'failed')->count();
        $failedGenerations = $failedImages + $failedVideos;

        $r2Count = VideoGeneration::whereNotNull('video_path')
            ->where(function ($q) {
                $q->where('video_path', 'like', 'users/%')
                  ->orWhere('video_path', 'like', '%image-to-video%');
            })->count();

        $stats = [
            'total_users' => $totalUsers,
            'total_admins' => $totalAdmins,
            'total_images' => $totalImages,
            'total_videos' => $totalVideos,
            'total_generations' => $totalGenerations,
            'generations_today' => $generationsToday,
            'active_generations' => $activeGenerations,
            'failed_generations' => $failedGenerations,
            'r2_storage_count' => $r2Count,
        ];

        // 2. Recent Generations (Images & Videos unified)
        $recentImages = Generation::orderByDesc('created_at')->limit(8)->get()->map(function ($gen) {
            return [
                'id' => $gen->id,
                'type' => 'image',
                'type_label' => 'Image',
                'user_id' => $gen->user_id,
                'prompt' => $gen->prompt,
                'model' => 'Cinematic 2MP',
                'status' => $gen->status,
                'preview_url' => $gen->image_url,
                'created_at' => $gen->created_at,
            ];
        });

        $recentVideos = VideoGeneration::orderByDesc('created_at')->limit(8)->get()->map(function ($gen) {
            return [
                'id' => $gen->id,
                'type' => 'video',
                'type_label' => ($gen->generation_type === 'image-to-video') ? 'Image-to-Video' : 'Text-to-Video',
                'user_id' => $gen->user_id,
                'prompt' => $gen->prompt,
                'model' => ($gen->generation_type === 'image-to-video') ? 'Wan 2.2' : 'Hunyuan Video',
                'status' => $gen->status,
                'preview_url' => $gen->video_url,
                'created_at' => $gen->created_at,
            ];
        });

        $recentGenerations = $recentImages->concat($recentVideos)
            ->sortByDesc('created_at')
            ->take(10)
            ->values();

        // 3. User lookup cache for recent generations
        $userIds = $recentGenerations->pluck('user_id')->filter()->unique();
        $userMap = User::whereIn('id', $userIds)
            ->orWhereIn('email', $userIds)
            ->get()
            ->keyBy(function ($u) {
                return (string) $u->id;
            });

        // 4. System Health Summary
        $healthSummary = [
            'mysql' => ['status' => 'connected', 'label' => 'Connected'],
            'supabase' => ['status' => !empty(env('SUPABASE_URL')) ? 'configured' : 'unconfigured', 'label' => !empty(env('SUPABASE_URL')) ? 'Connected' : 'Missing Config'],
            'r2' => ['status' => !empty(env('R2_BUCKET')) ? 'configured' : 'unconfigured', 'label' => !empty(env('R2_BUCKET')) ? 'Configured' : 'Missing Bucket'],
            'api_market' => ['status' => !empty(env('API_MARKET_KEY')) ? 'configured' : 'unconfigured', 'label' => !empty(env('API_MARKET_KEY')) ? 'Ready' : 'No API Key'],
            'text_to_image' => ['status' => 'ready', 'label' => 'Ready'],
            'text_to_video' => ['status' => 'ready', 'label' => 'Ready'],
            'image_to_video' => ['status' => 'ready', 'label' => 'Ready'],
        ];

        // 5. Recent Activity Feed
        $recentUsers = User::orderByDesc('created_at')->limit(5)->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentGenerations' => $recentGenerations,
            'userMap' => $userMap,
            'healthSummary' => $healthSummary,
            'recentUsers' => $recentUsers,
        ]);
    }
}
