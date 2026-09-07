<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiToolPagesCrystalCardAndVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_crystal_card_renders_on_all_ai_tool_pages(): void
    {
        SiteSetting::set('hero_showcase_video_url', '/storage/hero/demo.mp4');

        $toolRoutes = [
            '/tools/overview',
            '/tools/image-generator',
            '/tools/video-generator',
            '/tools/image-to-video',
        ];

        foreach ($toolRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);
            $response->assertSee('ai-premium-crystal-card');
            $response->assertSee('Unlock Premium AI Tools');
            $response->assertSee('crystal-svg');
            $response->assertSee('crystal-gem-body');
            $response->assertSee('crystal-orbit-1');
            $response->assertSee('crystal-orbit-2');
            $response->assertSee(route('pricing'));
        }
    }

    public function test_crystal_card_is_not_rendered_on_non_tool_pages(): void
    {
        // 1. Pricing
        $response = $this->get(route('pricing'));
        $response->assertStatus(200);
        $response->assertDontSee('ai-premium-crystal-card');
        $response->assertDontSee('Unlock Premium AI Tools');

        // 2. Profile (authenticated)
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('profile.index'));
        $response->assertStatus(200);
        $response->assertDontSee('ai-premium-crystal-card');

        // 3. Welcome
        $response = $this->get(route('welcome'));
        $response->assertStatus(200);
        $response->assertDontSee('ai-premium-crystal-card');

        // 4. Library (authenticated)
        $response = $this->actingAs($user)->get(route('library.index'));
        $response->assertStatus(200);
        $response->assertDontSee('ai-premium-crystal-card');

        // 5. Admin Dashboard (authenticated admin)
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin_test@imgai.test']);
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertDontSee('ai-premium-crystal-card');
    }

    public function test_studio_hero_media_wrapper_and_responsive_video_elements(): void
    {
        SiteSetting::set('hero_showcase_video_url', '/storage/hero/sample.mp4');

        $response = $this->get('/tools/overview');
        $response->assertStatus(200);

        // Core wrapper and elements
        $response->assertSee('studio-hero-media-wrapper');
        $response->assertSee('hero-video-container');
        $response->assertSee('hero-video-element');

        // Mobile playsinline and autoplay compliance
        $response->assertSee('webkit-playsinline');
        $response->assertSee('playsinline');
        $response->assertSee('muted');
        $response->assertSee('autoplay');
    }
}
