<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CORS Middleware - Handles Cross-Origin Resource Sharing
 *
 * IMPORTANT: This middleware MUST be the first in the chain.
 * It handles OPTIONS preflight requests IMMEDIATELY without passing to other middlewares.
 * This prevents auth/guard middlewares from blocking preflight requests.
 *
 * Configuration via .env:
 * - CORS_ALLOWED_ORIGINS: comma-separated list of allowed origins
 * - FRONTEND_URL: automatically added to allowed origins
 */
class CorsMiddleware
{
    /**
     * Allowed HTTP methods
     */
    protected array $allowedMethods = [
        'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'
    ];

    /**
     * Allowed headers - Include ALL custom headers your frontend sends
     */
    protected array $allowedHeaders = [
        'Content-Type',
        'Authorization',
        'Accept',
        'Origin',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-API-KEY',
        'X-Frontend-Key',
        'X-Frontend-Api-Key',
        'X-App-Locale',
        'X-Country-Id',
        'X-Country-Code',
        'Cache-Control',
        'Pragma',
    ];

    /**
     * Exposed headers (client can read these)
     */
    protected array $exposedHeaders = [
        'Authorization',
        'X-CSRF-TOKEN',
        'Content-Disposition',
    ];

    /**
     * Handle an incoming request.
     *
     * CRITICAL: OPTIONS requests are handled immediately and returned.
     * They NEVER pass through to other middlewares or controllers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the origin from the request
        $origin = $request->header('Origin');

        // Check if this origin is allowed
        $allowedOrigin = $this->getAllowedOrigin($origin);

        // Handle preflight OPTIONS request IMMEDIATELY
        // Do NOT pass to $next - this prevents other middlewares from blocking it
        if ($request->isMethod('OPTIONS')) {
            return $this->handlePreflightRequest($origin, $allowedOrigin);
        }

        // Process the actual request
        $response = $next($request);

        // Add CORS headers to the response
        return $this->addCorsHeaders($response, $allowedOrigin);
    }

    /**
     * Get the allowed origin (returns the origin if allowed, null otherwise)
     */
    protected function getAllowedOrigin(?string $origin): ?string
    {
        if (!$origin) {
            return null;
        }

        $allowedOrigins = $this->getAllowedOrigins();

        // Check if origin is in allowed list
        if (in_array($origin, $allowedOrigins, true)) {
            return $origin;
        }

        // Check for wildcard (development only)
        if (in_array('*', $allowedOrigins, true)) {
            return $origin;
        }

        return null;
    }

    /**
     * Get list of allowed origins from environment
     */
    protected function getAllowedOrigins(): array
    {
        $origins = [];

        // Add from CORS_ALLOWED_ORIGINS
        $corsOrigins = env('CORS_ALLOWED_ORIGINS', '');
        if ($corsOrigins && $corsOrigins !== '*') {
            $origins = array_merge($origins, array_filter(array_map('trim', explode(',', $corsOrigins))));
        } elseif ($corsOrigins === '*') {
            return ['*'];
        }

        // Add FRONTEND_URL
        $frontendUrl = env('FRONTEND_URL');
        if ($frontendUrl) {
            $origins[] = $frontendUrl;
            // Add www variant
            $parsed = parse_url($frontendUrl);
            if (isset($parsed['host']) && !str_starts_with($parsed['host'], 'www.')) {
                $origins[] = ($parsed['scheme'] ?? 'https') . '://www.' . $parsed['host'];
            }
        }

        // Always allow localhost for development
        $origins[] = 'http://localhost:3000';
        $origins[] = 'http://localhost:3001';
        $origins[] = 'http://127.0.0.1:3000';

        return array_unique(array_filter($origins));
    }

    /**
     * Handle preflight OPTIONS request
     *
     * IMPORTANT: Always return headers even if origin is not allowed.
     * This helps with debugging and doesn't expose security issues
     * (the actual request will still be blocked if origin is invalid).
     */
    protected function handlePreflightRequest(?string $requestOrigin, ?string $allowedOrigin): Response
    {
        $response = response()->noContent(204);

        // If origin is allowed, set it. Otherwise, don't set Access-Control-Allow-Origin
        // (browser will block the request, but we still send other headers for debugging)
        if ($allowedOrigin) {
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        // Always send these headers for preflight
        $response->headers->set('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
        $response->headers->set('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
        $response->headers->set('Access-Control-Max-Age', '86400'); // 24 hours

        // Vary header is important for caching
        $response->headers->set('Vary', 'Origin, Access-Control-Request-Headers');

        return $response;
    }

    /**
     * Add CORS headers to response
     */
    protected function addCorsHeaders(Response $response, ?string $allowedOrigin): Response
    {
        if ($allowedOrigin) {
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');

            if (!empty($this->exposedHeaders)) {
                $response->headers->set('Access-Control-Expose-Headers', implode(', ', $this->exposedHeaders));
            }
        }

        // Vary header is important for proper caching
        $response->headers->set('Vary', 'Origin');

        return $response;
    }
}
