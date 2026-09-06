<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Supabase\SupabaseService;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureToolAuthenticated
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Handle an incoming request to ensure the user is authenticated.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if user is already authenticated via Laravel session or Auth guard
        if (session()->has('supabase_user_id') && !empty(session('supabase_user_id'))) {
            return $next($request);
        }

        if (Auth::check() && Auth::user() !== null) {
            return $next($request);
        }

        // 2. Check for Bearer token in Authorization header
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

                    try {
                        $localUser = $this->supabase->syncUser($userData);
                        if ($localUser) {
                            Auth::login($localUser);
                        }
                    } catch (Exception $syncEx) {
                        // ignore local sync failure if DB issue
                    }

                    return $next($request);
                }
            } catch (Exception $e) {
                // Token verification failed
            }
        }

        // 3. User is unauthenticated
        // For API / AJAX / JSON requests (or tool generate/mutate endpoints):
        if (
            $request->expectsJson() ||
            $request->ajax() ||
            $request->is('tools/*/generate*') ||
            $request->is('tools/*/cancel*') ||
            $request->is('tools/*/delete*') ||
            ($request->is('profile/*') && $request->isMethod('POST'))
        ) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required. Please sign in or register to use this tool.',
                'message' => 'Authentication required. Please sign in or register to use this tool.',
                'requires_auth' => true,
            ], 401);
        }

        // For standard browser GET requests to protected pages (like /profile or /library):
        return redirect()->route('tools.index', ['auth' => 'required']);
    }
}
