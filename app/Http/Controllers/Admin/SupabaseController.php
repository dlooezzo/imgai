<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Supabase\SupabaseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseController extends Controller
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Display Supabase Auth settings and status.
     */
    public function index(Request $request)
    {
        $url = SiteSetting::get('supabase_url', config('services.supabase.url', env('SUPABASE_URL', '')));
        $anonKey = SiteSetting::get('supabase_anon_key', config('services.supabase.anon_key', env('SUPABASE_ANON_KEY', env('SUPABASE_KEY', ''))));
        $serviceKey = SiteSetting::get('supabase_service_role_key', config('services.supabase.service_key', env('SUPABASE_SERVICE_ROLE_KEY', '')));

        $isConfigured = !empty($url) && !empty($anonKey);

        $syncedUsersCount = User::count();
        $adminsCount = User::where('role', 'admin')->count();

        return view('admin.supabase.index', [
            'url' => $url,
            'anonKey' => $anonKey,
            'serviceKey' => $serviceKey,
            'isConfigured' => $isConfigured,
            'syncedUsersCount' => $syncedUsersCount,
            'adminsCount' => $adminsCount,
        ]);
    }

    /**
     * Update and persist Supabase Project URL and API Keys.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'supabase_url' => 'nullable|string|max:255',
            'supabase_anon_key' => 'nullable|string|max:1000',
            'supabase_service_role_key' => 'nullable|string|max:1000',
        ]);

        if (isset($validated['supabase_url'])) {
            $url = rtrim(trim($validated['supabase_url']), '/');
            SiteSetting::set('supabase_url', $url);
            config(['services.supabase.url' => $url]);
        }

        if (isset($validated['supabase_anon_key'])) {
            $anon = trim($validated['supabase_anon_key']);
            SiteSetting::set('supabase_anon_key', $anon);
            config(['services.supabase.anon_key' => $anon]);
        }

        if (isset($validated['supabase_service_role_key'])) {
            $service = trim($validated['supabase_service_role_key']);
            SiteSetting::set('supabase_service_role_key', $service);
            config(['services.supabase.service_key' => $service]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Supabase Authentication settings successfully saved!',
            ]);
        }

        return redirect()->back()->with('success', 'Supabase Project URL and API Keys successfully updated and saved!');
    }

    /**
     * Test Supabase Auth service connection.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $url = SiteSetting::get('supabase_url', config('services.supabase.url', env('SUPABASE_URL', '')));
        $anonKey = SiteSetting::get('supabase_anon_key', config('services.supabase.anon_key', env('SUPABASE_ANON_KEY', env('SUPABASE_KEY', ''))));

        if (empty($url) || empty($anonKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Supabase URL or Anon Key is missing. Please input valid credentials and save.',
                'latency_ms' => 0,
            ], 422);
        }

        $startTime = microtime(true);

        try {
            // Probe Supabase Auth health endpoint
            $healthUrl = rtrim($url, '/') . '/auth/v1/health';
            $response = Http::withHeaders([
                'apikey' => $anonKey,
            ])->timeout(10)->get($healthUrl);

            $latency = round((microtime(true) - $startTime) * 1000, 2);

            $isHealthy = $response->successful() || $response->status() === 200;

            return response()->json([
                'success' => $isHealthy,
                'message' => $isHealthy ? 'Supabase Auth service is online and operational.' : "Supabase returned HTTP {$response->status()}.",
                'latency_ms' => $latency,
                'status_code' => $response->status(),
            ]);
        } catch (Exception $e) {
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            Log::error('Supabase test connection failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to reach Supabase: ' . $e->getMessage(),
                'latency_ms' => $latency,
            ], 500);
        }
    }
}
