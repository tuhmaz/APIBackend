<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Str;

class VisitorSession extends Model
{
    protected $fillable = [
        'session_id',
        'ip',
        'country',
        'country_code',
        'city',
        'latitude',
        'longitude',
        'device',
        'browser',
        'browser_version',
        'platform',
        'user_id',
        'user_agent',
        'url',
        'is_ajax',
        'is_desktop',
        'is_mobile',
        'is_bot',
        'last_activity'
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'is_ajax' => 'boolean',
        'is_desktop' => 'boolean',
        'is_mobile' => 'boolean',
        'is_bot' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log($request, $user = null)
    {
        // Get location data from IP
        $location = self::getIpLocation($request->ip());

        // Parse user agent (lightweight parsing without external deps)
        $ua = (string) $request->userAgent();
        [$browser, $browserVersion] = self::parseBrowserAndVersion($ua);
        $platform = self::parsePlatform($ua);
        $device = self::parseDeviceType($ua);
        $isMobile = str_contains(strtolower($ua), 'mobile');
        $isTablet = str_contains(strtolower($ua), 'tablet') || str_contains(strtolower($ua), 'ipad');
        $isDesktop = !$isMobile && !$isTablet;
        $isBot = self::isBotUa($ua);

        $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        if (!$sessionId) {
            $sessionId = $request->attributes->get('visitor_id') ?: $request->cookie('visitor_id');
        }
        if (!$sessionId) {
            $sessionId = 'vid_' . Str::uuid()->toString();
        }

        return self::updateOrCreate(
            ['session_id' => $sessionId],
            [
                'ip' => $request->ip(),
                'country' => $location['country'] ?? null,
                'country_code' => $location['country_code'] ?? null,
                'city' => $location['city'] ?? null,
                'latitude' => $location['latitude'] ?? ($location['lat'] ?? null),
                'longitude' => $location['longitude'] ?? ($location['lon'] ?? null),
                'device' => $device,
                'browser' => $browser,
                'browser_version' => $browserVersion,
                'platform' => $platform,
                'user_id' => $user ? $user->id : null,
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'is_ajax' => $request->ajax(),
                'is_desktop' => $isDesktop,
                'is_mobile' => $isMobile,
                'is_bot' => $isBot,
                'last_activity' => now()
            ]
        );
    }

    /**
     * Get location information for an IP address
     * 
     * @param string $ip The IP address to look up
     * @return array Location data
     */
    protected static function getIpLocation($ip)
    {
        // Skip private and local IPs
        if (self::isPrivateIp($ip)) {
            return [
                'country' => 'Local',
                'city' => 'Local Network',
                'isp' => 'Local Network',
                'organization' => 'Local Network',
                'as' => null,
                'mobile' => false,
                'proxy' => false,
                'hosting' => false,
            ];
        }
        
        // Use cache to avoid repeated API calls for the same IP
        $cacheKey = 'ip_location_' . md5($ip);
        $cached = cache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $locationData = [];
        
        try {
            // Try the primary service with HTTPS
            $response = Http::timeout(3) // 3 second timeout
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->get("https://ipapi.co/{$ip}/json/");
                
            if ($response->successful() && $data = $response->json()) {
                $locationData = [
                    'country' => $data['country_name'] ?? null,
                    'country_code' => $data['country'] ?? null,
                    'city' => $data['city'] ?? null,
                    'isp' => $data['org'] ?? null,
                    'organization' => $data['org'] ?? null,
                    'as' => $data['asn'] ?? null,
                    'mobile' => isset($data['mobile']) ? (bool)$data['mobile'] : null,
                    'proxy' => isset($data['proxy']) ? (bool)$data['proxy'] : null,
                    'hosting' => isset($data['hosting']) ? (bool)$data['hosting'] : null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                ];
            } else {
                // Fallback to ip-api.com if primary fails
                $response = Http::timeout(3)
                    ->get("http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,city,lat,lon,isp,org,as,mobile,proxy,hosting,query&lang=en");
                
                if ($response->successful() && $response->json('status') === 'success') {
                    $locationData = [
                        'country' => $response->json('country'),
                        'country_code' => $response->json('countryCode'),
                        'city' => $response->json('city'),
                        'latitude' => $response->json('lat'),
                        'longitude' => $response->json('lon'),
                        'isp' => $response->json('isp'),
                        'organization' => $response->json('organization'),
                        'as' => $response->json('as'),
                        'mobile' => $response->json('mobile'),
                        'proxy' => $response->json('proxy'),
                        'hosting' => $response->json('hosting'),
                    ];
                }
            }
            
            // Cache successful lookups for 24 hours, failed lookups for 1 hour
            $cacheTime = !empty($locationData) ? now()->addDay() : now()->addHour();
            cache([$cacheKey => $locationData], $cacheTime);
            
        } catch (\Exception $e) {
            Log::error('Error getting IP location for ' . $ip . ': ' . $e->getMessage());
            // Cache the error for 15 minutes to avoid hammering the API
            cache([$cacheKey => []], now()->addMinutes(15));
        }
        
        return $locationData;
    }
    
    /**
     * Check if an IP is private/local
     */
    protected static function isPrivateIp($ip)
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    public function scopeActive($query, $minutes = 5)
    {
        return $query->where('last_activity', '>=', now()->subMinutes($minutes));
    }

    public function scopeBots($query)
    {
        return $query->where('is_bot', true);
    }

    public function scopeHumans($query)
    {
        return $query->where('is_bot', false);
    }

    public function scopeGuests($query)
    {
        return $query->whereNull('user_id');
    }

    public function scopeMembers($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Check if the session is active (last activity within the last 15 minutes)
     *
     * @param int $minutes Number of minutes to consider a session active
     * @return bool
     */
    public function isActive($minutes = 5)
    {
        return $this->last_activity && $this->last_activity->gt(now()->subMinutes($minutes));
    }

    /**
     * Accessor: browser_family used by Blade to map icons
     */
    public function getBrowserFamilyAttribute(): ?string
    {
        $browser = strtolower((string)($this->browser ?? ''));
        if ($browser === '') return null;

        // Normalize common variants to families
        return match (true) {
            str_contains($browser, 'chrome') => 'chrome',
            str_contains($browser, 'firefox') => 'firefox',
            str_contains($browser, 'safari') => 'safari',
            str_contains($browser, 'edge') => 'edge',
            str_contains($browser, 'opera') || str_contains($browser, 'opr') => 'opera',
            str_contains($browser, 'msie') || str_contains($browser, 'trident') || str_contains($browser, 'ie') => 'ie',
            str_contains($browser, 'samsung') => 'samsung internet',
            str_contains($browser, 'uc ') => 'uc browser',
            str_contains($browser, 'yandex') => 'yandex',
            str_contains($browser, 'brave') => 'brave',
            str_contains($browser, 'vivaldi') => 'vivaldi',
            str_contains($browser, 'silk') => 'silk',
            str_contains($browser, 'maxthon') => 'maxthon',
            str_contains($browser, 'sogou') => 'sogou explorer',
            default => 'other',
        };
    }

    /**
     * Lightweight UA parsing helpers
     */
    protected static function parseBrowserAndVersion(string $ua): array
    {
        $uaLower = strtolower($ua);
        $candidates = [
            'edg' => 'Edge',
            'edge' => 'Edge',
            'opr' => 'Opera',
            'opera' => 'Opera',
            'chrome' => 'Chrome',
            'safari' => 'Safari',
            'firefox' => 'Firefox',
            'msie' => 'IE',
            'trident' => 'IE',
        ];
        $name = 'Other';
        $version = null;
        foreach ($candidates as $needle => $label) {
            if (str_contains($uaLower, $needle)) {
                $name = $label;
                break;
            }
        }
        // Extract version for common browsers
        $patterns = [
            '/edg\/(\d+[^\s]*)/i',
            '/edge\/(\d+[^\s]*)/i',
            '/opr\/(\d+[^\s]*)/i',
            '/opera\/(\d+[^\s]*)/i',
            '/chrome\/(\d+[^\s]*)/i',
            '/version\/(\d+[^\s]*)\s+safari/i',
            '/firefox\/(\d+[^\s]*)/i',
            '/msie\s(\d+[^;\s]*)/i',
            '/rv:(\d+[^\s]*)\)\s*like\s*gecko/i',
        ];
        foreach ($patterns as $re) {
            if (preg_match($re, $ua, $m)) {
                $version = $m[1] ?? null;
                break;
            }
        }
        return [$name, $version];
    }

    protected static function parsePlatform(string $ua): string
    {
        $uaLower = strtolower($ua);
        return match (true) {
            str_contains($uaLower, 'windows') => 'Windows',
            str_contains($uaLower, 'mac os x') || str_contains($uaLower, 'macintosh') => 'macOS',
            str_contains($uaLower, 'iphone') || str_contains($uaLower, 'ipad') || str_contains($uaLower, 'ios') => 'iOS',
            str_contains($uaLower, 'android') => 'Android',
            str_contains($uaLower, 'linux') => 'Linux',
            default => 'Other',
        };
    }

    protected static function parseDeviceType(string $ua): string
    {
        $uaLower = strtolower($ua);
        return match (true) {
            str_contains($uaLower, 'ipad') || str_contains($uaLower, 'tablet') => 'tablet',
            str_contains($uaLower, 'mobi') || str_contains($uaLower, 'iphone') || str_contains($uaLower, 'android') => 'mobile',
            default => 'desktop',
        };
    }

    protected static function isBotUa(string $ua): bool
    {
        $uaLower = strtolower($ua);
        $bots = ['bot', 'spider', 'crawler', 'curl', 'wget', 'httpclient', 'headless', 'python-requests'];
        foreach ($bots as $b) {
            if (str_contains($uaLower, $b)) return true;
        }
        return false;
    }
}
