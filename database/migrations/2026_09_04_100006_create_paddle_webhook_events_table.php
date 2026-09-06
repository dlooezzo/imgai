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
        if (!Schema::hasTable('paddle_webhook_events')) {
            Schema::create('paddle_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_id', 191)->unique();
                $table->string('event_type', 100)->index();
                $table->json('payload')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('paddle_webhook_events', function (Blueprint $table) {
                if (!Schema::hasColumn('paddle_webhook_events', 'event_id')) {
                    $table->string('event_id', 191)->nullable()->unique();
                }
                if (!Schema::hasColumn('paddle_webhook_events', 'event_type')) {
                    $table->string('event_type', 100)->default('')->index();
                }
                if (!Schema::hasColumn('paddle_webhook_events', 'payload')) {
                    $table->json('payload')->nullable();
                }
                if (!Schema::hasColumn('paddle_webhook_events', 'processed_at')) {
                    $table->timestamp('processed_at')->nullable();
                }
                if (!Schema::hasColumn('paddle_webhook_events', 'created_at') && !Schema::hasColumn('paddle_webhook_events', 'updated_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
