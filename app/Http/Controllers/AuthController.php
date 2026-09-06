<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Supabase\SupabaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Synchronize Supabase authentication session with Laravel.
     */
    public function syncSession(Request $request): JsonResponse
    {
        $accessToken = $request->input('access_token');
        $supabaseUser = $request->input('user');

        if (!$accessToken || !$supabaseUser) {
            return response()->json([
                'success' => false,
                'message' => 'Missing session credentials.',
            ], 400);
        }

        try {
            // Verify token if configured
            $verifiedUser = $this->supabase->getUserFromToken($accessToken);
            $userData = $verifiedUser ?? $supabaseUser;

            $userId = $userData['id'] ?? null;
            $email = $userData['email'] ?? null;

            if ($userId) {
                // Store in Laravel session
                session([
                    'supabase_token' => $accessToken,
                    'supabase_user_id' => $userId,
                    'supabase_user' => [
                        'id' => $userId,
                        'email' => $email,
                        'name' => $userData['user_metadata']['full_name'] ?? $userData['user_metadata']['name'] ?? explode('@', $email)[0],
                        'avatar' => $userData['user_metadata']['avatar_url'] ?? null,
                    ],
                ]);

                // Sync local user record if possible
                try {
                    $localUser = $this->supabase->syncUser($userData);
                    if ($localUser) {
                        Auth::login($localUser);
                    }
                } catch (Exception $dbEx) {
                    Log::info('Local user sync skipped: ' . $dbEx->getMessage());
                }

                return response()->json([
                    'success' => true,
                    'user' => session('supabase_user'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid user payload.',
            ], 422);
        } catch (Exception $e) {
            Log::error('Auth sync failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync session.',
            ], 500);
        }
    }

    /**
     * Log out the current user session.
     */
    public function logout(Request $request): JsonResponse
    {
        session()->forget(['supabase_token', 'supabase_user_id', 'supabase_user']);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
