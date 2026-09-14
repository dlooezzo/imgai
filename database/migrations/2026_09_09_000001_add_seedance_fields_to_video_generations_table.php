<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Seedance 1.5 Pro fields to video_generations table.
     * These columns are required for Text-to-Video Audio (Seedance API).
     *
     * Existing compatible columns reused (not duplicated):
     * - resolution (string, nullable) — already present
     * - aspect_ratio (string) — already present
     *
     * New dedicated columns added:
     * - duration (integer, seconds) — Seedance duration param
     * - generate_audio (boolean) — Seedance generate_audio param
     * - seed (bigint, nullable) — Seedance seed param
     * - camerafixed (boolean) — Seedance camerafixed param
     * - watermark (boolean) — Seedance watermark param
     */
    public function up(): void
    {
        Schema::table('video_generations', function (Blueprint $table) {
            if (! Schema::hasColumn('video_generations', 'aspect_ratio')) {
                $table->string('aspect_ratio', 10)->default('16:9');
            }
            if (! Schema::hasColumn('video_generations', 'resolution')) {
                $table->string('resolution', 10)->nullable();
            }
            if (! Schema::hasColumn('video_generations', 'duration')) {
                $table->unsignedTinyInteger('duration')->default(5);
            }
            if (! Schema::hasColumn('video_generations', 'generate_audio')) {
                $table->boolean('generate_audio')->default(false);
            }
            if (! Schema::hasColumn('video_generations', 'seed')) {
                $table->bigInteger('seed')->nullable();
            }
            if (! Schema::hasColumn('video_generations', 'camerafixed')) {
                $table->boolean('camerafixed')->default(false);
            }
            if (! Schema::hasColumn('video_generations', 'watermark')) {
                $table->boolean('watermark')->default(false);
            }
            if (! Schema::hasColumn('video_generations', 'credits_charged')) {
                $table->unsignedInteger('credits_charged')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_generations', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('video_generations', 'duration') ? 'duration' : null,
                Schema::hasColumn('video_generations', 'generate_audio') ? 'generate_audio' : null,
                Schema::hasColumn('video_generations', 'seed') ? 'seed' : null,
                Schema::hasColumn('video_generations', 'camerafixed') ? 'camerafixed' : null,
                Schema::hasColumn('video_generations', 'watermark') ? 'watermark' : null,
                Schema::hasColumn('video_generations', 'credits_charged') ? 'credits_charged' : null,
            ]));
        });
    }
};
