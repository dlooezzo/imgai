<?php

namespace App\Services\Credits;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class AiCreditPricingService
{
    /**
     * Cache tag or key prefix for pricing settings.
     */
    protected const CACHE_KEY = 'ai_credit_pricing_policy';

    /**
     * Get the authoritative credit cost for Image Generation.
     * Database is the source of truth, falling back to config only if unset.
     */
    public function getImageGenerationCost(): int
    {
        $val = SiteSetting::get('ai_credit.image_generation');
        if ($val !== null && is_numeric($val)) {
            return max(1, (int) $val);
        }

        return (int) config('credits.costs.image_generation', 1);
    }

    /**
     * Get the authoritative credit cost for Image-to-Video.
     */
    public function getImageToVideoCost(): int
    {
        $val = SiteSetting::get('ai_credit.image_to_video');
        if ($val !== null && is_numeric($val)) {
            return max(1, (int) $val);
        }

        return (int) config('credits.costs.image_to_video', 5);
    }

    /**
     * Get the base credit cost for Text-to-Video Audio.
     */
    public function getVideoBaseCost(): int
    {
        $val = SiteSetting::get('ai_credit.video.base');
        if ($val !== null && is_numeric($val)) {
            return max(1, (int) $val);
        }

        return (int) config('credits.text_to_video_audio.base', config('credits.costs.video_generation', 5));
    }

    /**
     * Get the multiplier for a given resolution (480p, 720p, 1080p).
     */
    public function getVideoResolutionMultiplier(string $resolution): int
    {
        $key = 'ai_credit.video.resolution.'.strtolower($resolution);
        $val = SiteSetting::get($key);
        if ($val !== null && is_numeric($val)) {
            return max(1, (int) $val);
        }

        $configMultipliers = config('credits.text_to_video_audio.resolution_multiplier', [
            '480p' => 1,
            '720p' => 2,
            '1080p' => 3,
        ]);

        return (int) ($configMultipliers[$resolution] ?? 1);
    }

    /**
     * Get the multiplier for a given duration (5, 8, 12 seconds).
     */
    public function getVideoDurationMultiplier(int|string $duration): int
    {
        $durInt = (int) $duration;
        $key = 'ai_credit.video.duration.'.$durInt;
        $val = SiteSetting::get($key);
        if ($val !== null && is_numeric($val)) {
            return max(1, (int) $val);
        }

        $configMultipliers = config('credits.text_to_video_audio.duration_multiplier', [
            5 => 1,
            8 => 2,
            12 => 3,
        ]);

        return (int) ($configMultipliers[$durInt] ?? 1);
    }

    /**
     * Get the multiplier for audio generation (No Audio vs With Audio).
     */
    public function getVideoAudioMultiplier(bool $generateAudio): int
    {
        $key = $generateAudio ? 'ai_credit.video.audio.yes' : 'ai_credit.video.audio.no';
        $val = SiteSetting::get($key);
        if ($val !== null && is_numeric($val)) {
            return max(1, (int) $val);
        }

        $configMultipliers = config('credits.text_to_video_audio.audio_multiplier', [
            'no' => 1,
            'yes' => 2,
        ]);

        return (int) ($generateAudio ? ($configMultipliers['yes'] ?? 2) : ($configMultipliers['no'] ?? 1));
    }

    /**
     * Authoritative calculation for Text-to-Video Audio credit cost.
     * Formula: Base × Resolution Multiplier × Duration Multiplier × Audio Multiplier
     */
    public function calculateTextToVideoCost(string $resolution, int|string $duration, bool $generateAudio = false): int
    {
        $base = $this->getVideoBaseCost();
        $resMult = $this->getVideoResolutionMultiplier($resolution);
        $durMult = $this->getVideoDurationMultiplier($duration);
        $audioMult = $this->getVideoAudioMultiplier($generateAudio);

        return max(1, $base * $resMult * $durMult * $audioMult);
    }

    /**
     * Get default free starting credits for new users.
     */
    public function getDefaultFreeCredits(): int
    {
        $val = SiteSetting::get('ai_credit.default_free_credits');
        if ($val !== null && is_numeric($val)) {
            return max(0, (int) $val);
        }

        return (int) config('credits.default_free_credits', 0);
    }

