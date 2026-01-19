<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CachePublicResponse
{
    public function handle(Request $request, Closure $next)
    {
        /** @var SymfonyResponse $response */
        $response = $next($request);

        if (!$request->isMethodSafe()) {
            return $response;
        }

        if ($request->user()) {
            return $response;
        }

        if (!$response instanceof SymfonyResponse && !$response instanceof BinaryFileResponse) {
            return $response;
        }

        if (!$response->isSuccessful()) {
            return $response;
        }

        if ($response->headers->has('Cache-Control')) {
            return $response;
        }

        $maxAge = (int) config('http-cache.front.max_age', 900);
        $staleWhileRevalidate = (int) config('http-cache.front.stale_while_revalidate', 60);
        $staleIfError = (int) config('http-cache.front.stale_if_error', 300);

        $cacheHeader = sprintf(
            'public, max-age=%d, s-maxage=%d, stale-while-revalidate=%d, stale-if-error=%d',
            $maxAge,
            $maxAge,
            $staleWhileRevalidate,
            $staleIfError
        );

        $response->headers->set('Cache-Control', $cacheHeader);

        $existingVary = $response->headers->get('Vary');
        $varyHeader = $existingVary ? $existingVary . ', Accept-Encoding' : 'Accept-Encoding';
        $response->headers->set('Vary', $varyHeader);

        if (!$response->getEtag()) {
            $response->setEtag(md5($response->getContent()));
        }

        $response->isNotModified($request);

        return $response;
    }
}
