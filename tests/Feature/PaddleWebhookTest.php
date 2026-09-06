<?php

namespace Tests\Feature;

use App\Models\CreditTransaction;
use App\Models\PaddleWebhookEvent;
use App\Models\PricingPlan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaddleWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected string $webhookSecret = 'pdl_ntfset_test_secret_key_12345';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.paddle.webhook_secret' => $this->webhookSecret]);
        config(['services.paddle.environment' => 'sandbox']);
    }

    /**
     * Helper to generate a valid Paddle-Signature header for a given raw payload.
     */
    protected function generatePaddleSignature(string $rawBody, ?int $timestamp = null): string
    {
        $ts = $timestamp ?? time();
        $payloadToSign = "{$ts}:{$rawBody}";
        $hash = hash_hmac('sha256', $payloadToSign, $this->webhookSecret);

        return "ts={$ts};h1={$hash}";
    }

    /**
     * Test 1: Invalid webhook signature returns 401 Unauthorized.
     */
    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $payload = json_encode([
            'event_id' => 'evt_test_001',
            'event_type' => 'transaction.completed',
            'data' => []
        ]);

        $response = $this->call(
            'POST',
            route('webhooks.paddle'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PADDLE_SIGNATURE' => 'ts=' . time() . ';h1=invalid_hmac_hash'
            ],
            $payload
        );

        $response->assertStatus(401);
        $response->assertJson(['success' => false, 'message' => 'Invalid webhook signature.']);
    }

    /**
     * Test 2: Valid webhook signature is accepted and processes transaction.completed.
     */
    public function test_valid_webhook_transaction_completed_grants_credits(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'credit_balance' => 10,
        ]);

        $plan = PricingPlan::create([
            'name' => 'Pro Studio',
            'slug' => 'pro-studio',
            'monthly_price' => '$49',
            'yearly_price' => '$470',
            'monthly_price_id' => 'pri_monthly_pro_123',
            'yearly_price_id' => 'pri_yearly_pro_456',
            'monthly_credits' => 3000,
            'yearly_credits' => 36000,
            'is_active' => true,
        ]);

        $payloadArray = [
            'event_id' => 'evt_valid_001',
            'event_type' => 'transaction.completed',
            'data' => [
                'id' => 'txn_valid_999',
                'customer_id' => 'ctm_customer_abc',
                'subscription_id' => 'sub_paddle_111',
                'currency_code' => 'USD',
                'custom_data' => [
                    'user_id' => (string) $user->id,
                ],
                'items' => [
                    [
                        'price' => [
                            'id' => 'pri_monthly_pro_123',
                        ],
                        'quantity' => 1,
                    ]
                ],
                'details' => [
                    'totals' => [
                        'grand_total' => '49.00',
                    ]
                ],
            ]
        ];

        $rawBody = json_encode($payloadArray);
        $signature = $this->generatePaddleSignature($rawBody);

        $response = $this->call(
            'POST',
            route('webhooks.paddle'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PADDLE_SIGNATURE' => $signature,
            ],
            $rawBody
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify credits granted to user (10 initial + 3000 = 3010)
        $user->refresh();
        $this->assertEquals(3010, $user->credit_balance);
        $this->assertEquals('ctm_customer_abc', $user->paddle_customer_id);

        // Verify CreditTransaction ledger entry
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 3000,
            'type' => 'subscription_grant',
            'source' => 'paddle_webhook',
            'reference_id' => 'txn_valid_999',
            'balance_after' => 3010,
        ]);

        // Verify Transaction record
        $this->assertDatabaseHas('transactions', [
            'paddle_transaction_id' => 'txn_valid_999',
            'user_id' => $user->id,
            'pricing_plan_id' => $plan->id,
            'status' => 'completed',
            'amount' => '49.00',
        ]);

        // Verify Subscription record
        $this->assertDatabaseHas('subscriptions', [
            'paddle_subscription_id' => 'sub_paddle_111',
            'user_id' => $user->id,
            'pricing_plan_id' => $plan->id,
            'status' => 'active',
            'billing_period' => 'monthly',
        ]);

        // Verify idempotency event recorded
        $this->assertDatabaseHas('paddle_webhook_events', [
            'event_id' => 'evt_valid_001',
            'event_type' => 'transaction.completed',
        ]);
    }

    /**
     * Test 3: Duplicate notification (event_id) is recognized and does NOT grant credits twice.
     */
    public function test_duplicate_notification_is_idempotent_and_does_not_grant_credits_twice(): void
    {
        $user = User::factory()->create(['credit_balance' => 0]);

        $plan = PricingPlan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'monthly_price_id' => 'pri_starter_mo',
            'monthly_credits' => 500,
        ]);

        $payloadArray = [
            'event_id' => 'evt_duplicate_test',
            'event_type' => 'transaction.completed',
            'data' => [
                'id' => 'txn_dup_001',
                'customer_id' => 'ctm_dup_123',
                'custom_data' => ['user_id' => (string) $user->id],
                'items' => [['price' => ['id' => 'pri_starter_mo']]],
                'details' => ['totals' => ['grand_total' => '19.00']],
            ]
        ];

        $rawBody = json_encode($payloadArray);
        $signature = $this->generatePaddleSignature($rawBody);

        // 1st delivery
        $res1 = $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $signature,
        ], $rawBody);
        $res1->assertStatus(200);

        $user->refresh();
        $this->assertEquals(500, $user->credit_balance);
        $this->assertEquals(1, CreditTransaction::count());

        // 2nd delivery of the exact same event
        $res2 = $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $signature,
        ], $rawBody);
        $res2->assertStatus(200);
        $res2->assertJson(['message' => 'Webhook event already processed.']);

        // User balance must STILL be 500, not 1000!
        $user->refresh();
        $this->assertEquals(500, $user->credit_balance);
        $this->assertEquals(1, CreditTransaction::count());
    }

    /**
     * Test 4: Duplicate transaction with different event_id does NOT grant credits twice.
     */
    public function test_duplicate_transaction_id_with_different_event_does_not_grant_credits_twice(): void
    {
        $user = User::factory()->create(['credit_balance' => 100]);

        PricingPlan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'monthly_price_id' => 'pri_starter_mo_2',
            'monthly_credits' => 500,
        ]);

        $txnId = 'txn_unique_shared_id';

        // 1st event
        $payload1 = json_encode([
            'event_id' => 'evt_delivery_A',
            'event_type' => 'transaction.completed',
            'data' => [
                'id' => $txnId,
                'custom_data' => ['user_id' => (string) $user->id],
                'items' => [['price' => ['id' => 'pri_starter_mo_2']]],
            ]
        ]);

        $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $this->generatePaddleSignature($payload1),
        ], $payload1)->assertStatus(200);

        $user->refresh();
        $this->assertEquals(600, $user->credit_balance);

        // 2nd event with same transaction_id but new event_id
        $payload2 = json_encode([
            'event_id' => 'evt_delivery_B',
            'event_type' => 'transaction.completed',
            'data' => [
                'id' => $txnId,
                'custom_data' => ['user_id' => (string) $user->id],
                'items' => [['price' => ['id' => 'pri_starter_mo_2']]],
            ]
        ]);

        $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $this->generatePaddleSignature($payload2),
        ], $payload2)->assertStatus(200);

        // Balance must remain 600
        $user->refresh();
        $this->assertEquals(600, $user->credit_balance);
        $this->assertEquals(1, CreditTransaction::where('reference_id', $txnId)->count());
    }

    /**
     * Test 5: Correct matching of Yearly Price ID grants yearly_credits.
     */
    public function test_yearly_price_id_matches_and_grants_yearly_credits(): void
    {
        $user = User::factory()->create(['credit_balance' => 0]);

        PricingPlan::create([
            'name' => 'Advanced Studio',
            'slug' => 'advanced',
            'yearly_price_id' => 'pri_advanced_yearly_id',
            'monthly_credits' => 10000,
            'yearly_credits' => 120000,
        ]);

        $rawBody = json_encode([
            'event_id' => 'evt_yearly_001',
            'event_type' => 'transaction.completed',
            'data' => [
                'id' => 'txn_yearly_999',
                'custom_data' => ['user_id' => (string) $user->id],
                'items' => [['price' => ['id' => 'pri_advanced_yearly_id']]],
            ]
        ]);

        $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $this->generatePaddleSignature($rawBody),
        ], $rawBody)->assertStatus(200);

        $user->refresh();
        $this->assertEquals(120000, $user->credit_balance);
    }

    /**
     * Test 6: Unknown Paddle Price ID does NOT grant any credits.
     */
    public function test_unknown_price_id_does_not_grant_credits(): void
    {
        $user = User::factory()->create(['credit_balance' => 50]);

        $rawBody = json_encode([
            'event_id' => 'evt_unknown_price',
            'event_type' => 'transaction.completed',
            'data' => [
                'id' => 'txn_unknown_price_123',
                'custom_data' => ['user_id' => (string) $user->id],
                'items' => [['price' => ['id' => 'pri_unrecognized_in_database']]],
            ]
        ]);

        $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $this->generatePaddleSignature($rawBody),
        ], $rawBody)->assertStatus(200);

        $user->refresh();
        $this->assertEquals(50, $user->credit_balance);
        $this->assertEquals(0, CreditTransaction::count());
        $this->assertDatabaseHas('transactions', [
            'paddle_transaction_id' => 'txn_unknown_price_123',
            'status' => 'unknown_plan',
        ]);
    }

    /**
     * Test 7: Subscription lifecycle event updates status without granting credits.
     */
    public function test_subscription_canceled_updates_status_without_granting_credits(): void
    {
        $user = User::factory()->create(['credit_balance' => 100]);

        $sub = Subscription::create([
            'user_id' => $user->id,
            'paddle_subscription_id' => 'sub_cancel_test',
            'status' => 'active',
            'billing_period' => 'monthly',
        ]);

        $rawBody = json_encode([
            'event_id' => 'evt_sub_canceled_001',
            'event_type' => 'subscription.canceled',
            'data' => [
                'id' => 'sub_cancel_test',
                'status' => 'canceled',
                'canceled_at' => now()->toISOString(),
            ]
        ]);

        $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $this->generatePaddleSignature($rawBody),
        ], $rawBody)->assertStatus(200);

        $sub->refresh();
        $this->assertEquals('canceled', $sub->status);
        $this->assertNotNull($sub->canceled_at);

        // Credit balance must remain untouched
        $user->refresh();
        $this->assertEquals(100, $user->credit_balance);
    }

    /**
     * Test 8: subscription_recurring + transaction.completed for monthly price:
     * - Adds monthly_credits to user
     * - Creates correct record in credit_transactions
     * - Idempotent: Does not add credits twice on duplicate webhook
     */
    public function test_subscription_recurring_monthly_price_grants_monthly_credits_and_prevents_duplicates(): void
    {
        $user = User::factory()->create([
            'credit_balance' => 50,
            'paddle_customer_id' => 'ctm_sub_mo_user_1',
        ]);

        $plan = PricingPlan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro-plan',
            'monthly_price_id' => 'pri_recurring_mo_plan',
            'yearly_price_id' => 'pri_recurring_yr_plan',
            'monthly_credits' => 3000,
            'yearly_credits' => 36000,
            'is_active' => true,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'paddle_subscription_id' => 'sub_recurring_mo_123',
            'paddle_customer_id' => 'ctm_sub_mo_user_1',
            'paddle_price_id' => 'pri_recurring_mo_plan',
            'pricing_plan_id' => $plan->id,
            'status' => 'active',
            'billing_period' => 'monthly',
        ]);

        $txnId = 'txn_recurring_mo_999';
        $payloadArray = [
            'event_id' => 'evt_recurring_mo_001',
            'event_type' => 'transaction.completed',
            'data' => [
                'id' => $txnId,
                'customer_id' => 'ctm_sub_mo_user_1',
                'subscription_id' => 'sub_recurring_mo_123',
                'origin' => 'subscription_recurring',
                'currency_code' => 'USD',
                'items' => [
                    [
                        'price' => [
                            'id' => 'pri_recurring_mo_plan',
                        ],
                        'quantity' => 1,
                    ]
                ],
                'details' => [
                    'totals' => [
                        'grand_total' => '29.00',
                    ]
                ],
            ]
        ];

        $rawBody = json_encode($payloadArray);
        $signature = $this->generatePaddleSignature($rawBody);

        // 1st delivery
        $response1 = $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $signature,
        ], $rawBody);

        $response1->assertStatus(200);
        $response1->assertJson(['success' => true]);

        // Verify credit balance updated: 50 + 3000 = 3050
        $user->refresh();
        $this->assertEquals(3050, $user->credit_balance);

        // Verify credit_transactions entry
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 3000,
            'balance_after' => 3050,
            'type' => 'subscription_grant',
            'source' => 'paddle_webhook',
            'reference_id' => $txnId,
        ]);

        // Verify transaction entry with type 'subscription_recurring'
        $this->assertDatabaseHas('transactions', [
            'paddle_transaction_id' => $txnId,
            'user_id' => $user->id,
            'paddle_subscription_id' => 'sub_recurring_mo_123',
            'type' => 'subscription_recurring',
            'status' => 'completed',
            'amount' => '29.00',
        ]);

        // 2nd delivery (duplicate event_id)
        $response2 = $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $signature,
        ], $rawBody);

        $response2->assertStatus(200);
        $response2->assertJson(['message' => 'Webhook event already processed.']);

        // Assert no double grant
        $user->refresh();
        $this->assertEquals(3050, $user->credit_balance);
        $this->assertEquals(1, CreditTransaction::where('reference_id', $txnId)->count());

        // 3rd delivery (different event_id but same transaction ID)
        $payloadArrayDupTxn = $payloadArray;
        $payloadArrayDupTxn['event_id'] = 'evt_recurring_mo_002_new_id';
        $rawBodyDupTxn = json_encode($payloadArrayDupTxn);
        $signatureDupTxn = $this->generatePaddleSignature($rawBodyDupTxn);

        $response3 = $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $signatureDupTxn,
        ], $rawBodyDupTxn);

        $response3->assertStatus(200);

        // Assert still no double grant
        $user->refresh();
        $this->assertEquals(3050, $user->credit_balance);
        $this->assertEquals(1, CreditTransaction::where('reference_id', $txnId)->count());
    }

    /**
     * Test 9: subscription_recurring + transaction.completed for yearly price:
     * - Adds yearly_credits to user
     * - Creates correct record in credit_transactions
     * - Idempotent: Does not add credits twice on duplicate webhook
     */
    public function test_subscription_recurring_yearly_price_grants_yearly_credits_and_prevents_duplicates(): void
    {
        $user = User::factory()->create([
            'credit_balance' => 200,
            'paddle_customer_id' => 'ctm_sub_yr_user_1',
        ]);

        $plan = PricingPlan::create([
            'name' => 'Advanced Plan',
            'slug' => 'advanced-plan',
            'monthly_price_id' => 'pri_rec_mo_adv',
            'yearly_price_id' => 'pri_rec_yr_adv',
            'monthly_credits' => 10000,
            'yearly_credits' => 120000,
            'is_active' => true,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'paddle_subscription_id' => 'sub_recurring_yr_456',
            'paddle_customer_id' => 'ctm_sub_yr_user_1',
            'paddle_price_id' => 'pri_rec_yr_adv',
            'pricing_plan_id' => $plan->id,
            'status' => 'active',
            'billing_period' => 'yearly',
        ]);

        $txnId = 'txn_recurring_yr_777';
        $payloadArray = [
            'event_id' => 'evt_recurring_yr_001',
            'event_type' => 'transaction.completed',
            'data' => [
                'id' => $txnId,
                'customer_id' => 'ctm_sub_yr_user_1',
                'subscription_id' => 'sub_recurring_yr_456',
                'origin' => 'subscription_recurring',
                'currency_code' => 'USD',
                'items' => [
                    [
                        'price' => [
                            'id' => 'pri_rec_yr_adv',
                        ],
                        'quantity' => 1,
                    ]
                ],
                'details' => [
                    'totals' => [
                        'grand_total' => '290.00',
                    ]
                ],
            ]
        ];

        $rawBody = json_encode($payloadArray);
        $signature = $this->generatePaddleSignature($rawBody);

        // 1st delivery
        $res1 = $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $signature,
        ], $rawBody);
        $res1->assertStatus(200);

        // Verify credit balance updated: 200 + 120000 = 120200
        $user->refresh();
        $this->assertEquals(120200, $user->credit_balance);

        // Verify credit_transactions entry
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'amount' => 120000,
            'balance_after' => 120200,
            'type' => 'subscription_grant',
            'source' => 'paddle_webhook',
            'reference_id' => $txnId,
        ]);

        // 2nd delivery (duplicate event)
        $res2 = $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $signature,
        ], $rawBody);
        $res2->assertStatus(200);

        // Balance must remain unchanged
        $user->refresh();
        $this->assertEquals(120200, $user->credit_balance);
        $this->assertEquals(1, CreditTransaction::where('reference_id', $txnId)->count());
    }

    /**
     * Test 10: subscription.updated updates subscription data only and does NOT grant credits.
     */
    public function test_subscription_updated_updates_subscription_data_only_and_does_not_grant_credits(): void
    {
        $user = User::factory()->create([
            'credit_balance' => 250,
            'paddle_customer_id' => 'ctm_sub_upd_1',
        ]);

        $sub = Subscription::create([
            'user_id' => $user->id,
            'paddle_subscription_id' => 'sub_update_test_1',
            'paddle_customer_id' => 'ctm_sub_upd_1',
            'status' => 'active',
            'billing_period' => 'monthly',
        ]);

        $newBilledAt = now()->addMonth()->toISOString();
        $rawBody = json_encode([
            'event_id' => 'evt_sub_updated_001',
            'event_type' => 'subscription.updated',
            'data' => [
                'id' => 'sub_update_test_1',
                'customer_id' => 'ctm_sub_upd_1',
                'status' => 'active',
                'next_billed_at' => $newBilledAt,
            ]
        ]);

        $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $this->generatePaddleSignature($rawBody),
        ], $rawBody)->assertStatus(200);

        // Subscription data updated
        $sub->refresh();
        $this->assertEquals('active', $sub->status);
        $this->assertNotNull($sub->next_billed_at);

        // User credits must NOT change
        $user->refresh();
        $this->assertEquals(250, $user->credit_balance);
        $this->assertEquals(0, CreditTransaction::count());
    }

    /**
     * Test 11: subscription.past_due updates status only and does NOT grant credits.
     */
    public function test_subscription_past_due_updates_status_only_and_does_not_grant_credits(): void
    {
        $user = User::factory()->create([
            'credit_balance' => 175,
            'paddle_customer_id' => 'ctm_past_due_user',
        ]);

        $sub = Subscription::create([
            'user_id' => $user->id,
            'paddle_subscription_id' => 'sub_past_due_test_1',
            'paddle_customer_id' => 'ctm_past_due_user',
            'status' => 'active',
            'billing_period' => 'monthly',
        ]);

        $rawBody = json_encode([
            'event_id' => 'evt_sub_past_due_001',
            'event_type' => 'subscription.past_due',
            'data' => [
                'id' => 'sub_past_due_test_1',
                'customer_id' => 'ctm_past_due_user',
                'status' => 'past_due',
            ]
        ]);

        $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $this->generatePaddleSignature($rawBody),
        ], $rawBody)->assertStatus(200);

        // Subscription status changed to past_due
        $sub->refresh();
        $this->assertEquals('past_due', $sub->status);

        // Credits remain strictly untouched
        $user->refresh();
        $this->assertEquals(175, $user->credit_balance);
        $this->assertEquals(0, CreditTransaction::count());
    }

    /**
     * Test 12: Webhook signature verification uses raw request body and rejects any body tampering.
     */
    public function test_raw_request_body_signature_verification_rejects_tampered_body(): void
    {
        $originalBody = json_encode([
            'event_id' => 'evt_raw_check',
            'event_type' => 'subscription.canceled',
            'data' => ['id' => 'sub_tamper_test', 'status' => 'canceled']
        ]);

        // Generate signature with original body
        $validSignature = $this->generatePaddleSignature($originalBody);

        // Tamper with body even by adding an extra whitespace
        $tamperedBody = $originalBody . " ";

        $response = $this->call('POST', route('webhooks.paddle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_PADDLE_SIGNATURE' => $validSignature,
        ], $tamperedBody);

        $response->assertStatus(401);
        $response->assertJson(['success' => false, 'message' => 'Invalid webhook signature.']);
    }
}
