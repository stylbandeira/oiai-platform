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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'nominatim' => [
        'email' => env('APP_MAIL'),
        'url' => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org/search')
    ],

    'cosmos' => [
        'token' => env('COSMOS_API_TOKEN'),
        'url' => env('COSMOS_API_URL', 'https://api.cosmos.bluesoft.com.br'),
        'user_agent' => env('COSMOS_API_USER_AGENT', 'Cosmos-API-Request'),
        'batch_size' => env('COSMOS_API_DAILY_PRODUCT_COUNT', 7),
        'recurrence_minutes' => env('COSMOS_API_RECURRENCE_MINUTES', 180),
        'daily_limit' => env('COSMOS_API_DAILY_LIMIT', 45),
    ],

    'oscbr' => [
        'login' => env('OSCBR_API_LOGIN'),
        'password' => env('OSCBR_API_PASSWORD'),
        'auth_url' => env('OSCBR_AUTH_URL', 'https://gtin.rscsistemas.com.br/api/v3/oauth/token'),
        'product_url' => env('OSCBR_API_URL', 'https://gtin.rscsistemas.com.br/api/v3/gtin'),
        'batch_size' => env('OSCBR_API_DAILY_PRODUCT_COUNT', 5),
        'recurrence_minutes' => env('OSCBR_API_RECURRENCE_MINUTES', 5),
        'daily_limit' => env('OSCBR_API_DAILY_LIMIT', 50),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
