<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Cache\RateLimiting\Limit;
use Symfony\Component\HttpFoundation\Response;
use App\Models\RateLimitLog;

class ApiRateLimiter
{
    /**
     * تطبيق تقييد معدل الطلبات على نقاط النهاية API
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $limiterType  نوع المحدد (api, web, route, custom)
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $limiterType = 'api')
    {
        // التحقق مما إذا كان تقييد معدل الطلبات مفعل
        if (!Config::get('rate-limiting.enabled', true)) {
            return $next($request);
        }

        // التحقق من القائمة البيضاء (SSR/Trusted IPs) - تجاوز Rate Limiting
        if ($this->isIpTrusted($request)) {
            return $next($request);
        }

        // التحقق مما إذا كان عنوان IP محظور بشكل دائم
        if ($this->isIpBlocked($request->ip())) {
            // تسجيل محاولة الوصول من عنوان IP محظور
            if (Config::get('rate-limiting.log_throttled_requests', true)) {
                Log::warning('Blocked IP attempted access', [
                    'ip' => $request->ip(),
                    'uri' => $request->getRequestUri(),
                ]);
                
                // تسجيل في قاعدة البيانات
                try {
                    RateLimitLog::logAttempt([
                        'ip_address' => $request->ip(),
                        'user_id' => $request->user() ? $request->user()->id : null,
                        'route' => $request->route() ? ($request->route()->getName() ?? $request->path()) : $request->path(),
                        'method' => $request->method(),
                        'user_agent' => $request->userAgent(),
                        'attempts' => 999,
                        'limit' => 0,
                        'blocked_until' => now()->addYears(10), // حظر دائم تقريبًا
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to log blocked IP attempt', [
                        'error' => $e->getMessage(),
                        'ip' => $request->ip(),
                    ]);
                }
            }

            // إرجاع رسالة خطأ للعناوين المحظورة
            $message = Config::get('rate-limiting.blocked_ip_message', 'This IP address has been blocked due to suspicious activity.');
            $statusCode = Config::get('rate-limiting.response_code', 429);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'error' => 'ip_blocked',
                ], $statusCode);
            }

            return response($message, $statusCode);
        }

        // تحديد مفتاح تقييد معدل الطلبات
        $key = $this->resolveRequestSignature($request, $limiterType);

        // تحديد حد تقييد معدل الطلبات
        $limit = $this->resolveRateLimit($request, $limiterType);

        // تطبيق تقييد معدل الطلبات
        $executed = RateLimiter::attempt(
            $key,
            $limit['attempts'],
            function() {
                return true;
            },
            $limit['decay'] * 60 // تحويل الدقائق إلى ثواني
        );

        if (!$executed) {
            // تسجيل محاولة تجاوز الحد المسموح به من الطلبات
            if (Config::get('rate-limiting.log_throttled_requests', true)) {
                // تسجيل في سجل النظام
                Log::warning('Rate limit exceeded', [
                    'ip' => $request->ip(),
                    'user_id' => $request->user() ? $request->user()->id : 'guest',
                    'uri' => $request->getRequestUri(),
                    'key' => $key,
                    'limit' => $limit,
                ]);
                
                // تسجيل في قاعدة البيانات
                try {
                    $blockDuration = Config::get('rate-limiting.default_block_duration', 5);
                    
                    RateLimitLog::logAttempt([
                        'ip_address' => $request->ip(),
                        'user_id' => $request->user() ? $request->user()->id : null,
                        'route' => $request->route() ? ($request->route()->getName() ?? $request->path()) : $request->path(),
                        'method' => $request->method(),
                        'user_agent' => $request->userAgent(),
                        'attempts' => RateLimiter::attempts($key),
                        'limit' => $limit['attempts'],
                        'blocked_until' => now()->addMinutes($blockDuration),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to log rate limit attempt', [
                        'error' => $e->getMessage(),
                        'ip' => $request->ip(),
                        'uri' => $request->getRequestUri(),
                    ]);
                }
            }

            // إرجاع رسالة خطأ
            $seconds = RateLimiter::availableIn($key);
            $message = str_replace(':seconds', $seconds, Config::get('rate-limiting.error_message', 'Too many requests'));
            $statusCode = Config::get('rate-limiting.response_code', 429);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'retry_after' => $seconds,
                    'error' => 'rate_limit_exceeded',
                ], $statusCode);
            }

            return response($message, $statusCode)
                ->header('Retry-After', $seconds);
        }

        // إضافة رؤوس تقييد معدل الطلبات إلى الاستجابة
        $response = $next($request);
        
        if ($response instanceof Response) {
            $response->headers->add([
                'X-RateLimit-Limit' => $limit['attempts'],
                'X-RateLimit-Remaining' => RateLimiter::remaining($key, $limit['attempts']),
                'X-RateLimit-Reset' => RateLimiter::availableIn($key),
            ]);
        }

        return $response;
    }

    /**
     * التحقق مما إذا كان عنوان IP محظور
     *
     * @param  string  $ip
     * @return bool
     */
    protected function isIpBlocked($ip)
    {
        $blockedIps = Config::get('rate-limiting.blocked_ips', []);
        
        // التحقق من التطابق المباشر
        if (in_array($ip, $blockedIps)) {
            return true;
        }
        
        // التحقق من التطابق باستخدام النمط (*)
        foreach ($blockedIps as $blockedIp) {
            if (Str::is($blockedIp, $ip)) {
                return true;
            }
        }
        
        // التحقق من قاعدة البيانات للتأكد من عدم وجود حظر نشط
        try {
            $blockedLog = RateLimitLog::where('ip_address', $ip)
                ->where('blocked_until', '>', now())
                ->where('method', 'MANUAL') // فقط الحظر اليدوي
                ->orderBy('blocked_until', 'desc')
                ->first();
                
            return $blockedLog !== null;
        } catch (\Exception $e) {
            Log::error('Error checking IP block status', [
                'error' => $e->getMessage(),
                'ip' => $ip,
            ]);
            return false;
        }
    }

