<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    /**
     * Allowed origins for CORS
     * Production: Only alemancenter.com domains
     * Development: localhost for testing
     */
    protected $allowedOrigins = [
        'https://alemancenter.com',
        'https://www.alemancenter.com',
        'http://localhost:3000',  // Development only
        'http://localhost:3001',  // Development only
    ];

    public function handle(Request $request, Closure $next)
    {
        $origin = $request->headers->get('Origin');

        // Check if origin is allowed
        $allowedOrigin = null;
        if ($origin && in_array($origin, $this->allowedOrigins)) {
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
