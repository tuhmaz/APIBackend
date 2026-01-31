<?php

namespace App\Services;

use App\Models\VisitorTracking;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

/**
 * Class VisitorService
 *
 * خدمة مسؤولة عن إحصائيات الزوار وعمليات تحليل الـUser-Agent والموقع الجغرافي.
 */
class VisitorService
{
    /**
     * Optional GeoIP reader instance when available.
     * @var object|null
     */
    protected $geoReader = null;

    /**
     * VisitorService constructor.
     * يقوم بتحميل قاعدة بيانات GeoLite2-City من مجلد التخزين.
     */
    public function __construct()
    {
        // Attempt to initialize MaxMind GeoIP reader only if available
        try {
            $readerClass = 'GeoIp2\\Database\\Reader';
            $dbPath = storage_path('geoip/GeoLite2-City.mmdb');
            if (class_exists($readerClass) && file_exists($dbPath)) {
                $this->geoReader = new $readerClass($dbPath);
            } else {
                $this->geoReader = null; // Fallback: will use ipwho.is in getGeoDataFromIP()
            }
        } catch (\Throwable $e) {
            // Do not block app boot; just log and continue with HTTP geolocation fallback
            Log::warning('GeoIP Reader initialization skipped: ' . $e->getMessage());
            $this->geoReader = null;
        }
    }

