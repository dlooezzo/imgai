<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Migrate AI Model settings from legacy Tencent Hunyuan to Seedance 1.5 Pro.
 *
 * Idempotent: only updates records that still have the legacy Hunyuan values.
 * Admin edits in the UI are always preserved.
 *
 * Production-safe for MySQL/RDS — does not assume SQLite.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        // Only seed if the key does not already exist in the database
        // This ensures Admin edits are never overwritten
        $defaultSettings = [
            // Text-to-Video Audio (Seedance 1.5 Pro)
            'ai_model_video_name'    => 'Seedance 1.5 Pro (Text-to-Video Audio)',
            'ai_model_video_url'     => 'https://prod.api.market/api/v1/byteplus/seedance-text-to-video-1-5-pro',
            'ai_model_video_version' => 'text-to-video-1-5-pro',
        ];

        foreach ($defaultSettings as $key => $defaultValue) {
            if (! SiteSetting::where('key', $key)->exists()) {
                SiteSetting::create([
                    'key'   => $key,
                    'value' => $defaultValue,
                ]);
            }
        }

        // Migrate any legacy settings that still have old Hunyuan values
        $legacyCheck = SiteSetting::where('key', 'ai_model_video_name')
            ->where('value', 'Tencent Hunyuan-Video (Text-to-Video)')
            ->exists();

        if ($legacyCheck) {
            SiteSetting::set('ai_model_video_name', 'Seedance 1.5 Pro (Text-to-Video Audio)');
        }

        $legacyUrlCheck = SiteSetting::where('key', 'ai_model_video_url')
            ->where('value', 'https://prod.api.market/api/v1/magicapi/hunyuan-video')
            ->exists();

        if ($legacyUrlCheck) {
            SiteSetting::set('ai_model_video_url', 'https://prod.api.market/api/v1/byteplus/seedance-text-to-video-1-5-pro');
        }

        // Set the legacy Hunyuan version hash to the Seedance model identifier
        $legacyVersionCheck = SiteSetting::where('key', 'ai_model_video_version')
            ->where('value', '6c9132aee14409cd6568d030453f1ba50f5f3412b844fe67f78a9eb62d55664f')
            ->exists();

        if ($legacyVersionCheck) {
            SiteSetting::set('ai_model_video_version', 'text-to-video-1-5-pro');
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

        // Restore legacy values
        SiteSetting::set('ai_model_video_name', 'Tencent Hunyuan-Video (Text-to-Video)');
        SiteSetting::set('ai_model_video_version', '6c9132aee14409cd6568d030453f1ba50f5f3412b844fe67f78a9eb62d55664f');

        SiteSetting::flushCache();
    }
};
