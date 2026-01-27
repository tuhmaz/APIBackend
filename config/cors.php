<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configure cross-origin resource sharing settings for your application.
    | Adjust origins and credentials via environment variables.
    |
    | CORS_ALLOWED_ORIGINS: comma-separated list of allowed origins
    |   e.g. https://example.com,https://admin.example.com
    |   Use * ONLY for local development.
    | CORS_SUPPORTS_CREDENTIALS: true/false (required true for Sanctum cookie auth)
    |
    */

    'paths' => [
        'api/*',
        'oauth/*',
        'sanctum/csrf-cookie',
        // Add additional paths as needed
    ],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Comma-separated in .env, example: https://example.com,https://admin.example.com
    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '*')))),

    'allowed_origins_patterns' => [],

    // Explicitly list allowed headers (don't use '*' with credentials)
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'Accept',
        'Origin',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-API-KEY',
        'X-Frontend-Key',
        'X-App-Locale',
        'X-Country-Id',
        'X-Country-Code',
    ],

    // Headers your client may read in responses
    'exposed_headers' => ['Authorization', 'X-CSRF-TOKEN'],

    'max_age' => 86400, // Cache preflight for 24 hours

    // Required for Sanctum cookie auth and frontend with credentials
    'supports_credentials' => true,
];
