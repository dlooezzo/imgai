<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SubscriptionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriptions_table_is_created_with_all_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('subscriptions'));

        $expectedColumns = [
            'id',
            'user_id',
            'paddle_subscription_id',
            'paddle_customer_id',
            'paddle_price_id',
            'pricing_plan_id',
            'status',
            'billing_period',
            'next_billed_at',
            'canceled_at',
            'raw_metadata',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('subscriptions', $column),
                "Missing expected column '{$column}' in subscriptions table."
            );
        }
    }

    public function test_migration_up_is_idempotent_and_safe_when_table_already_exists(): void
    {
        // Table already exists from RefreshDatabase
        $this->assertTrue(Schema::hasTable('subscriptions'));

        // Re-running the migration up() method must NOT throw table already exists error
        $migration = require database_path('migrations/2026_09_04_100003_create_subscriptions_table.php');
        $migration->up();

        // Verify table and columns still intact
        $this->assertTrue(Schema::hasTable('subscriptions'));
        $this->assertTrue(Schema::hasColumn('subscriptions', 'paddle_subscription_id'));
        $this->assertTrue(Schema::hasColumn('subscriptions', 'raw_metadata'));
    }

    public function test_transactions_and_other_migrations_are_also_idempotent(): void
    {
        $transactionsMigration = require database_path('migrations/2026_09_04_100004_create_transactions_table.php');
        $transactionsMigration->up();
        $this->assertTrue(Schema::hasTable('transactions'));

        $creditTxMigration = require database_path('migrations/2026_09_04_100005_create_credit_transactions_table.php');
        $creditTxMigration->up();
        $this->assertTrue(Schema::hasTable('credit_transactions'));

        $webhookMigration = require database_path('migrations/2026_09_04_100006_create_paddle_webhook_events_table.php');
        $webhookMigration->up();
        $this->assertTrue(Schema::hasTable('paddle_webhook_events'));
    }
}
