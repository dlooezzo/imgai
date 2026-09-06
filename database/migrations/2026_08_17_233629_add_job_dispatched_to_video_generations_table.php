<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_generations', function (Blueprint $table) {
            // Tracks whether a polling job has been dispatched to prevent duplicate dispatch
            $table->boolean('job_dispatched')->default(false)->after('prediction_id');
            // Tracks the number of consecutive poll attempts for self-rescheduling jobs
            $table->unsignedSmallInteger('poll_attempts')->default(0)->after('job_dispatched');
        });
    }

    public function down(): void
    {
        Schema::table('video_generations', function (Blueprint $table) {
            $table->dropColumn(['job_dispatched', 'poll_attempts']);
        });
    }
};
