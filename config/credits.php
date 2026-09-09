<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Credit Costs for AI Generations
    |--------------------------------------------------------------------------
    |
    | Configure the credit cost deducted per generation request.
    | These can be overridden via environment variables.
    |
    */
    'costs' => [
        'image_generation' => (int) env('CREDIT_COST_IMAGE_GENERATION', 1),
        'video_generation' => (int) env('CREDIT_COST_VIDEO_GENERATION', 5),
        'image_to_video'   => (int) env('CREDIT_COST_IMAGE_TO_VIDEO', 5),
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
    */
    'text_to_video_audio' => [
        'base' => (int) env('CREDIT_COST_VIDEO_GENERATION', 5),

        // Application credit policy — NOT API.market pricing
        'resolution_multiplier' => [
            '480p'  => (int) env('CREDIT_MULTIPLIER_480P', 1),
            '720p'  => (int) env('CREDIT_MULTIPLIER_720P', 2),
            '1080p' => (int) env('CREDIT_MULTIPLIER_1080P', 3),
        ],

        // Application credit policy — NOT API.market pricing
        'duration_multiplier' => [
            5  => (int) env('CREDIT_MULTIPLIER_5S', 1),
            8  => (int) env('CREDIT_MULTIPLIER_8S', 2),
            12 => (int) env('CREDIT_MULTIPLIER_12S', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Free Starting Credits
    |--------------------------------------------------------------------------
    |
    | Number of free credits granted to newly registered users if any.
    |
    */
    'default_free_credits' => (int) env('DEFAULT_FREE_CREDITS', 0),
];
