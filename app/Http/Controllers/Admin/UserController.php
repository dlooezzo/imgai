<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Generation;
use App\Models\User;
use App\Models\VideoGeneration;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of all users.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $role = $request->input('role');
        $status = $request->input('status');

        $query = User::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        if ($role && in_array($role, ['admin', 'user'], true)) {
            $query->where('role', $role);
        }

        if ($status === 'verified') {
            $query->whereNotNull('email_verified_at');
        } elseif ($status === 'unverified') {
            $query->whereNull('email_verified_at');
        }

        $users = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        // Attach counts for each user
        $users->getCollection()->transform(function ($user) {
            $user->images_count = Generation::where('user_id', (string) $user->id)
                ->orWhere('user_id', $user->email)
                ->count();
            $user->videos_count = VideoGeneration::where('user_id', (string) $user->id)
                ->orWhere('user_id', $user->email)
                ->count();
            $user->total_generations_count = $user->images_count + $user->videos_count;
            return $user;
        });

        $totalUsersCount = User::count();
        $totalAdminsCount = User::where('role', 'admin')->count();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'totalUsersCount' => $totalUsersCount,
            'totalAdminsCount' => $totalAdminsCount,
        ]);
    }

    /**
     * Display detailed user information.
     */
    public function show(string $id)
    {
        $user = User::where('id', $id)->orWhere('email', $id)->firstOrFail();

        $userIdVariants = [(string) $user->id, $user->email];

        // Fetch User's image generations
        $imageGenerations = Generation::whereIn('user_id', $userIdVariants)
            ->orderByDesc('created_at')
            ->paginate(12, ['*'], 'images_page');

        // Fetch User's video generations
        $videoGenerations = VideoGeneration::whereIn('user_id', $userIdVariants)
            ->orderByDesc('created_at')
            ->paginate(12, ['*'], 'videos_page');

        // Compute generation metrics
        $totalImages = Generation::whereIn('user_id', $userIdVariants)->count();
        $successfulImages = Generation::whereIn('user_id', $userIdVariants)->where('status', 'succeeded')->count();
        $failedImages = Generation::whereIn('user_id', $userIdVariants)->where('status', 'failed')->count();

        $totalVideos = VideoGeneration::whereIn('user_id', $userIdVariants)->count();
        $successfulVideos = VideoGeneration::whereIn('user_id', $userIdVariants)->where('status', 'succeeded')->count();
        $failedVideos = VideoGeneration::whereIn('user_id', $userIdVariants)->where('status', 'failed')->count();

        $stats = [
            'total_images' => $totalImages,
            'successful_images' => $successfulImages,
            'failed_images' => $failedImages,
            'total_videos' => $totalVideos,
            'successful_videos' => $successfulVideos,
            'failed_videos' => $failedVideos,
            'total_creations' => $totalImages + $totalVideos,
        ];

        // Fetch user subscription and billing records
        $subscription = $user->subscriptions()->with('pricingPlan')->latest()->first();
        $creditTransactions = $user->creditTransactions()->latest()->take(10)->get();
        $transactions = $user->transactions()->with('pricingPlan')->latest()->take(5)->get();

        return view('admin.users.show', [
            'user' => $user,
            'stats' => $stats,
            'subscription' => $subscription,
            'creditTransactions' => $creditTransactions,
            'transactions' => $transactions,
            'imageGenerations' => $imageGenerations,
            'videoGenerations' => $videoGenerations,
        ]);
    }
}

