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
    | Default Free Starting Credits
    |--------------------------------------------------------------------------
    |
    | Number of free credits granted to newly registered users if any.
    |
    */
    'default_free_credits' => (int) env('DEFAULT_FREE_CREDITS', 0),
];
