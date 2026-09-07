<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPurchaseRedirectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Pricing page renders with Paddle configuration, success modal, and redirect handler.
     */
    public function test_pricing_page_contains_paddle_checkout_and_success_redirect_elements(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('pricing'));

        $response->assertStatus(200);
        $response->assertSee('handleCheckoutSuccess');
        $response->assertSee('successUrl');
        $response->assertSee('Subscription Activated!');
        $response->assertSee('Continue to Dashboard');
        $response->assertSee(route('tools.overview'));
    }

    /**
     * Test 2: Welcome / Confirmation page renders with 3-second auto-redirect script and dashboard button.
     */
    public function test_welcome_page_renders_with_auto_redirect_and_dashboard_action(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('welcome'));

        $response->assertStatus(200);
        $response->assertSee('Welcome to the Studio!');
        $response->assertSee('Payment Completed Successfully');
        $response->assertSee('welcome-countdown');
        $response->assertSee('Continue to Dashboard');
        $response->assertSee(route('tools.overview'));
        $response->assertSee('window.location.href = "' . route('tools.overview') . '"', false);
    }
}
