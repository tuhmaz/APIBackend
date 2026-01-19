<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class LogLastActivity
{
    /**
     * Update last_seen every authenticated request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $minutes = (int) Config::get('monitoring.user_last_activity_minutes', 5);
            $key = 'ua:last_seen:' . $user->id;
            if (Cache::add($key, 1, now()->addMinutes($minutes))) {
                $user->last_seen = now();
                $user->save();
            }
        }
        return $next($request);
    }
}
