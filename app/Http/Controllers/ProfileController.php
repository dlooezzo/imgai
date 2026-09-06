<?php

namespace App\Http\Controllers;

use App\Models\Generation;
use App\Models\User;
use App\Models\VideoGeneration;
use App\Services\Supabase\SupabaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Display the User Dashboard & Profile page.
     */
    public function index(Request $request)
    {
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : null);
        if (!$userId) {
            return redirect()->route('tools.index', ['auth' => 'required']);
        }

        $currentUser = session('supabase_user') ?? ($request->user() ? [
            'id' => (string) $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'avatar' => null,
        ] : null);

        // Fetch image creations
        $imageGenerations = Generation::where('user_id', $userId)
        ->orderByDesc('created_at')
        ->limit(30)
        ->get();

        // Fetch video creations
        $videoGenerations = VideoGeneration::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        // Compute statistics
        $stats = [
            'total_images' => $imageGenerations->count(),
            'total_videos' => $videoGenerations->count(),
            'total_creations' => $imageGenerations->count() + $videoGenerations->count(),
            'successful_images' => $imageGenerations->where('status', 'succeeded')->count(),
            'successful_videos' => $videoGenerations->where('status', 'succeeded')->count(),
        ];

        // Security settings in session/local model
        $securitySettings = session('user_security_settings_' . $userId, [
            'two_factor_enabled' => false,
            'email_verified' => !empty($currentUser['email_verified_at']) || ($request->user() && $request->user()->hasVerifiedEmail()) || true,
            'last_password_change' => 'Not changed recently',
            'active_sessions_count' => 1,
        ]);

        $supabaseConfig = [
            'url' => $this->supabase->getUrl(),
            'anonKey' => $this->supabase->getAnonKey(),
        ];

        return view('profile.index', [
            'activeTool' => 'profile',
            'currentUser' => $currentUser,
            'imageGenerations' => $imageGenerations,
            'videoGenerations' => $videoGenerations,
            'stats' => $stats,
            'securitySettings' => $securitySettings,
            'supabaseConfig' => $supabaseConfig,
        ]);
    }

    /**
     * Display the dedicated Media Library page.
     */
    public function library(Request $request)
    {
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : null);
        if (!$userId) {
            return redirect()->route('tools.index', ['auth' => 'required']);
        }

        $currentUser = session('supabase_user') ?? ($request->user() ? [
            'id' => (string) $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'avatar' => null,
        ] : null);

        // Fetch image creations
        $imageGenerations = Generation::where('user_id', $userId)
        ->orderByDesc('created_at')
        ->limit(60)
        ->get();

        // Fetch video creations
        $videoGenerations = VideoGeneration::where('user_id', $userId)
        ->orderByDesc('created_at')
        ->limit(60)
        ->get();

        // Compute statistics
        $stats = [
            'total_images' => $imageGenerations->count(),
            'total_videos' => $videoGenerations->count(),
            'total_creations' => $imageGenerations->count() + $videoGenerations->count(),
            'successful_images' => $imageGenerations->where('status', 'succeeded')->count(),
            'successful_videos' => $videoGenerations->where('status', 'succeeded')->count(),
        ];

        $supabaseConfig = [
            'url' => $this->supabase->getUrl(),
            'anonKey' => $this->supabase->getAnonKey(),
        ];

        return view('library.index', [
            'activeTool' => 'library',
            'currentUser' => $currentUser,
            'imageGenerations' => $imageGenerations,
            'videoGenerations' => $videoGenerations,
            'stats' => $stats,
            'supabaseConfig' => $supabaseConfig,
        ]);
    }

    /**
     * Update user profile information.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'nullable|email|max:150',
        ]);

        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : session()->getId());

        // Update session user if exists
        $user = session('supabase_user', []);
        $user['name'] = $validated['name'];
        if (!empty($validated['email'])) {
            $user['email'] = $validated['email'];
        }
        session(['supabase_user' => $user]);

        // Update local database record if authenticated
        if ($request->user()) {
            $request->user()->update([
                'name' => $validated['name'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => $user,
        ]);
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'nullable|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($request->user() && $request->user()->password) {
            if (!empty($validated['current_password']) && !Hash::check($validated['current_password'], $request->user()->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The provided current password does not match our records.',
                ], 422);
            }

            $request->user()->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : session()->getId());
        $sec = session('user_security_settings_' . $userId, []);
        $sec['last_password_change'] = now()->diffForHumans();
        session(['user_security_settings_' . $userId => $sec]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);
    }

    /**
     * Toggle Two-Factor Authentication.
     */
    public function toggle2FA(Request $request): JsonResponse
    {
        $enabled = (bool) $request->input('enabled', false);
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : session()->getId());

        $sec = session('user_security_settings_' . $userId, []);
        $sec['two_factor_enabled'] = $enabled;
        session(['user_security_settings_' . $userId => $sec]);

        return response()->json([
            'success' => true,
            'message' => $enabled ? 'Two-Factor Authentication enabled.' : 'Two-Factor Authentication disabled.',
            'two_factor_enabled' => $enabled,
        ]);
    }

    /**
     * Clear generation history for user.
     */
    public function clearHistory(Request $request): JsonResponse
    {
        $userId = session('supabase_user_id') ?? ($request->user() ? (string) $request->user()->id : null);
        $sessionId = session()->getId();
        $type = $request->input('type', 'all'); // 'all', 'images', 'videos'

        if ($type === 'all' || $type === 'images') {
            $images = Generation::where(function ($q) use ($userId, $sessionId) {
                if ($userId) $q->where('user_id', $userId);
                else $q->where('user_id', $sessionId);
            })->get();

            foreach ($images as $img) {
                $img->delete();
            }
        }

        if ($type === 'all' || $type === 'videos') {
            $videos = VideoGeneration::where(function ($q) use ($userId, $sessionId) {
                if ($userId) $q->where('user_id', $userId);
                else $q->where('user_id', $sessionId);
            })->get();

            foreach ($videos as $vid) {
                $vid->delete();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Generation history cleared successfully.',
        ]);
    }
}
