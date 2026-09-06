<?php

namespace App\Services\Supabase;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseService
{
    protected ?string $url;
    protected ?string $anonKey;
    protected ?string $serviceKey;

    public function __construct()
    {
        $this->url = rtrim(config('services.supabase.url', env('SUPABASE_URL', '')), '/');
        $this->anonKey = config('services.supabase.anon_key', env('SUPABASE_ANON_KEY', env('SUPABASE_KEY', '')));
        $this->serviceKey = config('services.supabase.service_key', env('SUPABASE_SERVICE_ROLE_KEY', ''));
    }

    /**
     * Get the Supabase Project URL for frontend.
     */
    public function getUrl(): string
    {
        return $this->url ?? '';
    }

    /**
     * Get the Supabase Anon Public Key for frontend.
     */
    public function getAnonKey(): string
    {
        return $this->anonKey ?? '';
    }

    /**
     * Verify a Supabase access token (JWT) and retrieve user profile.
     */
    public function getUserFromToken(string $token): ?array
    {
        if (empty($this->url) || empty($this->anonKey)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'apikey' => $this->anonKey,
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(10)->get("{$this->url}/auth/v1/user");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Supabase token verification failed: ' . $response->body());
            return null;
        } catch (Exception $e) {
            Log::error('Supabase auth error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Synchronize a Supabase user into the local database.
     */
    public function syncUser(array $supabaseUser): ?User
    {
        if (empty($supabaseUser['id'])) {
            return null;
        }

        $email = $supabaseUser['email'] ?? ($supabaseUser['id'] . '@supabase.user');
        $name = $supabaseUser['user_metadata']['full_name']
            ?? $supabaseUser['user_metadata']['name']
            ?? explode('@', $email)[0];

        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => bcrypt(str()->random(32)), // Random password since Supabase manages auth
                'email_verified_at' => !empty($supabaseUser['email_confirmed_at']) ? now() : null,
            ]
        );
    }
}
