<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('paddle_subscription_id', 191)->unique();
                $table->string('paddle_customer_id', 191)->nullable()->index();
                $table->string('paddle_price_id', 191)->nullable()->index();
                $table->unsignedBigInteger('pricing_plan_id')->nullable()->index();
                $table->string('status', 50)->default('active')->index(); // active, trialing, past_due, paused, canceled
                $table->string('billing_period')->default('monthly'); // monthly, yearly
                $table->timestamp('next_billed_at')->nullable();
                $table->timestamp('canceled_at')->nullable();
                $table->json('raw_metadata')->nullable();
                $table->timestamps();
            });

            try {
                Schema::table('subscriptions', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('subscriptions', function (Blueprint $table) {
                    $table->foreign('pricing_plan_id')->references('id')->on('pricing_plans')->nullOnDelete();
                });
            } catch (\Throwable $e) {}
        } else {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (!Schema::hasColumn('subscriptions', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->index();
                }
                if (!Schema::hasColumn('subscriptions', 'paddle_subscription_id')) {
                    $table->string('paddle_subscription_id', 191)->nullable()->unique();
                }
                if (!Schema::hasColumn('subscriptions', 'paddle_customer_id')) {
                    $table->string('paddle_customer_id', 191)->nullable()->index();
                }
                if (!Schema::hasColumn('subscriptions', 'paddle_price_id')) {
                    $table->string('paddle_price_id', 191)->nullable()->index();
                }
                if (!Schema::hasColumn('subscriptions', 'pricing_plan_id')) {
                    $table->unsignedBigInteger('pricing_plan_id')->nullable()->index();
                }
                if (!Schema::hasColumn('subscriptions', 'status')) {
                    $table->string('status', 50)->default('active')->index();
                }
                if (!Schema::hasColumn('subscriptions', 'billing_period')) {
                    $table->string('billing_period')->default('monthly');
                }
                if (!Schema::hasColumn('subscriptions', 'next_billed_at')) {
                    $table->timestamp('next_billed_at')->nullable();
                }
                if (!Schema::hasColumn('subscriptions', 'canceled_at')) {
                    $table->timestamp('canceled_at')->nullable();
                }
                if (!Schema::hasColumn('subscriptions', 'raw_metadata')) {
                    $table->json('raw_metadata')->nullable();
                }
                if (!Schema::hasColumn('subscriptions', 'created_at') && !Schema::hasColumn('subscriptions', 'updated_at')) {
                    $table->timestamps();
                }
            });

            try {
                Schema::table('subscriptions', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('subscriptions', function (Blueprint $table) {
                    $table->foreign('pricing_plan_id')->references('id')->on('pricing_plans')->nullOnDelete();
                });
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe rollback without dropping pre-existing critical production data
    }
};
