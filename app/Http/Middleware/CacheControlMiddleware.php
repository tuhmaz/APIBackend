<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CacheControlMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // تطبيق التحكم في التخزين المؤقت على جميع المسارات الديناميكية
        if ($request->is('dashboard/*') || $request->is('api/*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        return $response;
    }
}
