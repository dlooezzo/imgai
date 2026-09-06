<?php

namespace Tests\Feature;

use App\Models\CreditTransaction;
use App\Models\PricingPlan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBillingLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@imgai.test',
        ]);
    }

    public function test_admin_credits_page_renders_with_zero_records_empty_state(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.credits.index'));

        $response->assertStatus(200);
        $response->assertSee('Credit Transactions Ledger');
        $response->assertSee('No credit ledger entries found.');
        $response->assertSee('Entries: 0');
        $response->assertSee('Granted: +0');
        $response->assertSee('Deducted: -0');
    }

    public function test_admin_transactions_page_renders_with_zero_records_empty_state(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.transactions.index'));

        $response->assertStatus(200);
        $response->assertSee('Paddle Transactions');
        $response->assertSee('No transactions recorded yet.');
        $response->assertSee('Total: 0');
        $response->assertSee('Completed: 0');
        $response->assertSee('Volume: $0.00');
    }

    public function test_admin_subscriptions_page_renders_with_zero_records_empty_state(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.subscriptions.index'));

        $response->assertStatus(200);
        $response->assertSee('Customer Subscriptions');
        $response->assertSee('No subscriptions found.');
        $response->assertSee('Active: 0');
        $response->assertSee('Canceled: 0');
        $response->assertSee('Total: 0');
    }

    public function test_admin_credits_page_displays_ledger_records_and_stats(): void
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);

        // Grant entry
        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 500,
            'type' => 'subscription_grant',
            'source' => 'paddle_webhook',
            'reference_id' => 'txn_grant_123',
            'description' => 'Pro Monthly plan grant',
            'balance_after' => 500,
        ]);

        // Deduction entry
        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => -5,
            'type' => 'generation_deduction',
            'source' => 'video_generation',
            'reference_id' => 'gen_vid_456',
            'description' => 'Hunyuan video generation deduction',
            'balance_after' => 495,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.credits.index'));

        $response->assertStatus(200);
        $response->assertSee('john@example.com');
        $response->assertSee('+500');
        $response->assertSee('-5');
        $response->assertSee('txn_grant_123');
        $response->assertSee('gen_vid_456');
        $response->assertSee('Granted: +500');
        $response->assertSee('Deducted: -5');
        $response->assertSee('Entries: 2');
    }

    public function test_admin_transactions_page_displays_transaction_records_and_stats(): void
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create(['name' => 'Alice Smith', 'email' => 'alice@example.com']);
        $plan = PricingPlan::create([
            'name' => 'Studio Pro',
            'slug' => 'studio-pro',
            'monthly_price' => '$29',
            'monthly_price_id' => 'pri_pro_monthly',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'pricing_plan_id' => $plan->id,
            'paddle_transaction_id' => 'txn_paddle_999',
            'paddle_subscription_id' => 'sub_paddle_111',
            'status' => 'completed',
            'amount' => '29.00',
            'currency' => 'USD',
            'type' => 'subscription',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.transactions.index'));

        $response->assertStatus(200);
        $response->assertSee('txn_paddle_999');
        $response->assertSee('sub_paddle_111');
        $response->assertSee('Studio Pro');
        $response->assertSee('alice@example.com');
        $response->assertSee('$29.00');
        $response->assertSee('Completed: 1');
        $response->assertSee('Volume: $29.00');
        $response->assertSee('Total: 1');
    }

    public function test_admin_subscriptions_page_displays_subscription_records_and_stats(): void
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create(['name' => 'Bob Builder', 'email' => 'bob@example.com']);
        $plan = PricingPlan::create([
            'name' => 'Starter Pack',
            'slug' => 'starter-pack',
            'monthly_price' => '$10',
            'monthly_price_id' => 'pri_starter_monthly',
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'pricing_plan_id' => $plan->id,
            'paddle_subscription_id' => 'sub_live_555',
            'paddle_customer_id' => 'ctm_live_666',
            'status' => 'active',
            'billing_period' => 'monthly',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.subscriptions.index'));

        $response->assertStatus(200);
        $response->assertSee('sub_live_555');
        $response->assertSee('ctm_live_666');
        $response->assertSee('Starter Pack');
        $response->assertSee('bob@example.com');
        $response->assertSee('Active: 1');
        $response->assertSee('Total: 1');
    }

    public function test_non_admin_cannot_access_billing_ledger_pages(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('admin.credits.index'))->assertStatus(403);
        $this->actingAs($user)->get(route('admin.transactions.index'))->assertStatus(403);
        $this->actingAs($user)->get(route('admin.subscriptions.index'))->assertStatus(403);
    }

    public function test_admin_can_run_migrations_from_settings_endpoint(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->post(route('admin.settings.migrate'));

        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