    /**
     * Build the structured policy payload for frontend consumers and controllers.
     */
    public function getTextToVideoPolicy(): array
    {
        return [
            'base' => $this->getVideoBaseCost(),
            'resolution_multiplier' => [
                '480p' => $this->getVideoResolutionMultiplier('480p'),
                '720p' => $this->getVideoResolutionMultiplier('720p'),
                '1080p' => $this->getVideoResolutionMultiplier('1080p'),
            ],
            'duration_multiplier' => [
                5 => $this->getVideoDurationMultiplier(5),
                8 => $this->getVideoDurationMultiplier(8),
                12 => $this->getVideoDurationMultiplier(12),
            ],
            'audio_multiplier' => [
                'no' => $this->getVideoAudioMultiplier(false),
                'yes' => $this->getVideoAudioMultiplier(true),
            ],
        ];
    }

    /**
     * Retrieve all current pricing settings for the Admin interface.
     */
    public function getAllSettings(): array
    {
        return [
            'image_generation' => $this->getImageGenerationCost(),
            'image_to_video' => $this->getImageToVideoCost(),
            'video_base' => $this->getVideoBaseCost(),
            'video_resolution_480p' => $this->getVideoResolutionMultiplier('480p'),
            'video_resolution_720p' => $this->getVideoResolutionMultiplier('720p'),
            'video_resolution_1080p' => $this->getVideoResolutionMultiplier('1080p'),
            'video_duration_5' => $this->getVideoDurationMultiplier(5),
            'video_duration_8' => $this->getVideoDurationMultiplier(8),
            'video_duration_12' => $this->getVideoDurationMultiplier(12),
            'video_audio_no' => $this->getVideoAudioMultiplier(false),
            'video_audio_yes' => $this->getVideoAudioMultiplier(true),
            'default_free_credits' => $this->getDefaultFreeCredits(),
        ];
    }

    /**
     * Save settings from validated Admin request and immediately flush caches.
     */
    public function updateSettings(array $data): void
    {
        $mapping = [
            'image_generation' => 'ai_credit.image_generation',
            'image_to_video' => 'ai_credit.image_to_video',
            'video_base' => 'ai_credit.video.base',
            'video_resolution_480p' => 'ai_credit.video.resolution.480p',
            'video_resolution_720p' => 'ai_credit.video.resolution.720p',
            'video_resolution_1080p' => 'ai_credit.video.resolution.1080p',
            'video_duration_5' => 'ai_credit.video.duration.5',
            'video_duration_8' => 'ai_credit.video.duration.8',
            'video_duration_12' => 'ai_credit.video.duration.12',
            'video_audio_no' => 'ai_credit.video.audio.no',
            'video_audio_yes' => 'ai_credit.video.audio.yes',
            'default_free_credits' => 'ai_credit.default_free_credits',
        ];

        foreach ($mapping as $inputKey => $settingKey) {
            if (array_key_exists($inputKey, $data) && $data[$inputKey] !== null) {
                SiteSetting::set($settingKey, (string) $data[$inputKey]);
            }
        }

        $this->flushCache();
    }

    /**
     * Invalidate all runtime, SiteSetting, and application level caches.
     */
    public function flushCache(): void
    {
        SiteSetting::flushCache();
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Generate the complete calculation matrix for Admin Preview.
     */
    public function getPreviewMatrix(): array
    {
        $resolutions = ['480p', '720p', '1080p'];
        $durations = [5, 8, 12];
        $audioStates = [
            false => 'No',
            true => 'Yes',
        ];

        $matrix = [];

        foreach ($resolutions as $res) {
            foreach ($durations as $dur) {
                foreach ($audioStates as $hasAudio => $audioLabel) {
                    $matrix[] = [
                        'resolution' => $res,
                        'duration' => $dur.'s',
                        'audio' => $audioLabel,
                        'has_audio' => $hasAudio,
                        'base' => $this->getVideoBaseCost(),
                        'res_mult' => $this->getVideoResolutionMultiplier($res),
                        'dur_mult' => $this->getVideoDurationMultiplier($dur),
                        'audio_mult' => $this->getVideoAudioMultiplier($hasAudio),
                        'total_credits' => $this->calculateTextToVideoCost($res, $dur, $hasAudio),
                    ];
                }
            }
        }

        return $matrix;
    }
}
