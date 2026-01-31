<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\VisitorTracking;
use App\Models\VisitorSession;
use App\Models\PageVisit;
use App\Services\VisitorService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VisitorTrackingMiddleware
{
    protected $visitorService;

    public function __construct(VisitorService $visitorService)
    {
        $this->visitorService = $visitorService;
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Ensure a stable visitor ID cookie for anonymous traffic (not tied to IP)
        $visitorId = $request->cookie('visitor_id');
        if (!$visitorId && !$request->hasSession()) {
            $visitorId = 'vid_' . Str::uuid()->toString();
            $minutes = (int) Config::get('session.lifetime', 30);
            $cookie = cookie(
                'visitor_id',
                $visitorId,
                $minutes,
                '/',
                Config::get('session.domain'),
                (bool) Config::get('session.secure'),
                true,
                false,
                Config::get('session.same_site', 'lax')
            );
            $response->headers->setCookie($cookie);
        }

        if ($visitorId) {
            $request->attributes->set('visitor_id', $visitorId);
        }

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

            // 4. Lightweight Parsing (Case-insensitive detection)
            $browser = 'Unknown';
            $os = 'Unknown';

            $ua = strtolower($userAgent);

            // Browser detection (order matters - check specific before generic)
            if (strpos($ua, 'edg/') !== false || strpos($ua, 'edge/') !== false) {
                $browser = 'Edge';
            } elseif (strpos($ua, 'opr/') !== false || strpos($ua, 'opera') !== false) {
                $browser = 'Opera';
            } elseif (strpos($ua, 'chrome') !== false || strpos($ua, 'crios') !== false) {
                $browser = 'Chrome';
            } elseif (strpos($ua, 'firefox') !== false || strpos($ua, 'fxios') !== false) {
                $browser = 'Firefox';
            } elseif (strpos($ua, 'safari') !== false) {
                $browser = 'Safari';
            } elseif (strpos($ua, 'msie') !== false || strpos($ua, 'trident') !== false) {
                $browser = 'IE';
            } elseif (strpos($ua, 'curl') !== false) {
                $browser = 'cURL';
            } elseif (strpos($ua, 'postman') !== false) {
                $browser = 'Postman';
            } elseif (strpos($ua, 'insomnia') !== false) {
                $browser = 'Insomnia';
            } elseif (strpos($ua, 'axios') !== false || strpos($ua, 'node') !== false || strpos($ua, 'fetch') !== false) {
                $browser = 'API Client';
            } elseif (strpos($ua, 'okhttp') !== false) {
                $browser = 'Android App';
            } elseif (strpos($ua, 'cfnetwork') !== false || strpos($ua, 'darwin') !== false) {
                $browser = 'iOS App';
            }

            // OS detection
            if (strpos($ua, 'windows nt 10') !== false || strpos($ua, 'windows nt 11') !== false) {
                $os = 'Windows 10/11';
            } elseif (strpos($ua, 'windows') !== false) {
                $os = 'Windows';
            } elseif (strpos($ua, 'iphone') !== false) {
                $os = 'iOS (iPhone)';
            } elseif (strpos($ua, 'ipad') !== false) {
                $os = 'iOS (iPad)';
            } elseif (strpos($ua, 'android') !== false) {
                $os = 'Android';
            } elseif (strpos($ua, 'mac os x') !== false || strpos($ua, 'macintosh') !== false) {
                $os = 'macOS';
            } elseif (strpos($ua, 'linux') !== false) {
                $os = 'Linux';
            } elseif (strpos($ua, 'cros') !== false) {
                $os = 'Chrome OS';
            }

            // Bot detection
            $botPatterns = ['bot', 'crawl', 'spider', 'slurp', 'googlebot', 'bingbot', 'yandex',
                           'baidu', 'duckduck', 'facebookexternalhit', 'twitterbot', 'linkedinbot',
                           'whatsapp', 'telegram', 'scraper', 'curl', 'wget', 'python', 'java/',
                           'go-http', 'ruby', 'perl', 'php/', 'apache', 'nginx'];
            foreach ($botPatterns as $pattern) {
                if (strpos($ua, $pattern) !== false) {
                    $os = 'Bot/Crawler';
                    if ($browser === 'Unknown') {
                        $browser = 'Bot';
                    }
                    break;
                }
            }

            // If still unknown, try to provide more info
            if ($browser === 'Unknown' && $os === 'Unknown') {
                // Check if it's an API request without standard browser UA
                if (empty($userAgent) || strlen($userAgent) < 10) {
                    $browser = 'API/Script';
                    $os = 'Server';
                }
            }

            // 5. Get geo data (cached, non-blocking)
            $geoData = $this->visitorService->getGeoDataFromIP($ip);
            $country = $geoData['country'] ?? null;
            $city = $geoData['city'] ?? null;

            // 6. DB Write (Safe Update)
            $visitor = VisitorTracking::updateOrCreate(
                [
                    'ip_address' => $ip,
                ],
                [
                    'user_agent'    => substr($userAgent, 0, 255),
                    'url'           => substr($url, 0, 255),
                    'referer'       => $referer ? substr($referer, 0, 255) : null,
                    'browser'       => $browser,
                    'os'            => $os,
                    'country'       => $country,
                    'city'          => $city,
                    'status_code'   => $statusCode,
                    'user_id'       => $userId,
                    'last_activity' => now(),
                ]
            );

            // 7. Track Page Visit (for pageViews analytics)
            PageVisit::create([
                'visitor_id' => $visitor->id,
                'page_url'   => substr($url, 0, 2048),
            ]);

            // 8. Optional: Visitor Session Log (Debounced)
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
