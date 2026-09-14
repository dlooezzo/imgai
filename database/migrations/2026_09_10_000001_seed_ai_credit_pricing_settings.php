<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seed initial AI Credit Pricing settings into site_settings table.
     * Database is the authoritative source of truth.
     */
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $defaultSettings = [
            // Fixed AI Credit Costs
            'ai_credit.image_generation' => '1',
            'ai_credit.image_to_video' => '5',
            'ai_credit.video.base' => '5',

            // Resolution Multipliers
            'ai_credit.video.resolution.480p' => '1',
            'ai_credit.video.resolution.720p' => '2',
            'ai_credit.video.resolution.1080p' => '3',

            // Duration Multipliers
            'ai_credit.video.duration.5' => '1',
            'ai_credit.video.duration.8' => '2',
            'ai_credit.video.duration.12' => '3',

            // Audio Multipliers
            'ai_credit.video.audio.no' => '1',
            'ai_credit.video.audio.yes' => '2',

            // Default Free Starting Credits
            'ai_credit.default_free_credits' => '0',
        ];

        foreach ($defaultSettings as $key => $defaultValue) {
            // Only seed if the key does not already exist in the database
            if (! SiteSetting::where('key', $key)->exists()) {
                SiteSetting::create([
                    'key' => $key,
                    'value' => $defaultValue,
                ]);
            }
        }

        SiteSetting::flushCache();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $keys = [
            'ai_credit.image_generation',
            'ai_credit.image_to_video',
            'ai_credit.video.base',
            'ai_credit.video.resolution.480p',
            'ai_credit.video.resolution.720p',
            'ai_credit.video.resolution.1080p',
            'ai_credit.video.duration.5',
            'ai_credit.video.duration.8',
            'ai_credit.video.duration.12',
            'ai_credit.video.audio.no',
            'ai_credit.video.audio.yes',
            'ai_credit.default_free_credits',
        ];

        SiteSetting::whereIn('key', $keys)->delete();
        SiteSetting::flushCache();
    }
};
