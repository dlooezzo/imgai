<?php

namespace Tests\Feature;

use App\Models\Generation;
use App\Models\User;
use App\Models\VideoGeneration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test unauthenticated access to profile redirects to tools with auth param.
     */
    public function test_unauthenticated_user_is_redirected_from_profile(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect(route('tools.index', ['auth' => 'required']));
    }

    /**
     * Test profile page is accessible for authenticated user.
     */
    public function test_profile_page_is_accessible_for_authenticated_user(): void
    {
        $response = $this->withSession(['supabase_user_id' => 'user-123'])->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('User Dashboard');
        $response->assertSee('Personal Profile Information');
        $response->assertSee('Two-Factor Authentication');
    }

    /**
     * Test updating profile information.
     */
    public function test_profile_information_can_be_updated(): void
    {
        $response = $this->withSession(['supabase_user_id' => 'user-123'])->postJson('/profile/update', [
            'name' => 'Elena Rostova',
            'email' => 'elena@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => [
                'name' => 'Elena Rostova',
                'email' => 'elena@example.com',
            ],
        ]);
    }

    /**
     * Test password change validation.
     */
    public function test_password_change_validates_minimum_length_and_confirmation(): void
    {
        $response = $this->withSession(['supabase_user_id' => 'user-123'])->postJson('/profile/password', [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);

        $response2 = $this->postJson('/profile/password', [
            'password' => 'valid_password_123',
            'password_confirmation' => 'different_password',
        ]);

        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['password']);
    }

    /**
     * Test successful password change for authenticated user.
     */
    public function test_authenticated_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_secret_123'),
        ]);

        $response = $this->actingAs($user)->postJson('/profile/password', [
            'current_password' => 'old_secret_123',
            'password' => 'new_secret_456',
            'password_confirmation' => 'new_secret_456',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);

        $this->assertTrue(Hash::check('new_secret_456', $user->fresh()->password));
    }

    /**
     * Test toggling Two-Factor Authentication.
     */
    public function test_toggle_2fa(): void
    {
        $response = $this->withSession(['supabase_user_id' => 'user-123'])->postJson('/profile/2fa', [
            'enabled' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'two_factor_enabled' => true,
        ]);

        $response2 = $this->withSession(['supabase_user_id' => 'user-123'])->postJson('/profile/2fa', [
            'enabled' => false,
        ]);

        $response2->assertStatus(200);
        $response2->assertJson([
            'success' => true,
            'two_factor_enabled' => false,
        ]);
    }

    /**
     * Test clearing history removes media generations.
     */
    public function test_clear_generation_history(): void
    {
        $userId = 'test_user_uuid_123';

        Generation::create([
            'user_id' => $userId,
            'prompt' => 'Image to clear',
            'aspect_ratio' => '1:1',
            'status' => 'succeeded',
        ]);

        VideoGeneration::create([
            'user_id' => $userId,
            'prompt' => 'Video to clear',
            'aspect_ratio' => '16:9',
            'status' => 'succeeded',
        ]);

        $this->assertDatabaseCount('generations', 1);
        $this->assertDatabaseCount('video_generations', 1);

        $response = $this->withSession(['supabase_user_id' => $userId])
            ->postJson('/profile/clear-history', [
                'type' => 'all',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseCount('generations', 0);
        $this->assertDatabaseCount('video_generations', 0);
    }
}
