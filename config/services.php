<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'magicapi' => [
        'base_url' => env('MAGICAPI_BASE_URL', 'https://prod.api.market/api/v1/magicapi/cinematic-text-to-image-generator'),
        'key' => env('API_MARKET_KEY'),
        'version' => env('MAGICAPI_MODEL_VERSION', '16e15e913fcc71c1a5defb335ea84739f99731fa1ee17995117c7d9adc6d176c'),
        'video_base_url' => env('MAGICAPI_VIDEO_BASE_URL'),
        'video_version' => env('MAGICAPI_VIDEO_VERSION', '6c9132aee14409cd6568d030453f1ba50f5f3412b844fe67f78a9eb62d55664f'),
        'image_to_video_base_url' => env('MAGICAPI_IMAGE_TO_VIDEO_BASE_URL', 'https://prod.api.market/api/v1/magicapi/ultra-fast-text-to-image-image-to-video-api'),
        'image_to_video_version' => env('MAGICAPI_IMAGE_TO_VIDEO_VERSION', 'c92ab4265c9b3b5ea9ac9a87df839ebfd662ee3a820d62c21305bf6501a73fe1'),
        // Seedance 1.5 Pro — Text-to-Video Audio
        'seedance_video_base_url' => env('SEEDANCE_VIDEO_BASE_URL', 'https://prod.api.market/api/v1/byteplus/seedance-text-to-video-1-5-pro'),
    ],

    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'anon_key' => env('SUPABASE_ANON_KEY', env('SUPABASE_KEY')),
        'service_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
    ],

    'paddle' => [
        'api_key' => env('PADDLE_API_KEY'),
        'client_token' => env('PADDLE_CLIENT_TOKEN'),
        'webhook_secret' => env('PADDLE_WEBHOOK_SECRET'),
        'environment' => env('PADDLE_ENV', 'sandbox'),
    ],

];

