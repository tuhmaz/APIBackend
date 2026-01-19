<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\VisitorTracking;
use App\Models\VisitorSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

class VisitorTrackingMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Fail fast if disabled
        if (!Config::get('monitoring.visitor_tracking_enabled', true)) {
            return $response;
        }

        // Only track GET requests to reduce noise and load
        if (!$request->isMethod('GET')) {
            return $response;
        }

        // Use terminating callback to ensure response is sent to user first
        // detailed logic is inside track()
        app()->terminating(function () use ($request, $response) {
            $this->track($request, $response);
        });

        return $response;
    }

    protected function track(Request $request, $response)
    {
        try {
            $ip = $request->ip() ?? '127.0.0.1';
            
            // 1. Debounce Check (Fast)
            // If we recently tracked this IP, skip heavy DB write
            $debounceSeconds = (int) Config::get('monitoring.visitor_write_debounce_seconds', 60);
            $debounceKey = "vt:debounce:" . $ip;
            
            if (!Cache::add($debounceKey, 1, $debounceSeconds)) {
                return;
            }

            // 2. Prepare Data
            $userAgent = $request->header('User-Agent', 'Unknown');
            $url = $request->fullUrl();
            $referer = $request->header('Referer');
            $statusCode = $response->getStatusCode();

            // 3. User Info (if available)
            $user = $request->user(); 
            $userId = $user ? $user->id : null;

            // 4. Lightweight Parsing (No external API calls to prevent timeouts)
            // We skip getGeoDataFromIP to prevent 1m+ delays caused by external API timeouts.
            // We use simple string matching for Browser/OS to avoid heavy regex libraries.
            
            $browser = 'Unknown';
            $os = 'Unknown';
            
            $ua = $userAgent;
            if (strpos($ua, 'Chrome') !== false) $browser = 'Chrome';
            elseif (strpos($ua, 'Firefox') !== false) $browser = 'Firefox';
            elseif (strpos($ua, 'Safari') !== false) $browser = 'Safari';
            elseif (strpos($ua, 'Edge') !== false) $browser = 'Edge';
            elseif (strpos($ua, 'Opera') !== false) $browser = 'Opera';

            if (strpos($ua, 'Windows') !== false) $os = 'Windows';
            elseif (strpos($ua, 'Mac') !== false) $os = 'macOS';
            elseif (strpos($ua, 'Linux') !== false) $os = 'Linux';
            elseif (strpos($ua, 'Android') !== false) $os = 'Android';
            elseif (strpos($ua, 'iPhone') !== false || strpos($ua, 'iPad') !== false) $os = 'iOS';
            elseif (strpos($ua, 'bot') !== false || strpos($ua, 'crawl') !== false) $os = 'Bot';

            // 5. DB Write (Safe Update)
            VisitorTracking::updateOrCreate(
                [
                    'ip_address' => $ip,
                    'user_id'    => $userId,
                ],
                [
                    'user_agent'    => substr($userAgent, 0, 255),
                    'url'           => substr($url, 0, 255),
                    'referer'       => $referer ? substr($referer, 0, 255) : null,
                    'browser'       => $browser,
                    'os'            => $os,
                    'status_code'   => $statusCode,
                    'last_activity' => now(),
                    // Geo fields are intentionally left untouched or null to avoid blocking
                    // 'country' => null, 
                    // 'city' => null,
                ]
            );

            // 6. Optional: Visitor Session Log (Debounced)
            // Only if strictly needed, otherwise skip to save another DB write
            // $this->logSession($request, $user, $ip);

        } catch (\Throwable $e) {
            // Silently fail to never impact user
            Log::error('VisitorTracking Error: ' . $e->getMessage());
        }
    }

    protected function logSession($request, $user, $ip)
    {
        try {
            $sessionId = $request->hasSession() ? $request->session()->getId() : null;
            $vsKey = 'vs:log:' . ($sessionId ?: $ip);
            $vsDebounce = (int) Config::get('monitoring.visitor_session_log_debounce', 30);
            
            if (Cache::add($vsKey, 1, $vsDebounce)) {
                VisitorSession::log($request, $user);
            }
        } catch (\Throwable $e) {
            // Ignore
        }
    }
}
