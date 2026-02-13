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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'plot' => [
        'base_url' => env('PLOT_API_BASE', env('VITE_PLOT_API_BASE')),
        'api_key' => env('PLOT_API_KEY', env('VITE_PLOT_API_KEY')),
        // Solo para debug: si es true, se loggea el API key completo en laravel.log
        'log_api_key' => env('PLOT_LOG_API_KEY', true),
    ],

    // ML puede usar un host/key distinto; si no se define, hereda de plot
    'ml' => [
        'base_url' => env('ML_API_BASE', env('PLOT_API_BASE', env('VITE_PLOT_API_BASE'))),
        'api_key' => env('ML_API_KEY', env('PLOT_API_KEY', env('VITE_PLOT_API_KEY'))),
        'time_column' => env('ML_TIME_COLUMN', 'measurement_time'),
        // Solo para debug: si es true, se loggea el API key completo en laravel.log
        'log_api_key' => env('ML_LOG_API_KEY', true),
    ],

];
