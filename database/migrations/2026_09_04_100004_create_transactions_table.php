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
        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('paddle_transaction_id', 191)->unique();
                $table->string('paddle_subscription_id', 191)->nullable()->index();
                $table->string('paddle_price_id', 191)->nullable()->index();
                $table->unsignedBigInteger('pricing_plan_id')->nullable()->index();
                $table->string('status', 50)->default('completed')->index(); // completed, billed, paid, past_due, canceled
                $table->string('amount')->default('0');
                $table->string('currency', 10)->default('USD');
                $table->string('type', 50)->default('subscription'); // subscription, one_time
                $table->timestamp('processed_at')->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamps();
            });

            try {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->foreign('pricing_plan_id')->references('id')->on('pricing_plans')->nullOnDelete();
                });
            } catch (\Throwable $e) {}
        } else {
            Schema::table('transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('transactions', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->index();
                }
                if (!Schema::hasColumn('transactions', 'paddle_transaction_id')) {
                    $table->string('paddle_transaction_id', 191)->nullable()->unique();
                }
                if (!Schema::hasColumn('transactions', 'paddle_subscription_id')) {
                    $table->string('paddle_subscription_id', 191)->nullable()->index();
                }
                if (!Schema::hasColumn('transactions', 'paddle_price_id')) {
                    $table->string('paddle_price_id', 191)->nullable()->index();
                }
                if (!Schema::hasColumn('transactions', 'pricing_plan_id')) {
                    $table->unsignedBigInteger('pricing_plan_id')->nullable()->index();
                }
                if (!Schema::hasColumn('transactions', 'status')) {
                    $table->string('status', 50)->default('completed')->index();
                }
                if (!Schema::hasColumn('transactions', 'amount')) {
                    $table->string('amount')->default('0');
                }
                if (!Schema::hasColumn('transactions', 'currency')) {
                    $table->string('currency', 10)->default('USD');
                }
                if (!Schema::hasColumn('transactions', 'type')) {
                    $table->string('type', 50)->default('subscription');
                }
                if (!Schema::hasColumn('transactions', 'processed_at')) {
                    $table->timestamp('processed_at')->nullable();
                }
                if (!Schema::hasColumn('transactions', 'raw_payload')) {
                    $table->json('raw_payload')->nullable();
                }
                if (!Schema::hasColumn('transactions', 'created_at') && !Schema::hasColumn('transactions', 'updated_at')) {
                    $table->timestamps();
                }
            });

            try {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table('transactions', function (Blueprint $table) {
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
    }
};
