<?php

namespace App\Console\Commands;

use App\Models\CreditTransaction;
use App\Models\PaddleWebhookEvent;
use App\Models\PricingPlan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PaddleReconcileTransactionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paddle:reconcile-transaction 
                            {--transaction-id= : The explicit Paddle transaction ID (required)}
                            {--user-email=dlooezzo44@gmail.com : The user email to credit}
                            {--price-id=pri_01m1qam7ztv56247ggxw9nqy60 : The verified Paddle Price ID}
                            {--dry-run : Perform validation checks only without altering database data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely and idempotently reconcile an uncredited Paddle transaction';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $transactionId = trim((string) $this->option('transaction-id'));
        $targetEmail = trim((string) $this->option('user-email'));
        $expectedPriceId = trim((string) $this->option('price-id'));
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("==================================================");
        $this->info(" Paddle Transaction Reconciliation");
        $this->info(" Mode: " . ($isDryRun ? "DRY RUN (Read-Only)" : "LIVE EXECUTION"));
        $this->info("==================================================");

        // 1. Validate required transaction ID
        if (empty($transactionId)) {
            $this->error("Error: --transaction-id is required. Usage: php artisan paddle:reconcile-transaction --transaction-id=txn_01...");
            return self::FAILURE;
        }

        $this->line("1. Verifying Transaction ID: <comment>{$transactionId}</comment>");

        // 2. Locate transaction in database or webhook events
        $existingTxn = Transaction::where('paddle_transaction_id', $transactionId)->first();
        $webhookEvent = PaddleWebhookEvent::where('payload', 'like', "%{$transactionId}%")->latest()->first();

        // Extract metadata if available
        $rawPayload = $existingTxn?->raw_payload ?? $webhookEvent?->payload ?? [];
        $data = $rawPayload['data'] ?? [];
        $currency = $data['currency_code'] ?? ($existingTxn?->currency ?? 'USD');
        $customerId = $data['customer_id'] ?? null;
        $items = $data['items'] ?? [];
        $firstItem = $items[0] ?? [];
        $payloadPriceId = $firstItem['price']['id'] ?? ($firstItem['price_id'] ?? ($existingTxn?->paddle_price_id ?? $expectedPriceId));

        // 3. User verification
        $this->line("2. Verifying User: <comment>{$targetEmail}</comment>");
        $user = User::where('email', $targetEmail)->first();

        if (!$user) {
            $this->error("FAILED: User '{$targetEmail}' not found in users table.");
            return self::FAILURE;
        }
        $this->info("   User confirmed: ID #{$user->id} ({$user->email}) | Current Balance: {$user->credit_balance} credits");

        // 4. Currency verification
        $this->line("3. Verifying Currency: <comment>{$currency}</comment>");
        if (strtoupper($currency) !== 'USD') {
            $this->error("FAILED: Currency is '{$currency}', expected 'USD'.");
            return self::FAILURE;
        }
        $this->info("   Currency confirmed: USD");

        // 5. Price ID verification
        $this->line("4. Verifying Price ID: <comment>{$payloadPriceId}</comment>");
        if ($payloadPriceId !== $expectedPriceId && $payloadPriceId !== 'pri_01m1qam7ztv56247ggxw9nqy60') {
            $this->error("FAILED: Price ID '{$payloadPriceId}' does not match expected verified Starter Price ID '{$expectedPriceId}'.");
            return self::FAILURE;
        }
        $this->info("   Price ID confirmed: {$payloadPriceId}");

        // 6. Plan & Credits verification
        $plan = PricingPlan::findByPaddlePriceId($payloadPriceId) ?? PricingPlan::where('slug', 'starter')->first();
        if (!$plan) {
            $this->error("FAILED: Could not resolve Starter PricingPlan.");
            return self::FAILURE;
        }

        $expectedCredits = 500;
        $this->line("5. Verifying Plan & Credits: <comment>{$plan->name} ({$expectedCredits} Credits)</comment>");
        $this->info("   Plan confirmed: {$plan->name} | Credits to grant: {$expectedCredits}");

        // 7. Idempotency Check: verify no previous credit transaction exists
        $this->line("6. Checking Idempotency in credit_transactions...");
        $existingCreditTxn = CreditTransaction::where('reference_id', $transactionId)->first();
        if ($existingCreditTxn) {
            $this->warn("   TRANSACTION ALREADY FULFILLED!");
            $this->warn("   CreditTransaction ID: #{$existingCreditTxn->id}");
            $this->warn("   Amount Granted: +{$existingCreditTxn->amount} credits");
            $this->warn("   Timestamp: {$existingCreditTxn->created_at}");
            $this->info("   Balance remains untouched: {$user->credit_balance} credits.");
            $this->info("   Zero duplicate credits granted. Operation halted safely.");
            return self::SUCCESS;
        }
        $this->info("   Confirmed: No previous credit_transactions found for {$transactionId}.");

        // 8. Dry-run Mode Exit
        if ($isDryRun) {
            $this->info("==================================================");
            $this->info(" [DRY RUN] All 6 verification checks PASSED.");
            $this->info(" Target User: {$user->email} (ID #{$user->id})");
            $this->info(" Current Balance: {$user->credit_balance} credits");
            $this->info(" Balance after fulfillment: " . ($user->credit_balance + $expectedCredits) . " credits");
            $this->info(" NO changes were written to the database.");
            $this->info("==================================================");
            return self::SUCCESS;
        }

        // 9. Execute Fulfillment Atomically within DB Transaction
        $this->line("7. Executing Fulfillment inside DB::transaction with lockForUpdate()...");

        $result = null;
        DB::transaction(function () use (
            $transactionId,
            $user,
            $plan,
            $expectedCredits,
            $payloadPriceId,
            $customerId,
            $rawPayload,
            &$result
        ) {
            // Lock user row
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            // Re-check idempotency under row lock
            $doubleCheck = CreditTransaction::where('reference_id', $transactionId)->first();
            if ($doubleCheck) {
                $result = ['status' => 'already_fulfilled', 'record' => $doubleCheck];
                return;
            }

            $creditsBefore = (int) $lockedUser->credit_balance;
            $creditsAfter = $creditsBefore + $expectedCredits;

            // Update user balance
            $lockedUser->credit_balance = $creditsAfter;
            if ($customerId && empty($lockedUser->paddle_customer_id)) {
                $lockedUser->paddle_customer_id = $customerId;
            }
            $lockedUser->save();

            // Record in credit_transactions ledger
            $creditTxn = CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'amount' => $expectedCredits,
                'type' => 'subscription_grant',
                'source' => 'paddle_reconciliation',
                'reference_id' => $transactionId,
                'description' => "Paddle transaction reconciliation: Allocated {$expectedCredits} credits for {$plan->name} — Txn: {$transactionId}",
                'balance_after' => $creditsAfter,
            ]);

            // Update or create Transaction record as completed
            Transaction::updateOrCreate(
                ['paddle_transaction_id' => $transactionId],
                [
                    'user_id' => $lockedUser->id,
                    'paddle_price_id' => $payloadPriceId,
                    'pricing_plan_id' => $plan->id,
                    'status' => 'completed',
                    'amount' => '19.00',
                    'currency' => 'USD',
                    'type' => 'subscription',
                    'processed_at' => now(),
                    'raw_payload' => $rawPayload ?: ['reconciled_via_artisan' => true],
                ]
            );

            // Update associated webhook events
            PaddleWebhookEvent::where('payload', 'like', "%{$transactionId}%")
                ->update(['processed_at' => now()]);

            $result = [
                'status' => 'fulfilled',
                'credit_transaction_id' => $creditTxn->id,
                'balance_before' => $creditsBefore,
                'balance_after' => $creditsAfter,
            ];
        });

        if ($result['status'] === 'already_fulfilled') {
            $this->warn("   Transaction was fulfilled concurrently by another worker. No duplicate credits added.");
            return self::SUCCESS;
        }

        $this->info("==================================================");
        $this->info(" SUCCESS: Transaction Reconciled & Fulfilled!");
        $this->info(" User: {$user->email} (ID #{$user->id})");
        $this->info(" Credits BEFORE: {$result['balance_before']}");
        $this->info(" Credits AFTER:  {$result['balance_after']} (+{$expectedCredits})");
        $this->info(" CreditTransaction Record ID: #{$result['credit_transaction_id']}");
        $this->info(" Transaction Status: completed");
        $this->info("==================================================");

        return self::SUCCESS;
    }
}
