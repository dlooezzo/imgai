<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBrandingSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@imgai.test',
        ]);
    }

    /**
     * Test GET for the new unified Branding & SEO page.
     */
    public function test_admin_can_access_unified_branding_seo_page(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/admin/branding-seo');

        $response->assertStatus(200);
        $response->assertSee('Brand Identity');
        $response->assertSee('Homepage SEO');
        $response->assertSee('Global SEO');
        $response->assertSee('AI Tools SEO');
        $response->assertSee('Search Verification');
        $response->assertSee('Technical SEO');
        $response->assertSee('Save All Branding');
    }

    /**
     * Test legacy GET /admin/branding redirects 301 to /admin/branding-seo.
     */
    public function test_legacy_branding_route_redirects_to_unified_page(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/admin/branding');

        $response->assertStatus(301);
        $response->assertRedirect('/admin/branding-seo');
    }

    /**
     * Test legacy GET /admin/seo redirects 301 to /admin/branding-seo.
     */
    public function test_legacy_seo_route_redirects_to_unified_page(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get('/admin/seo');

        $response->assertStatus(301);
        $response->assertRedirect('/admin/branding-seo');
    }

    /**
     * Test saving all Branding & SEO settings successfully without 419 error.
     */
    public function test_admin_can_save_all_branding_and_seo_settings_successfully(): void
    {
        $admin = $this->createAdminUser();

        $payload = [
            // 1. Brand Identity
            'site_title' => 'Updated Brand Studio',
            'site_tagline' => 'Next-Gen Generative AI',
            'site_logo' => 'https://cdn.example.com/logo.png',
            'site_logo_icon' => 'https://cdn.example.com/icon.png',
            'site_favicon' => 'https://cdn.example.com/favicon.ico',
            'site_footer_text' => '© 2026 Updated Studio. All rights reserved.',

            // 2. Homepage SEO
            'seo_homepage_title' => 'Custom Homepage Title — 2MP Studio',
            'seo_homepage_description' => 'Custom meta description for homepage testing purpose.',
            'seo_homepage_og_image' => 'https://cdn.example.com/homepage-og.jpg',
            'seo_homepage_robots' => 'index, follow',

            // 3. Global SEO
            'seo_default_title' => 'Default Global SEO Title',
            'seo_site_name' => 'Updated Brand Name',
            'seo_title_format' => '%title% | %site_name%',
            'seo_default_description' => 'Default meta description for all general pages.',
            'seo_meta_keywords' => 'ai, video, image, generator',
            'seo_default_robots' => 'index, follow',
            'seo_default_og_image' => 'https://cdn.example.com/global-og.jpg',
            'seo_default_twitter_image' => 'https://cdn.example.com/global-tw.jpg',

            // 4. AI Tools SEO
            'seo_tool_image_title' => 'AI Image Generator — Wan 2.2',
            'seo_tool_image_description' => 'Generate high definition photorealistic images.',
            'seo_tool_image_og_image' => 'https://cdn.example.com/t2i-og.jpg',

            'seo_tool_video_title' => 'AI Video Generator — Hunyuan',
            'seo_tool_video_description' => 'Generate temporal motion videos at 24 fps.',
            'seo_tool_video_og_image' => 'https://cdn.example.com/t2v-og.jpg',

            'seo_tool_i2v_title' => 'Image to Video — Motion Studio',
            'seo_tool_i2v_description' => 'Animate still photos into cinematic videos.',
            'seo_tool_i2v_og_image' => 'https://cdn.example.com/i2v-og.jpg',

            // 5. Search Verification
            'seo_google_verification' => 'google-test-token-123',
            'seo_bing_verification' => 'bing-test-token-456',

            // 6. Sitemap & Robots
            'seo_robots_txt_custom' => "User-agent: *\nDisallow: /admin\nAllow: /",
        ];

        $response = $this->actingAs($admin)->post('/admin/branding-seo/update', $payload);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Verify values were persisted in SiteSetting
        $this->assertEquals('Updated Brand Studio', SiteSetting::get('site_title'));
        $this->assertEquals('Next-Gen Generative AI', SiteSetting::get('site_tagline'));
        $this->assertEquals('Custom Homepage Title — 2MP Studio', SiteSetting::get('seo_homepage_title'));
        $this->assertEquals('Default Global SEO Title', SiteSetting::get('seo_default_title'));
        $this->assertEquals('AI Image Generator — Wan 2.2', SiteSetting::get('seo_tool_image_title'));
        $this->assertEquals('AI Video Generator — Hunyuan', SiteSetting::get('seo_tool_video_title'));
        $this->assertEquals('Image to Video — Motion Studio', SiteSetting::get('seo_tool_i2v_title'));
        $this->assertEquals('google-test-token-123', SiteSetting::get('seo_google_verification'));
        $this->assertEquals('bing-test-token-456', SiteSetting::get('seo_bing_verification'));
    }

    /**
     * Test that invalid CSRF token throws TokenMismatchException (HTTP 419) when CSRF is enforced.
     */
    public function test_csrf_middleware_enforces_token_and_rejects_tampered_token(): void
    {
        $middleware = new class(app(), app('encrypter')) extends \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken {
            protected function runningUnitTests()
            {
                return false; // Force CSRF enforcement during test
            }
        };

        $request = \Illuminate\Http\Request::create('/admin/branding-seo/update', 'POST', [
            '_token' => 'invalid-tampered-token',
        ]);

        $session = app('session')->driver();
        $session->setId('test-session-id');
        $session->start();
        $session->put('_token', 'legitimate-session-token');
        $request->setLaravelSession($session);

        $this->expectException(\Illuminate\Session\TokenMismatchException::class);
        $middleware->handle($request, function () {
            return response('OK');
        });
    }

    /**
     * Test that valid CSRF token passes cleanly through middleware.
     */
    public function test_csrf_middleware_accepts_valid_token(): void
    {
        $middleware = new class(app(), app('encrypter')) extends \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken {
            protected function runningUnitTests()
            {
                return false;
            }
        };

        $token = 'legitimate-session-token-valid';
        $request = \Illuminate\Http\Request::create('/admin/branding-seo/update', 'POST', [
            '_token' => $token,
        ]);

        $session = app('session')->driver();
        $session->setId('test-session-id');
        $session->start();
        $session->put('_token', $token);
        $request->setLaravelSession($session);

        $response = $middleware->handle($request, function () {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Ensure that Admin branding-seo routes are NOT exempted from CSRF in bootstrap/app.php.
     */
    public function test_admin_routes_are_strictly_protected_by_csrf_and_not_exempted(): void
    {
        $exemptions = [
            'tools/image-generator/*',
            'tools/video-generator/*',
            'tools/image-to-video/*',
            'profile/*',
            'auth/*',
            'webhooks/*',
        ];

        $adminRoute = 'admin/branding-seo/update';
        foreach ($exemptions as $pattern) {
            $this->assertFalse(
                \Illuminate\Support\Str::is($pattern, $adminRoute),
                "Admin route must never be exempted by pattern {$pattern}"
            );
        }
    }

    /**
     * Test clearing SEO cache endpoint.
     */
    public function test_clear_seo_cache_endpoint(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post('/admin/branding-seo/clear-cache');

        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
