<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\PersonalAccessToken;

class UpdateUserLastActivity
{
    public function handle(Request $request, Closure $next)
    {
        // Only update if user is already authenticated by the framework
        $user = Auth::user();

        if ($user) {
            /** @var \App\Models\User $user */
            // Increase cache window to 10 minutes to reduce DB writes
            $minutes = (int) Config::get('monitoring.user_last_activity_minutes', 10);
            $key = 'ua:last_activity:' . $user->id;

            if (Cache::add($key, 1, now()->addMinutes($minutes))) {
                // Update timestamp without firing events to be faster
                $user->timestamps = false;
                $user->last_activity = now();
                $user->saveQuietly(); // saveQuietly avoids events
                $user->timestamps = true;
            }
        }

        return $next($request);
    }
}
