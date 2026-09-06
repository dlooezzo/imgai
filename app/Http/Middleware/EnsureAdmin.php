<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Supabase\SupabaseService;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Handle an incoming request to ensure the user is an authorized administrator.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $localUser = null;

        // 1. Check if user is authenticated via Laravel Auth guard
        if (Auth::check() && Auth::user() !== null) {
            $localUser = Auth::user();
        }

        // 2. Check if session has Supabase user credentials
        if (!$localUser && session()->has('supabase_user_id') && !empty(session('supabase_user_id'))) {
            $supabaseUser = session('supabase_user');
            $email = $supabaseUser['email'] ?? null;
            if ($email) {
                $localUser = User::where('email', $email)->first();
            }
            if (!$localUser && is_numeric(session('supabase_user_id'))) {
                $localUser = User::find(session('supabase_user_id'));
            }
        }

        // 3. Check for Bearer token in Authorization header
        if (!$localUser) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
                try {
                    $userData = $this->supabase->getUserFromToken($token);
                    if ($userData && !empty($userData['id'])) {
                        $userId = $userData['id'];
                        $email = $userData['email'] ?? null;

                        session([
                            'supabase_token' => $token,
                            'supabase_user_id' => $userId,
                            'supabase_user' => [
                                'id' => $userId,
                                'email' => $email,
                                'name' => $userData['user_metadata']['full_name'] ?? $userData['user_metadata']['name'] ?? explode('@', $email)[0],
                                'avatar' => $userData['user_metadata']['avatar_url'] ?? null,
                            ],
                        ]);

                        $localUser = $this->supabase->syncUser($userData);
                        if ($localUser) {
                            Auth::login($localUser);
                        }
                    }
                } catch (Exception $e) {
                    // Token verification error
                }
            }
        }

        // 4. If user is unauthenticated
        if (!$localUser) {
            if ($request->expectsJson() || $request->ajax() || $request->is('admin/api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authentication required. Please sign in with an administrator account.',
                    'message' => 'Authentication required. Please sign in with an administrator account.',
                    'requires_auth' => true,
                ], 401);
            }

            return redirect()->route('tools.index', ['auth' => 'required', 'return_to' => '/admin']);
        }

        // 5. Check if user has administrator role in MySQL
        if (!$localUser->isAdmin()) {
            if ($request->expectsJson() || $request->ajax() || $request->is('admin/api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Access denied. You do not have administrator privileges.',
                    'message' => 'Access denied. You do not have administrator privileges.',
                ], 403);
            }

            abort(403, 'Access denied. You do not have administrator privileges.');
        }

        // User is an authorized administrator
        return $next($request);
    }
}
