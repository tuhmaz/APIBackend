<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\VisitorTrackingMiddleware;
use App\Http\Middleware\UpdateUserLastActivity;
use App\Http\Middleware\LogLastActivity;
use App\Http\Middleware\CompressResponse;
use App\Http\Middleware\RequestMonitorMiddleware;
use App\Http\Middleware\CachePublicResponse;
use App\Http\Middleware\SecurityScanMiddleware;
use App\Http\Middleware\StripContentEncodingHeader;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ApiRateLimiter;
use App\Http\Middleware\CorsMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
      api: __DIR__ . '/../routes/api.php',
      web: __DIR__ . '/../routes/web.php',
      commands: __DIR__ . '/../routes/console.php',
      health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middlewares - Custom CORS that reads directly from env (no cache issues)
        $middleware->use([CorsMiddleware::class]);
        // Route middleware aliases
        $middleware->alias([
            'api.throttle' => ApiRateLimiter::class,
        ]);
        // API middlewares - Minimal for maximum performance
        $middleware->api([
            SecurityHeaders::class,
            UpdateUserLastActivity::class,
            // ApiRateLimiter::class,
            VisitorTrackingMiddleware::class,
        ]);
        // Web middlewares - Optimized for performance
        $middleware->web([
            LocaleMiddleware::class,
            SecurityHeaders::class,
            // Disabled all heavy middlewares for performance
            // CompressResponse::class,
            // CachePublicResponse::class,
            // VisitorTrackingMiddleware::class,
            // UpdateUserLastActivity::class,
            // LogLastActivity::class,
            // RequestMonitorMiddleware::class,
            // SecurityScanMiddleware::class,
            // StripContentEncodingHeader::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