    /**
     * التحقق مما إذا كان عنوان IP موثوق (يتجاوز Rate Limiting)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isIpTrusted(Request $request): bool
    {
        $ip = $this->getClientIp($request);

        // 1. التحقق من قائمة IPs الموثوقة في الإعدادات
        $trustedIps = Config::get('rate-limiting.trusted_ips', []);
        if (in_array($ip, $trustedIps, true)) {
            return true;
        }

        // التحقق من التطابق باستخدام النمط (*)
        foreach ($trustedIps as $trustedIp) {
            if (Str::is($trustedIp, $ip)) {
                return true;
            }
        }

        // 2. التحقق من SSR_TRUSTED_IPS من البيئة
        $ssrTrustedIps = env('SSR_TRUSTED_IPS', '');
        if ($ssrTrustedIps) {
            $ssrIps = array_filter(array_map('trim', explode(',', $ssrTrustedIps)));
            if (in_array($ip, $ssrIps, true)) {
                return true;
            }
        }

        // 3. التحقق من أن الطلب من SSR مع User-Agent موثوق
        if ($this->isServerSideRequest($request)) {
            // طلبات SSR من الخادم نفسه (localhost)
            if (in_array($ip, ['127.0.0.1', '::1'])) {
                return true;
            }

            // طلبات SSR من IP موثوق عبر env
            $trustedServerIps = env('TRUSTED_SERVER_IPS', '');
            if ($trustedServerIps) {
                $serverIps = array_filter(array_map('trim', explode(',', $trustedServerIps)));
                if (in_array($ip, $serverIps, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * التحقق من طلبات Server-Side Rendering
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isServerSideRequest(Request $request): bool
    {
        $userAgent = $request->userAgent() ?? '';
        $xRequestedWith = $request->header('X-Requested-With');

        // Next.js و Node.js server-side request identifiers
        $serverAgents = ['node-fetch', 'undici', 'node', 'Next.js'];

        foreach ($serverAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return true;
            }
        }

        // طلبات بدون Origin header مع X-Requested-With (SSR pattern)
        if (!$request->header('Origin') && $xRequestedWith === 'XMLHttpRequest') {
            return true;
        }

        return false;
    }

    /**
     * الحصول على IP العميل الحقيقي (يدعم الـ proxies)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getClientIp(Request $request): string
    {
        // Cloudflare
        $cf = $request->header('CF-Connecting-IP');
        if (is_string($cf) && trim($cf) !== '') {
            return trim($cf);
        }

        // X-Forwarded-For
        $xff = $request->header('X-Forwarded-For');
        if (is_string($xff) && trim($xff) !== '') {
            $first = trim(explode(',', $xff)[0] ?? '');
            if ($first !== '') {
                return $first;
            }
        }

        // X-Real-IP
        $xri = $request->header('X-Real-IP');
        if (is_string($xri) && trim($xri) !== '') {
            return trim($xri);
        }

        return $request->ip() ?? '0.0.0.0';
    }

    /**
     * تحديد مفتاح تقييد معدل الطلبات
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $limiterType
     * @return string
     */
    protected function resolveRequestSignature(Request $request, $limiterType)
    {
        // إنشاء مفتاح فريد بناءً على نوع المحدد
        $signature = match($limiterType) {
            'api' => 'api|' . $request->ip(),
            'web' => 'web|' . $request->ip(),
            'route' => 'route|' . ($request->route() ? $request->route()->getName() : 'unknown') . '|' . $request->ip(),
            'user' => 'user|' . ($request->user() ? $request->user()->id : 'guest') . '|' . $request->ip(),
            default => $limiterType . '|' . $request->ip(),
        };

        return 'rate_limit:' . sha1($signature);
    }

