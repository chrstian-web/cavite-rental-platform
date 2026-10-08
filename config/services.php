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

    // Online payments. 'fake' = built-in test checkout (local/testing only, no real money).
    // 'paymongo' = real PayMongo checkout (use sk_test_ keys for testing, sk_live_ for real money).
    'payment_gateway' => env('PAYMENT_GATEWAY', 'fake'),

    'paymongo' => [
        'secret_key' => env('PAYMONGO_SECRET_KEY', ''),
        'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET', ''),
        'payment_methods' => ['card', 'gcash', 'qrph'],
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
