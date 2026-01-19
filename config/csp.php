<?php

// السماح بـ localhost في بيئة التطوير أو عند استخدام localhost
$localOrigins = (env('APP_ENV') === 'local' || strpos(env('APP_URL'), 'localhost') !== false || strpos(env('APP_URL'), '127.0.0.1') !== false)
    ? [
        'http://localhost',
        'http://localhost:8000',
        'http://127.0.0.1',
        'http://127.0.0.1:8000',
    ]
    : [];

return [
    'enabled' => true,

    // Content Security Policy directives
    'directives' => [
        'default-src' => ["'self'"],
        'base-uri' => ["'self'"],
        'img-src' => array_merge([
            "'self'",
            'data:',
            'https:',
            'https://*.googlesyndication.com',
            'https://*.doubleclick.net',
            'https://*.g.doubleclick.net',
            'https://*.google.com',
        ], $localOrigins),
        'style-src' => ["'self'", 'https:', "'unsafe-inline'"],
        // Allow AdSense and related Google domains
        'script-src' => [
            "'self'",
            'https:',
            "'unsafe-inline'",
            'https://*.googlesyndication.com',
            'https://*.doubleclick.net',
            'https://*.g.doubleclick.net',
            'https://*.googleadservices.com',
            'https://*.googletagservices.com',
            'https://*.googletagmanager.com',
            'https://*.google.com',
        ],
        'script-src-elem' => [
            "'self'",
            'https:',
            "'unsafe-inline'",
            'https://*.googlesyndication.com',
            'https://*.doubleclick.net',
            'https://*.g.doubleclick.net',
            'https://*.googleadservices.com',
            'https://*.googletagservices.com',
            'https://*.googletagmanager.com',
            'https://*.google.com',
        ],
        'connect-src' => [
            "'self'",
            'https:',
            'https://*.googlesyndication.com',
            'https://*.doubleclick.net',
            'https://*.g.doubleclick.net',
            'https://*.googleadservices.com',
            'https://*.googletagservices.com',
            'https://*.googletagmanager.com',
            'https://*.google.com',
        ],
        'frame-src' => [
            "'self'",
            'https://*.doubleclick.net',
            'https://*.g.doubleclick.net',
            'https://*.googlesyndication.com',
            'https://*.google.com',
            'https://*.adtrafficquality.google',
        ],
        'font-src' => ["'self'", 'https:', 'data:'],
        'object-src' => ["'none'"],
        'frame-ancestors' => ["'self'"],
        'form-action' => ["'self'"],
        'upgrade-insecure-requests' => [],
    ],

    // Overrides for monitoring pages if needed (kept empty for now)
    'monitoring_overrides' => [
        // Example: 'img-src' => ['https://maps.gstatic.com']
    ],
];
