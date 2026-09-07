<?php

namespace App\Http\Controllers;

use App\Models\PaddleWebhookEvent;
use App\Models\PricingPlan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Credits\CreditService;
use App\Services\Paddle\PaddleService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaddleWebhookController extends Controller
{
    protected PaddleService $paddleService;
    protected CreditService $creditService;

    public function __construct(PaddleService $paddleService, CreditService $creditService)
    {
        $this->paddleService = $paddleService;
        $this->creditService = $creditService;
    }

    /**
     * Handle incoming Paddle Billing webhook notifications.
     */
    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signatureHeader = $request->header('Paddle-Signature');

        // 1. Verify webhook signature using raw body before any processing
        if (!$this->paddleService->verifyWebhookSignature($rawBody, $signatureHeader)) {
            Log::warning('[PADDLE WEBHOOK] Signature verification failed.', [
                'ip' => $request->ip(),
                'signature' => $signatureHeader,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        // 2. Decode and validate JSON payload
        $payload = json_decode($rawBody, true);
        if (!is_array($payload) || empty($payload['event_id']) || empty($payload['event_type'])) {
            Log::warning('[PADDLE WEBHOOK] Invalid or missing event payload structure.', ['body' => $rawBody]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid event payload structure.',
            ], 400);
        }

        $eventId = (string) $payload['event_id'];
        $eventType = (string) $payload['event_type'];
        $eventData = $payload['data'] ?? [];

        Log::info("[PADDLE WEBHOOK] Received event: {$eventType} | Event ID: {$eventId}");

        // 3. Complete Idempotency Check: check if event was already processed
        if (PaddleWebhookEvent::where('event_id', $eventId)->exists()) {
            Log::info("[PADDLE WEBHOOK] Duplicate event ignored (already processed): {$eventId}");

            return response()->json([
                'success' => true,
                'message' => 'Webhook event already processed.',
            ]);
        }

        try {
            DB::transaction(function () use ($eventId, $eventType, $eventData, $payload) {
                // Route according to Paddle Billing event type
                switch ($eventType) {
                    case 'transaction.completed':
                        $this->handleTransactionCompleted($eventData, $payload);
                        break;

                    case 'subscription.created':
                    case 'subscription.updated':
                        $this->handleSubscriptionUpdated($eventData, $eventType);
                        break;

                    case 'subscription.canceled':
                        $this->handleSubscriptionCanceled($eventData);
                        break;

                    case 'subscription.past_due':
                    case 'subscription.paused':
                    case 'subscription.resumed':
                        $this->handleSubscriptionStatusChange($eventData, $eventType);
                        break;

                    default:
                        Log::info("[PADDLE WEBHOOK] Unhandled event type: {$eventType}. Acknowledging.");
                        break;
                }

                // Record the processed event in paddle_webhook_events
                PaddleWebhookEvent::create([
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                    'payload' => $payload,
                    'processed_at' => now(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Webhook handled successfully.',
            ]);
        } catch (Exception $e) {
            Log::error("[PADDLE WEBHOOK ERROR] Failed processing event {$eventId}: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Primary fulfillment handler: Grants credits ONLY on transaction.completed.
     */
    protected function handleTransactionCompleted(array $data, array $rawPayload): void
    {
        $txnId = $data['id'] ?? null;
        if (!$txnId) {
            Log::warning('[PADDLE WEBHOOK] transaction.completed missing transaction id.');
            return;
        }

        // Idempotency: Verify this transaction ID has not already been fulfilled
        $existingTxn = Transaction::where('paddle_transaction_id', $txnId)->first();
        if ($existingTxn && $existingTxn->status === 'completed') {
            Log::info("[PADDLE WEBHOOK] Transaction {$txnId} already recorded and fulfilled. Skipping credit grant.");
            return;
        }

        $customerId = $data['customer_id'] ?? null;
        $subscriptionId = $data['subscription_id'] ?? null;
        $customData = $data['custom_data'] ?? [];

        // Extract items and Price ID
        $items = $data['items'] ?? [];
        $firstItem = $items[0] ?? [];
        $priceId = $firstItem['price']['id'] ?? ($firstItem['price_id'] ?? null);

        // 1. Resolve User safely (do not trust frontend custom_data alone)
        $user = $this->resolveUserFromPaddleData($customerId, $customData, $data);
        if (!$user) {
            Log::error("[PADDLE WEBHOOK] Unknown user for transaction {$txnId}. Customer ID: {$customerId}. Credits not granted.");
            // Store transaction record as unassigned for audit
            Transaction::updateOrCreate(
                ['paddle_transaction_id' => $txnId],
                [
                    'user_id' => null,
                    'paddle_subscription_id' => $subscriptionId,
                    'paddle_price_id' => $priceId,
                    'status' => 'unassigned_user',
                    'amount' => $this->extractAmount($data),
                    'currency' => $data['currency_code'] ?? 'USD',
                    'type' => !empty($subscriptionId) ? 'subscription' : 'one_time',
                    'processed_at' => now(),
                    'raw_payload' => $rawPayload,
                ]
            );
            return;
        }

        // Keep user's paddle_customer_id up to date
        if ($customerId && empty($user->paddle_customer_id)) {
            $user->paddle_customer_id = $customerId;
            $user->save();
        }

        // 2. Validate and match Price ID to existing PricingPlan
        if (empty($priceId)) {
            Log::error("[PADDLE WEBHOOK] Missing Price ID in transaction {$txnId}. Credits not granted.");
            return;
        }

        $plan = PricingPlan::findByPaddlePriceId($priceId);
        if (!$plan) {
            Log::error("[PADDLE WEBHOOK] Unknown Paddle Price ID '{$priceId}' in transaction {$txnId}. No matching PricingPlan found. Credits not granted.");
            Transaction::updateOrCreate(
                ['paddle_transaction_id' => $txnId],
                [
                    'user_id' => $user->id,
                    'paddle_subscription_id' => $subscriptionId,
                    'paddle_price_id' => $priceId,
                    'status' => 'unknown_plan',
                    'amount' => $this->extractAmount($data),
                    'currency' => $data['currency_code'] ?? 'USD',
                    'type' => !empty($subscriptionId) ? 'subscription' : 'one_time',
                    'processed_at' => now(),
                    'raw_payload' => $rawPayload,
                ]
            );
            return;
        }

        // Determine whether monthly or yearly price
        $billingPeriod = ($plan->yearly_price_id === $priceId) ? 'yearly' : 'monthly';
        $creditsToGrant = ($billingPeriod === 'yearly')
            ? (int) ($plan->yearly_credits ?: ($plan->monthly_credits * 12))
            : (int) $plan->monthly_credits;

        // 3. Record or update the Transaction
        $amount = $this->extractAmount($data);
        $currency = $data['currency_code'] ?? 'USD';
        $origin = $data['origin'] ?? (!empty($subscriptionId) ? 'subscription' : 'one_time');

        $transaction = Transaction::updateOrCreate(
            ['paddle_transaction_id' => $txnId],
            [
                'user_id' => $user->id,
                'paddle_subscription_id' => $subscriptionId,
                'paddle_price_id' => $priceId,
                'pricing_plan_id' => $plan->id,
                'status' => 'completed',
                'amount' => $amount,
                'currency' => $currency,
                'type' => $origin,
                'processed_at' => now(),
                'raw_payload' => $rawPayload,
            ]
        );

        // 4. Update or link local subscription if present
        if ($subscriptionId) {
            Subscription::updateOrCreate(
                ['paddle_subscription_id' => $subscriptionId],
                [
                    'user_id' => $user->id,
                    'paddle_customer_id' => $customerId,
                    'paddle_price_id' => $priceId,
                    'pricing_plan_id' => $plan->id,
                    'status' => 'active',
                    'billing_period' => $billingPeriod,
                    'raw_metadata' => $data,
                ]
            );
        }

        // 5. Grant credits atomically via CreditService with unique reference
        if ($creditsToGrant > 0) {
            $descPrefix = ($origin === 'subscription_recurring') ? 'Subscription renewal' : 'Subscription grant';
            $this->creditService->addCredits(
                user: $user,
                amount: $creditsToGrant,
                type: 'subscription_grant',
                source: 'paddle_webhook',
                referenceId: $txnId,
                description: "{$descPrefix}: Allocated {$creditsToGrant} credits for {$plan->name} ({$billingPeriod}) — Txn: {$txnId}"
            );
        }

        Log::info("[PADDLE WEBHOOK] Successfully fulfilled transaction {$txnId}: Granted {$creditsToGrant} credits to user #{$user->id} for plan '{$plan->name}'.");
    }

    /**
     * Handle subscription created and updated lifecycle events.
     * NOTE: Does NOT grant credits; credits are granted exclusively on transaction.completed.
     */
    protected function handleSubscriptionUpdated(array $data, string $eventType): void
    {
        $subId = $data['id'] ?? null;
        if (!$subId) {
            return;
        }

        $customerId = $data['customer_id'] ?? null;
        $status = strtolower($data['status'] ?? 'active');
        $customData = $data['custom_data'] ?? [];

        // Extract items and Price ID
        $items = $data['items'] ?? [];
        $firstItem = $items[0] ?? [];
        $priceId = $firstItem['price']['id'] ?? ($firstItem['price_id'] ?? null);

        $user = $this->resolveUserFromPaddleData($customerId, $customData, $data);
        if (!$user) {
            Log::warning("[PADDLE WEBHOOK] {$eventType} for sub {$subId}: User could not be resolved.");
            return;
        }

        $plan = $priceId ? PricingPlan::findByPaddlePriceId($priceId) : null;
        $billingPeriod = ($plan && $plan->yearly_price_id === $priceId) ? 'yearly' : 'monthly';

        $nextBilledAt = null;
        if (!empty($data['next_billed_at'])) {
            $nextBilledAt = Carbon::parse($data['next_billed_at']);
        } elseif (!empty($data['current_billing_period']['ends_at'])) {
            $nextBilledAt = Carbon::parse($data['current_billing_period']['ends_at']);
        }

        Subscription::updateOrCreate(
            ['paddle_subscription_id' => $subId],
            [
                'user_id' => $user->id,
                'paddle_customer_id' => $customerId,
                'paddle_price_id' => $priceId,
                'pricing_plan_id' => $plan?->id,
                'status' => $status,
                'billing_period' => $billingPeriod,
                'next_billed_at' => $nextBilledAt,
                'raw_metadata' => $data,
            ]
        );

        Log::info("[PADDLE WEBHOOK] Synchronized subscription {$subId} ({$status}) for user #{$user->id}.");
    }

    /**
     * Handle subscription cancellation event.
     */
    protected function handleSubscriptionCanceled(array $data): void
    {
        $subId = $data['id'] ?? null;
        if (!$subId) {
            return;
        }

        $canceledAt = !empty($data['canceled_at']) ? Carbon::parse($data['canceled_at']) : now();

        $sub = Subscription::where('paddle_subscription_id', $subId)->first();
        if ($sub) {
            $sub->update([
                'status' => 'canceled',
                'canceled_at' => $canceledAt,
                'raw_metadata' => $data,
            ]);

            Log::info("[PADDLE WEBHOOK] Subscription {$subId} marked as canceled.");
        } else {
            $user = $this->resolveUserFromPaddleData($data['customer_id'] ?? null, $data['custom_data'] ?? [], $data);
            Subscription::create([
                'paddle_subscription_id' => $subId,
                'user_id' => $user?->id,
                'paddle_customer_id' => $data['customer_id'] ?? null,
                'status' => 'canceled',
                'canceled_at' => $canceledAt,
                'raw_metadata' => $data,
            ]);
        }
    }

    /**
     * Handle other subscription status changes (past_due, paused, resumed).
     */
    protected function handleSubscriptionStatusChange(array $data, string $eventType): void
    {
        $subId = $data['id'] ?? null;
        if (!$subId) {
            return;
        }

        $status = strtolower($data['status'] ?? str_replace('subscription.', '', $eventType));

        $sub = Subscription::where('paddle_subscription_id', $subId)->first();
        if ($sub) {
            $sub->update([
                'status' => $status,
                'raw_metadata' => $data,
            ]);

            Log::info("[PADDLE WEBHOOK] Subscription {$subId} status updated to {$status}.");
        } else {
            $user = $this->resolveUserFromPaddleData($data['customer_id'] ?? null, $data['custom_data'] ?? [], $data);
            Subscription::create([
                'paddle_subscription_id' => $subId,
                'user_id' => $user?->id,
                'paddle_customer_id' => $data['customer_id'] ?? null,
                'status' => $status,
                'raw_metadata' => $data,
            ]);
        }
    }

    /**
     * Reliably resolve a local User from Paddle event data.
     * Checks existing paddle_customer_id first, then custom_data with DB verification, then customer email.
     */
    protected function resolveUserFromPaddleData(?string $customerId, array $customData, array $data): ?User
    {
        // 1. Try matching existing paddle_customer_id in users table
        if ($customerId) {
            $userByCustomer = User::where('paddle_customer_id', $customerId)->first();
            if ($userByCustomer) {
                return $userByCustomer;
            }
        }

        // 2. Try custom_data.user_id if present and verify against database
        if (!empty($customData['user_id'])) {
            $userById = User::find($customData['user_id']);
            if ($userById) {
                return $userById;
            }
        }

        // 3. Try matching customer email directly from payload
        $customerEmail = $data['customer']['email']
            ?? $data['customer_email']
            ?? ($customData['email'] ?? null);

        if (!empty($customerEmail) && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $userByEmail = User::where('email', $customerEmail)->first();
            if ($userByEmail) {
                return $userByEmail;
            }
        }

        // 4. Fallback: Query Paddle API for customer details if customer ID is present
        if ($customerId) {
            $apiCustomer = $this->paddleService->getCustomerDetails($customerId);
            if (!empty($apiCustomer['email']) && filter_var($apiCustomer['email'], FILTER_VALIDATE_EMAIL)) {
                $userByApiEmail = User::where('email', $apiCustomer['email'])->first();
                if ($userByApiEmail) {
                    $userByApiEmail->paddle_customer_id = $customerId;
                    $userByApiEmail->save();
                    return $userByApiEmail;
                }
            }
        }

        return null;
    }

    /**
     * Extract human-readable amount string from Paddle event data.
     */
    protected function extractAmount(array $data): string
    {
        // In Paddle Billing v2: details.totals.grand_total or totals.grand_total (often in cents/lowest unit)
        $rawAmount = $data['details']['totals']['grand_total']
            ?? $data['details']['totals']['total']
            ?? $data['totals']['grand_total']
            ?? null;

        if ($rawAmount !== null) {
            if (is_numeric($rawAmount)) {
                // If Paddle passes amount formatted as 1900 cents, convert if needed or format as decimal
                // In Paddle Billing v2 grand_total is a string like "19.00" or "1900"
                if (str_contains((string) $rawAmount, '.')) {
                    return number_format((float) $rawAmount, 2, '.', '');
                }
                // If it's in cents (no decimal point and >= 100)
                return number_format(((int) $rawAmount) / 100, 2, '.', '');
            }
            return (string) $rawAmount;
        }

        return '0.00';
    }
}
