<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CORS Middleware - Handles Cross-Origin Resource Sharing
 *
 * This middleware handles CORS without relying on config cache.
 * It reads allowed origins directly from environment variables.
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
     * Allowed headers
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
        'X-App-Locale',
        'X-Country-Id',
        'X-Country-Code',
    ];

    /**
     * Exposed headers (client can read these)
     */
    protected array $exposedHeaders = [
        'Authorization',
        'X-CSRF-TOKEN',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the origin from the request
        $origin = $request->header('Origin');

        // Check if this origin is allowed
        $allowedOrigin = $this->getAllowedOrigin($origin);

        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            return $this->handlePreflightRequest($allowedOrigin);
        }

        // Process the request
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
     */
    protected function handlePreflightRequest(?string $allowedOrigin): Response
    {
        $response = response('', 204);

        if ($allowedOrigin) {
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
            $response->headers->set('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
            $response->headers->set('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400');
        }

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

        return $response;
    }
}
