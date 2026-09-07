<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MobileDrawerGlobalLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_drawer_is_isolated_at_root_and_closed_by_default()
    {
        $layoutContent = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $cssContent = file_get_contents(public_path('css/app.css'));

        // 1. [x-cloak] must be defined to prevent any flash of drawer content
        $this->assertStringContainsString('[x-cloak]', $cssContent);
        $this->assertMatchesRegularExpression('/\[x-cloak\]\s*\{[^}]*display:\s*none\s*!important/s', $cssContent);

        // 2. mobileMenuOpen initialized to false in app-shell
        $this->assertStringContainsString('x-data="{ mobileMenuOpen: false }"', $layoutContent);

        // 3. Drawer is outside header (header closes before drawer opens)
        $headerPos = strpos($layoutContent, '</header>');
        $drawerPos = strpos($layoutContent, 'class="mobile-drawer-backdrop"');
        $this->assertNotFalse($headerPos);
        $this->assertNotFalse($drawerPos);
        $this->assertGreaterThan($headerPos, $drawerPos, 'Mobile drawer backdrop must be placed outside and after </header>');

        // 4. Drawer has x-show="mobileMenuOpen" and x-cloak
        $this->assertMatchesRegularExpression('/class="mobile-drawer-backdrop"[^>]*x-show="mobileMenuOpen"[^>]*x-cloak/s', $layoutContent);

        // 5. CSS must NOT force display: flex !important on drawer backdrop (which breaks Alpine x-show)
        $this->assertDoesNotMatchRegularExpression('/\.mobile-drawer-backdrop\s*\{[^}]*display:\s*flex\s*!important/s', $cssContent);

        // 6. Desktop isolates drawer completely
        $this->assertMatchesRegularExpression('/@media[^{]*min-width:\s*769px.*?\.mobile-drawer-backdrop\s*\{[^}]*display:\s*none\s*!important/s', $cssContent);

        // 7. Sign Out button is strictly inside drawer footer
        $footerPos = strpos($layoutContent, 'class="mobile-drawer-footer"');
        $logoutPos = strpos($layoutContent, 'class="mobile-drawer-logout-btn"');
        $this->assertNotFalse($footerPos);
        $this->assertNotFalse($logoutPos);
        $this->assertGreaterThan($footerPos, $logoutPos, 'Sign Out button must be inside mobile-drawer-footer');
    }

    public function test_all_major_pages_render_with_app_layout()
    {
        $user = User::factory()->create([
            'credit_balance' => 100,
        ]);

        $routes = [
            'overview' => route('tools.overview'),
            'image' => route('tools.image.index'),
            'video' => route('tools.video.index'),
            'i2v' => route('tools.image-to-video.index'),
            'pricing' => route('pricing'),
            'profile' => route('profile.index'),
        ];

        foreach ($routes as $name => $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertStatus(200);
            $content = $response->getContent();

            // Verify header is clean
            $this->assertStringContainsString('class="app-header"', $content);
            $this->assertStringContainsString('class="mobile-header-menu-btn"', $content);

            // Verify drawer exists as root overlay and contains Sign Out inside its footer
            $this->assertStringContainsString('class="mobile-drawer-backdrop"', $content);
            $this->assertStringContainsString('class="mobile-drawer-logout-btn"', $content);

            // Verify drawer is placed AFTER </header>
            $hPos = strpos($content, '</header>');
            $dPos = strpos($content, 'class="mobile-drawer-backdrop"');
            $this->assertGreaterThan($hPos, $dPos, "Route {$name} must place drawer after </header>");
        }
    }
}