    /**
     * إحصائيات الزوار (الحاليين، اليوم، نسبة التغير، والسجل خلال آخر 24 ساعة).
     *
     * @return array
     */
    public function getVisitorStats()
    {
        try {
            // عدد الزوار الحاليين (آخر 5 دقائق) - تحديث كل 30 ثانية للدقة
            $currentVisitors = Cache::remember('current_visitors', 30, function () {
                return VisitorTracking::where('last_activity', '>=', now()->subMinutes(5))->count();
            });

            // عدد زيارات اليوم (نشاط اليوم وليس فقط الإنشاء)
            $totalToday = Cache::remember('total_today_visitors', 300, function () {
                return VisitorTracking::whereDate('last_activity', today())->count();
            });

            // الزوار في آخر ساعة
            $lastHour = VisitorTracking::where('created_at', '>=', now()->subHour())->count();
            // الزوار في الساعة السابقة
            $previousHour = VisitorTracking::where('created_at', '>=', now()->subHours(2))
                ->where('created_at', '<', now()->subHour())
                ->count();

            // نسبة التغيير
            $change = $previousHour > 0
                ? round((($lastHour - $previousHour) / $previousHour) * 100)
                : 0;

            // سجل الزيارات خلال آخر 24 ساعة، مجمّعة بالساعة
            $history = Cache::remember('visitor_history', 3600, function () {
                return DB::connection('jo')->table('visitors_tracking')
                    ->select(
                        DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as timestamp'),
                        DB::raw('COUNT(*) as count')
                    )
                    ->where('created_at', '>=', now()->subDay())
                    ->groupBy('timestamp')
                    ->orderBy('timestamp')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'timestamp' => $item->timestamp,
                            'count'     => (int) $item->count,
                        ];
                    })
                    ->toArray();
            });

            return [
                'current'     => $currentVisitors,
                'total_today' => $totalToday,
                'change'      => $change,
                'history'     => $history,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting visitor stats: ' . $e->getMessage());
            return [
                'current'     => 0,
                'total_today' => 0,
                'change'      => 0,
                'history'     => [],
            ];
        }
    }

    /**
     * يعيد بيانات المواقع الجغرافية للزوار خلال آخر 24 ساعة.
     *
     * @return array
     */
    public function getVisitorLocations()
    {
        try {
            // جلب جميع عناوين IP من آخر 24 ساعة مع عدد الزيارات لكل IP
            $visitors = DB::connection('jo')->table('visitors_tracking')
                ->select('ip_address', DB::raw('COUNT(*) as count'))
                ->whereNotNull('ip_address')
                ->where('created_at', '>=', now()->subDay())
                ->groupBy('ip_address')
                ->get();

            // تحويل كل IP إلى بيانات جغرافية
            $locations = $visitors->map(function ($visitor) {
                $geoData = $this->getGeoDataFromIP($visitor->ip_address);

                return [
                    'country' => $geoData['country'] ?? 'Unknown',
                    'city'    => $geoData['city']    ?? 'Unknown',
                    'lat'     => $geoData['lat']     ?? null,
                    'lng'     => $geoData['lon']     ?? null,
                    'count'   => (int) $visitor->count,
                ];
            });

            // تجميع النتائج حسب (الدولة-المدينة)
            $groupedLocations = $locations->groupBy(function ($item) {
                return $item['country'] . '-' . $item['city'];
            })->map(function ($group) {
                return [
                    'country' => $group->first()['country'],
                    'city'    => $group->first()['city'],
                    'lat'     => $group->first()['lat'],
                    'lng'     => $group->first()['lng'],
                    'count'   => $group->sum('count'),
                ];
            })->values();

            if ($groupedLocations->isEmpty()) {
                Log::warning('No visitor locations found via IP in the last 24 hours.');
            } else {
                Log::info('Visitor Locations Data via MaxMind:', $groupedLocations->toArray());
            }

            return $groupedLocations->toArray();
        } catch (\Exception $e) {
            Log::error('Error resolving IP locations: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * يحلّل الـUser-Agent ويعيد معلومات مفصلة باستخدام DeviceDetector.
     *
     * @param  string  $userAgent
     * @return array
     */
    public function analyzeUserAgent($userAgent)
    {
        $ddClass = 'DeviceDetector\\DeviceDetector';

        if (class_exists($ddClass)) {
            $dd = new $ddClass($userAgent);
            $dd->parse();

            if ($dd->isBot()) {
                return [
                    'isBot'   => true,
                    'botInfo' => $dd->getBot(),
                    'client'  => null,
                    'os'      => null,
                    'device'  => null,
                    'brand'   => null,
                    'model'   => null,
                ];
            }

            return [
                'isBot'   => false,
                'botInfo' => null,
                'client'  => $dd->getClient(),
                'os'      => $dd->getOs(),
                'device'  => $dd->getDeviceName(),
                'brand'   => $dd->getBrandName(),
                'model'   => $dd->getModel(),
            ];
        }

        // Fallback simple UA parsing when DeviceDetector is not installed
        $ua = strtolower($userAgent ?? '');
        $isBot = preg_match('/bot|crawl|spider|slurp|curl|wget|httpclient/i', $ua) === 1;
        $client = [
            'type' => 'browser',
            'name' => (strpos($ua, 'chrome') !== false ? 'Chrome' : (strpos($ua, 'firefox') !== false ? 'Firefox' : (strpos($ua, 'safari') !== false ? 'Safari' : 'Unknown'))),
            'version' => null,
        ];
        $os = [
            'name' => (strpos($ua, 'windows') !== false ? 'Windows' : (strpos($ua, 'mac os') !== false ? 'macOS' : (strpos($ua, 'linux') !== false ? 'Linux' : 'Unknown'))),
            'version' => null,
        ];
        $device = (strpos($ua, 'mobile') !== false || strpos($ua, 'iphone') !== false || strpos($ua, 'android') !== false) ? 'smartphone' : 'desktop';

        return [
            'isBot'   => $isBot,
            'botInfo' => null,
            'client'  => $client,
            'os'      => $os,
            'device'  => $device,
            'brand'   => null,
            'model'   => null,
        ];
    }

    /**
     * يعيد قائمة الزوار النشطين خلال آخر X دقائق.
     *
     * @param  int  $minutes
     * @return array
     */
    public function getActiveVisitors($minutes = 5)
    {
        try {
            $activeVisitors = VisitorTracking::where('last_activity', '>=', now()->subMinutes($minutes))
                ->orderBy('last_activity', 'desc')
                ->get();

            return $activeVisitors->map(function ($visitor) {
                return [
                    'ip'          => $visitor->ip_address ?? 'Unknown',
                    'country'     => $visitor->country ?? 'Unknown',
                    'city'        => $visitor->city ?? 'Unknown',
                    'browser'     => $visitor->browser ?? 'Unknown',
                    'os'          => $visitor->os ?? 'Unknown',
                    'last_active' => $visitor->last_activity,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting active visitors: ' . $e->getMessage());
            return [];
        }
    }
/**
 * جلب بيانات الموقع الجغرافي من عنوان IP مع مزودين احتياطيين ودارات حماية (circuit breakers).
 *
 * @param  string  $ip
 * @return array
 */
public function getGeoDataFromIP($ip)
{
    try {
        // تجاهل IP المحلي أو فارغ
        if ($ip === '127.0.0.1' || !$ip) {
            return [
                'country' => 'Localhost',
                'city'    => 'Local',
                'lat'     => null,
                'lon'     => null,
            ];
        }

        // التحقق من التخزين المؤقت للنجاح أو الفشل لتقليل الاستدعاءات وتكرار التحذيرات
        if ($cached = Cache::get('geo_ip_' . $ip)) {
            return $cached;
        }
        if ($failed = Cache::get('geo_ip_fail_' . $ip)) {
            // إرجاع آخر نتيجة فشل مخزنة دون إعادة المحاولة كثيراً
            return $failed;
        }

        // إن وُجد MaxMind geoReader استخدمه أولاً
        if ($this->geoReader) {
            try {
                $record = $this->geoReader->city($ip);
                $data = [
                    'country' => $record->country->name ?? 'Unknown',
                    'city'    => $record->city->name ?? 'Unknown',
                    'lat'     => $record->location->latitude ?? null,
                    'lon'     => $record->location->longitude ?? null,
                ];
                Cache::put('geo_ip_' . $ip, $data, config('geoip.cache_ttl_success', 86400));
                return $data;
            } catch (\Throwable $e) {
                // تجاهل وسنلجأ لـ HTTP fallback
            }
        }

        // إذا كانت استدعاءات HTTP معطلة، أعد Unknown بدون تسجيل تحذير مزعج
        if (!config('geoip.http_enabled', true)) {
            $unknown = ['country' => 'Unknown', 'city' => 'Unknown', 'lat' => null, 'lon' => null];
            Cache::put('geo_ip_fail_' . $ip, $unknown, config('geoip.cache_ttl_failure', 21600));
            return $unknown;
        }

        // قائمة المزودين بالترتيب من الإعدادات
        $providers = config('geoip.providers', ['ipwhois', 'ipapi', 'ipapicom']);

        foreach ($providers as $provider) {
            // تحقق دارة المزود (circuit breaker)
            if ($this->isProviderOpen($provider) === false) {
                continue; // المزود في حالة تعطيل مؤقت
            }

            $result = null;
            try {
                switch ($provider) {
                    case 'ipwhois':
                        $result = $this->fetchFromIpwhois($ip);
                        break;
                    case 'ipapi':
                        $result = $this->fetchFromIpapi($ip);
                        break;
                    case 'ipapicom':
                        $result = $this->fetchFromIpApiCom($ip);
                        break;
                }
            } catch (\Throwable $e) {
                $this->recordProviderFailure($provider);
                $result = null;
            }

            if ($result) {
                Cache::put('geo_ip_' . $ip, $result, config('geoip.cache_ttl_success', 86400));
                // عند النجاح، أعد ضبط فشل المزود لتحسين الدارة
                $this->resetProviderFailures($provider);
                return $result;
            }
            // فشل المزود الحالي — سجّل فشلًا
            $this->recordProviderFailure($provider);
        }

        // جميع المزودين فشلوا — خزن فشل لتقليل الضوضاء
        $unknown = ['country' => 'Unknown', 'city' => 'Unknown', 'lat' => null, 'lon' => null];
        Cache::put('geo_ip_fail_' . $ip, $unknown, config('geoip.cache_ttl_failure', 21600));

        if (config('geoip.log_on_failure', true)) {
            $warnKey = 'geo_ip_warned_' . $ip;
            if (!Cache::has($warnKey)) {
                Log::warning("GeoIP providers failed for IP $ip");
                Cache::put($warnKey, 1, 3600); // لا تكرر التحذير لنفس الـ IP لمدة ساعة
            }
        }

        return $unknown;

    } catch (\Exception $e) {
        Log::error("Error fetching geolocation for IP $ip: " . $e->getMessage());
        return [
            'country' => 'Unknown',
            'city'    => 'Unknown',
            'lat'     => null,
            'lon'     => null,
        ];
    }
}

/**
 * ipwho.is provider
 */
protected function fetchFromIpwhois(string $ip): ?array
{
    $timeout = (int) config('geoip.http_timeout', 3);
    /** @var \Illuminate\Http\Client\Response $response */
    $response = Http::timeout($timeout)->retry(2, 200)->get('https://ipwho.is/' . $ip);
    if (!$response->ok()) {
        return null;
    }
    $geoData = $response->json();
    if (($geoData['success'] ?? false) !== true) {
        return null;
    }
    return [
        'country' => $geoData['country'] ?? 'Unknown',
        'city'    => $geoData['city'] ?? 'Unknown',
        'lat'     => $geoData['latitude'] ?? null,
        'lon'     => $geoData['longitude'] ?? null,
    ];
}

/**
 * ipapi.co provider (no key required for basic usage; respect rate limits)
 */
protected function fetchFromIpapi(string $ip): ?array
{
    $timeout = (int) config('geoip.http_timeout', 3);
    /** @var \Illuminate\Http\Client\Response $response */
    $response = Http::timeout($timeout)->retry(2, 200)->get('https://ipapi.co/' . $ip . '/json/');
    if (!$response->ok()) {
        return null;
    }
    $d = $response->json();
    if (!$d || isset($d['error'])) {
        return null;
    }
    return [
        'country' => $d['country_name'] ?? 'Unknown',
        'city'    => $d['city'] ?? 'Unknown',
        'lat'     => $d['latitude'] ?? $d['lat'] ?? null,
        'lon'     => $d['longitude'] ?? $d['lon'] ?? null,
    ];
}

/**
 * ip-api.com provider (free JSON endpoint; note: has usage limits)
 */
protected function fetchFromIpApiCom(string $ip): ?array
{
    $timeout = (int) config('geoip.http_timeout', 3);
    /** @var \Illuminate\Http\Client\Response $response */
    $response = Http::timeout($timeout)->retry(2, 200)->get('http://ip-api.com/json/' . $ip, [
        'fields' => 'status,country,city,lat,lon',
    ]);
    if (!$response->ok()) {
        return null;
    }
    $d = $response->json();
    if (!($d && ($d['status'] ?? '') === 'success')) {
        return null;
    }
    return [
        'country' => $d['country'] ?? 'Unknown',
        'city'    => $d['city'] ?? 'Unknown',
        'lat'     => $d['lat'] ?? null,
        'lon'     => $d['lon'] ?? null,
    ];
}

/**
 * Circuit breaker helpers per provider
 */
protected function isProviderOpen(string $provider): bool
{
    // إذا تم وضع المزود في حالة تعطيل مؤقت (open=false)، لا نحاول استدعاءه
    $open = Cache::get("geoip_provider_open_{$provider}");
    if ($open === null) {
        // افتراضيًا مفتوح
        return true;
    }
    return (bool) $open;
}

protected function recordProviderFailure(string $provider): void
{
    $key = "geoip_provider_failures_{$provider}";
    $failures = (int) Cache::get($key, 0) + 1;
    Cache::put($key, $failures, 600); // احتفظ بعدّاد الفشل لمدة 10 دقائق

    $threshold = (int) config('geoip.provider_failure_threshold', 5);
    if ($failures >= $threshold) {
        // عطل المزود مدة قصيرة لتخفيف الضغط
        Cache::put("geoip_provider_open_{$provider}", false, 600); // 10 دقائق
        Log::notice("GeoIP provider '{$provider}' temporarily disabled due to repeated failures.");
    }
}

protected function resetProviderFailures(string $provider): void
{
    Cache::forget("geoip_provider_failures_{$provider}");
    Cache::put("geoip_provider_open_{$provider}", true, 600);
}

}
