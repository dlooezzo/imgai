<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PersistentLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that user syncSession sets the remember cookie.
     */
    public function test_sync_session_sets_remember_token_and_cookie(): void
    {
        $userData = [
            'id' => 'sb_user_uuid_123',
            'email' => 'persist_user@example.com',
            'user_metadata' => [
                'full_name' => 'Persistent User',
            ],
        ];

        $response = $this->postJson('/auth/sync-session', [
            'access_token' => 'mock_access_token_123',
            'user' => $userData,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Assert user was created/synced in DB
        $user = User::where('email', 'persist_user@example.com')->first();
        $this->assertNotNull($user);

        // Assert Auth::check() is true and remember token was created on user
        $this->assertTrue(Auth::check());
        $this->assertNotEmpty($user->fresh()->remember_token);

        // Assert remember_web cookie was queued in response
        $response->assertCookie(Auth::getRecallerName());
    }

    /**
     * Test that logout invalidates remember token and clears session.
     */
    public function test_logout_invalidates_session_and_remember_token(): void
    {
        $user = User::create([
            'name' => 'Logout User',
            'email' => 'logout_test@example.com',
            'password' => bcrypt('password123'),
            'remember_token' => 'initial_valid_token',
        ]);

        $this->actingAs($user);
        $this->assertTrue(Auth::check());

        $response = $this->postJson('/auth/logout');
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // User should be logged out
        $this->assertFalse(Auth::check());

        // Remember token should be cleared in DB
        $this->assertNull($user->fresh()->remember_token);
    }
}
