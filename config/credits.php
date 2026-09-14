<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Credit Costs for AI Generations
    |--------------------------------------------------------------------------
    |
    | Fallback defaults ONLY. The authoritative pricing values come from the
    | site_settings database table, accessed via AiCreditPricingService.
    | Admin changes update site_settings and invalidate the cache —
    | NO env file edits, code changes, or redeployments required.
    |
    */
    'costs' => [
        'image_generation' => 1,
        'video_generation' => 5,
        'image_to_video' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Text-to-Video Audio Credit Policy (Seedance 1.5 Pro)
    |--------------------------------------------------------------------------
    |
    | These are OUR application-side credit multipliers. They are NOT official
    | API.market pricing. The Seedance 1.5 Pro API documentation does not
    | provide a credits/pricing table — it only returns completion_tokens and
    | total_tokens in the response.
    |
    | Formula: base × resolution_multiplier × duration_multiplier
    |
    | Audio does NOT add an extra multiplier as the API provides no separate
    | credit cost for audio generation.
    |
    | These are fallback defaults ONLY. The authoritative values live in
    | site_settings (ai_credit.video.* keys). See AiCreditPricingService.
    |
    */
    'text_to_video_audio' => [
        'base' => 5,

        // Application credit policy — NOT API.market pricing
        'resolution_multiplier' => [
            '480p' => 1,
            '720p' => 2,
            '1080p' => 3,
        ],

        // Application credit policy — NOT API.market pricing
        'duration_multiplier' => [
            5 => 1,
            8 => 2,
            12 => 3,
        ],

        // Audio multiplier — Application credit policy
        'audio_multiplier' => [
            'no' => 1,
            'yes' => 2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Free Starting Credits
    |--------------------------------------------------------------------------
    |
    | Number of free credits granted to newly registered users if any.
    | Fallback default. Authoritative value: ai_credit.default_free_credits
    | in the site_settings table.
    |
    */
    'default_free_credits' => 0,
];
