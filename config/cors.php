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

    'allowed_headers' => ['*'],

    // Headers your client may read in responses
    'exposed_headers' => ['Authorization', 'X-CSRF-TOKEN'],

    'max_age' => 0,

    // If using Sanctum with SPA cookie auth, set to true and DO NOT use * in allowed_origins
    'supports_credentials' => filter_var(env('CORS_SUPPORTS_CREDENTIALS', false), FILTER_VALIDATE_BOOLEAN),
];