    /**
     * تحديد حد تقييد معدل الطلبات
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $limiterType
     * @return array
     */
    protected function resolveRateLimit(Request $request, $limiterType)
    {
        // الإعدادات الافتراضية
        $defaultLimit = [
            'attempts' => 60,
            'decay' => 1,
        ];

        // تحديد الحد بناءً على نوع المحدد
        $limitConfig = match($limiterType) {
            'api' => Config::get('rate-limiting.global.api', '60,1'),
            'web' => Config::get('rate-limiting.global.web', '120,1'),
            'route' => $this->getRouteLimit($request),
            'user' => $this->getUserLimit($request),
            default => '60,1',
        };

        // تحويل الإعدادات إلى مصفوفة
        if (is_string($limitConfig)) {
            $parts = explode(',', $limitConfig);
            if (count($parts) === 2) {
                return [
                    'attempts' => (int) $parts[0],
                    'decay' => (int) $parts[1],
                ];
            }
        }

        return $defaultLimit;
    }

    /**
     * تحديد حد تقييد معدل الطلبات بناءً على المسار
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getRouteLimit(Request $request)
    {
        // الحصول على اسم المسار
        $routeName = $request->route() ? $request->route()->getName() : null;
        
        if (!$routeName) {
            return Config::get('rate-limiting.global.api', '60,1');
        }

        // البحث عن حد محدد للمسار
        $routes = Config::get('rate-limiting.routes', []);
        
        // البحث عن تطابق مباشر
        if (isset($routes[$routeName])) {
            return $routes[$routeName];
        }
        
        // البحث عن تطابق باستخدام النمط (*)
        foreach ($routes as $pattern => $limit) {
            if (Str::is($pattern, $routeName)) {
                return $limit;
            }
        }

        // استخدام الحد العام
        return Config::get('rate-limiting.global.api', '60,1');
    }

    /**
     * تحديد حد تقييد معدل الطلبات بناءً على المستخدم
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function getUserLimit(Request $request)
    {
        // تحديد نوع المستخدم
        $userType = 'guest';
        
        if ($request->user()) {
            $userType = $request->user()->hasRole('admin') ? 'admin' : 'default';
        }

        // الحصول على الحد المناسب
        $users = Config::get('rate-limiting.users', []);
        
        return $users[$userType] ?? Config::get('rate-limiting.global.api', '60,1');
    }
}
