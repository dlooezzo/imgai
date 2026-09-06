<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeroShowcaseTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com',
        ]);
    }

    public function test_overview_renders_with_default_2x_video_scale_and_frameless_wrapper(): void
    {
        SiteSetting::set('hero_showcase_video_url', '/storage/hero/demo.mp4');

        $response = $this->get('/tools/overview');

        $response->assertStatus(200);
        // Default scale is 200%, max width is 840px (420 * 2)
        $response->assertSee('studio-hero-media-wrapper');
        $response->assertSee('--hero-video-max-width: 840px');
        $response->assertSee('hero-video-element');
        // Ambient backlight and boxed styling should not be present
        $response->assertDontSee('hero-video-ambient-backlight');
    }

    public function test_admin_can_update_video_scale_via_dedicated_endpoint(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.hero-showcase.scale'), [
                'video_scale' => 250,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'video_scale' => 250,
            'calculated_max_width' => 1050,
        ]);

        $this->assertEquals(250, (int) SiteSetting::get('hero_showcase_video_scale'));

        // Overview now renders with 1050px max width
        $overview = $this->get('/tools/overview');
        $overview->assertStatus(200);
        $overview->assertSee('--hero-video-max-width: 1050px');
    }

    public function test_admin_cannot_set_invalid_video_scale(): void
    {
        $admin = $this->createAdminUser();

        // Below 50%
        $response = $this->actingAs($admin)
            ->postJson(route('admin.hero-showcase.scale'), [
                'video_scale' => 20,
            ]);
        $response->assertStatus(422);

        // Above 300%
        $response = $this->actingAs($admin)
            ->postJson(route('admin.hero-showcase.scale'), [
                'video_scale' => 450,
            ]);
        $response->assertStatus(422);
    }

    public function test_non_admin_cannot_update_video_scale(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->postJson(route('admin.hero-showcase.scale'), [
                'video_scale' => 180,
            ]);

        $response->assertStatus(403);
    }
}
