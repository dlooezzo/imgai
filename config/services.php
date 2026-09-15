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
        // Text-to-Video Audio (Seedance 1.5 Pro)
        'video_base_url' => env('SEEDANCE_VIDEO_BASE_URL', 'https://prod.api.market/api/v1/byteplus/seedance-text-to-video-1-5-pro'),
        'video_version' => env('SEEDANCE_VIDEO_VERSION', 'text-to-video-1-5-pro'),
        // Image-to-Video (BytePlus Seedance 1.0 Pro Fast)
        'image_to_video_base_url' => env('SEEDANCE_IMAGE_TO_VIDEO_BASE_URL', 'https://prod.api.market/api/v1/byteplus/seedance-image-to-video-pro-fast'),
        'image_to_video_version' => env('SEEDANCE_IMAGE_TO_VIDEO_VERSION', 'image-to-video-pro-fast'),
        // Seedance 1.5 Pro — Text-to-Video Audio
        'seedance_video_base_url' => env('SEEDANCE_VIDEO_BASE_URL', 'https://prod.api.market/api/v1/byteplus/seedance-text-to-video-1-5-pro'),
        // BytePlus Seedance 1.0 Pro Fast — Image-to-Video
        'seedance_image_to_video_base_url' => env('SEEDANCE_IMAGE_TO_VIDEO_BASE_URL', 'https://prod.api.market/api/v1/byteplus/seedance-image-to-video-pro-fast'),
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
