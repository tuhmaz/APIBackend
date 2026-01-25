<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for different types of requests
    | Format: 'attempts,decay_minutes'
    |
    */

    // Enable rate limiting globally
    'enabled' => env('RATE_LIMITING_ENABLED', true),

    // Log throttled requests
    'log_throttled_requests' => env('RATE_LIMITING_LOG', true),

    // Response configuration
    'response_code' => 429,
    'error_message' => 'Too many requests. Please try again in :seconds seconds.',
    'blocked_ip_message' => 'This IP address has been blocked due to suspicious activity. Please contact support.',

    // Default block duration when rate limit exceeded (minutes)
    'default_block_duration' => 15,

    /*
    |--------------------------------------------------------------------------
    | Global Rate Limits
    |--------------------------------------------------------------------------
    */
    'global' => [
        // API requests: 60 requests per minute
        'api' => env('API_RATE_LIMIT', '60,1'),

        // Web requests: 120 requests per minute
        'web' => env('WEB_RATE_LIMIT', '120,1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route-Specific Rate Limits
    |--------------------------------------------------------------------------
    |
    | Define stricter limits for sensitive endpoints
    |
    */
    'routes' => [
        // Authentication endpoints - very strict
        'api.auth.login' => '5,1',              // 5 attempts per minute
        'api.auth.register' => '3,10',          // 3 attempts per 10 minutes
        'api.auth.forgot-password' => '3,60',   // 3 attempts per hour
        'api.auth.reset-password' => '3,60',    // 3 attempts per hour

        // File uploads - moderate limit
        'api.upload.*' => '10,1',               // 10 uploads per minute

        // Dashboard admin actions - moderate limit
        'dashboard.users.*' => '30,1',
        'dashboard.settings.*' => '20,1',
        'dashboard.security.*' => '40,1',

        // Frontend posts - generous limit
        'api.posts.*' => '100,1',               // 100 requests per minute
        'api.posts.index' => '120,1',           // 120 requests per minute
        'api.posts.show' => '120,1',

        // Frontend categories
        'api.categories.*' => '100,1',

        // Frontend school classes and subjects
        'api.school-classes.*' => '80,1',
        'api.subjects.*' => '80,1',

        // Comments and reactions
        'api.comments.*' => '20,1',             // 20 comments per minute
        'api.reactions.*' => '30,1',            // 30 reactions per minute

        // Front public endpoints (settings/contact/members)
        'api.front.contact' => '5,10',          // 5 contact submissions per 10 minutes
        'api.front.members.contact' => '5,10',  // 5 member contact submissions per 10 minutes
        'api.front.settings' => '120,1',
        'api.front.members.*' => '120,1',
        'api.front.*' => '120,1',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Type Rate Limits
    |--------------------------------------------------------------------------
    |
    | Different limits based on user authentication status
    |
    */
    'users' => [
        'guest' => '60,1',          // Guests: 60 requests per minute
        'default' => '120,1',       // Authenticated users: 120 per minute
        'admin' => '500,1',         // Admins: 500 per minute
    ],

    /*
    |--------------------------------------------------------------------------
    | Blocked IPs
    |--------------------------------------------------------------------------
    |
    | List of permanently blocked IP addresses
    | Supports wildcards: 192.168.1.* will block entire subnet
    |
    */
    'blocked_ips' => [
        // Add suspicious IPs here
        // '192.168.1.100',
        // '10.0.0.*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trusted IPs
    |--------------------------------------------------------------------------
    |
    | IPs that bypass rate limiting (e.g., monitoring services)
    |
    */
    'trusted_ips' => [
        // '127.0.0.1',
        // Add your server monitoring IPs here
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Protection
    |--------------------------------------------------------------------------
    */

    // Auto-block IP after X violations in Y minutes
    'auto_block' => [
        'enabled' => env('AUTO_BLOCK_ENABLED', true),
        'violations' => 10,         // Number of violations
        'time_window' => 10,        // Within X minutes
        'block_duration' => 60,     // Block for X minutes
    ],

    // Exponential backoff for repeated violations
    'exponential_backoff' => [
        'enabled' => env('EXPONENTIAL_BACKOFF_ENABLED', true),
        'multiplier' => 2,          // Double the block time each violation
        'max_duration' => 1440,     // Maximum 24 hours
    ],
];
