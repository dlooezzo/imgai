<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Seedance 1.0 Pro Fast Image-to-Video fields to video_generations table.
 * These columns are required for Image-to-Video (Seedance 1.0 Pro Fast API).
 *
 * Existing compatible columns reused (not duplicated):
 * - resolution (string, nullable) — already present
 * - aspect_ratio (string) — already present
 * - duration (unsignedTinyInteger) — already present from Seedance 1.5 Pro migration
 * - seed (bigInteger, nullable) — already present
 * - camerafixed (boolean) — already present
 * - watermark (boolean) — already present
 *
 * New columns added:
 * - ratio (string) — aspect ratio enum for Seedance API (replaces aspect_ratio for this model)
 * - duration (already exists, reused)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('video_generations', function (Blueprint $table) {
            // ratio field for Seedance API aspect ratio enum
            if (! Schema::hasColumn('video_generations', 'ratio')) {
                $table->string('ratio', 10)->nullable()->after('aspect_ratio');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('video_generations', function (Blueprint $table) {
            $columnsToDrop = array_filter([
                Schema::hasColumn('video_generations', 'ratio') ? 'ratio' : null,
            ]);
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};