<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StripContentEncodingHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
      $response = $next($request);
        // احذف أي Content-Encoding يرسله التطبيق
        if (method_exists($response, 'headers')) {
            $response->headers->remove('Content-Encoding');
        }
        return $response;
    }
}
