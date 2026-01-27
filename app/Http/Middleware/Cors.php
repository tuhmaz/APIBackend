<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    /**
     * Get allowed origins from environment variable
     * Configure via CORS_ALLOWED_ORIGINS in .env (comma-separated)
     */
    protected function getAllowedOrigins(): array
    {
        // Default origins (always allowed)
        $defaults = [
            'http://localhost:3000',  // Development
            'http://localhost:3001',  // Development
        ];

        // Get origins from .env
        $envOrigins = env('CORS_ALLOWED_ORIGINS', '');
        if ($envOrigins) {
            $origins = array_filter(array_map('trim', explode(',', $envOrigins)));
            return array_merge($defaults, $origins);
        }

        // Fallback to defaults
        return $defaults;
    }

    public function handle(Request $request, Closure $next)
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigins = $this->getAllowedOrigins();

        // Check if origin is allowed
        $allowedOrigin = null;
        if ($origin && in_array($origin, $allowedOrigins)) {
            $allowedOrigin = $origin;
        }

        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', $allowedOrigin ?: '')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-Token, X-API-KEY, X-Frontend-Key, X-App-Locale, X-Country-Id, X-Country-Code')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400'); // Cache preflight for 24 hours
        }

        $response = $next($request);

        // Add CORS headers only if origin is allowed
        if ($allowedOrigin) {
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-CSRF-Token, X-API-KEY, X-Frontend-Key, X-App-Locale, X-Country-Id, X-Country-Code');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
