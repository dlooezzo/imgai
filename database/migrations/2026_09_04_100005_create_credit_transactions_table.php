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
        if (!Schema::hasTable('credit_transactions')) {
            Schema::create('credit_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->integer('amount'); // Positive for grant, negative for deduction
                $table->string('type', 50)->index(); // subscription_grant, generation_deduction, generation_refund, admin_adjustment
                $table->string('source', 50)->index(); // paddle_webhook, image_generation, video_generation, image_to_video, admin
                $table->string('reference_id', 191)->nullable()->index(); // paddle_transaction_id or generation_id
                $table->string('description')->default('');
                $table->integer('balance_after')->default(0);
                $table->timestamps();
            });

            try {
                Schema::table('credit_transactions', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                });
            } catch (\Throwable $e) {}
        } else {
            Schema::table('credit_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('credit_transactions', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->index();
                }
                if (!Schema::hasColumn('credit_transactions', 'amount')) {
                    $table->integer('amount')->default(0);
                }
                if (!Schema::hasColumn('credit_transactions', 'type')) {
                    $table->string('type', 50)->default('subscription_grant')->index();
                }
                if (!Schema::hasColumn('credit_transactions', 'source')) {
                    $table->string('source', 50)->default('paddle_webhook')->index();
                }
                if (!Schema::hasColumn('credit_transactions', 'reference_id')) {
                    $table->string('reference_id', 191)->nullable()->index();
                }
                if (!Schema::hasColumn('credit_transactions', 'description')) {
                    $table->string('description')->default('');
                }
                if (!Schema::hasColumn('credit_transactions', 'balance_after')) {
                    $table->integer('balance_after')->default(0);
                }
                if (!Schema::hasColumn('credit_transactions', 'created_at') && !Schema::hasColumn('credit_transactions', 'updated_at')) {
                    $table->timestamps();
                }
            });

            try {
                Schema::table('credit_transactions', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
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
