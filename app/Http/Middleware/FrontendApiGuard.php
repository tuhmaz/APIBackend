<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Frontend API Guard
 * حماية API من الوصول المباشر - السماح فقط للفرونت إند
 */
class FrontendApiGuard
{
    /**
     * النطاقات المسموح بها للـ Origin
     */
    protected array $allowedOrigins = [
        'https://alemancenter.com',
        'https://www.alemancenter.com',
        'http://localhost:3000',
        'http://localhost:3001',
    ];

    /**
     * النطاقات المسموح بها للـ Referer
     */
    protected array $allowedReferers = [
        'alemancenter.com',
        'www.alemancenter.com',
        'localhost:3000',
        'localhost:3001',
    ];

    /**
     * المسارات المستثناة من الحماية (مثل webhooks)
     */
    protected array $excludedPaths = [
        'api/auth/google/callback',
        'api/auth/email/verify',
        'api/ping',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // السماح بـ preflight requests
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        // استثناء المسارات المحددة
        if ($this->isExcludedPath($request)) {
            return $next($request);
        }

        // التحقق من المصدر
        if (!$this->isValidSource($request)) {
            $this->logUnauthorizedAccess($request);

            return response()->json([
                'status' => false,
                'message' => 'غير مصرح بالوصول',
                'code' => 'UNAUTHORIZED_ACCESS'
            ], 403);
        }

        // التحقق من معدل الطلبات
        if ($this->isRateLimited($request)) {
            return response()->json([
                'status' => false,
                'message' => 'تم تجاوز الحد المسموح للطلبات',
                'code' => 'RATE_LIMIT_EXCEEDED'
            ], 429);
        }

        return $next($request);
    }

    /**
     * التحقق من صحة مصدر الطلب
     * الطلب يجب أن يأتي من مصدر موثوق (Frontend) وليس من المتصفح مباشرة
     */
    protected function isValidSource(Request $request): bool
    {
        // 1. التحقق من الـ API Key الخاص بالفرونت إند (الأكثر أماناً)
        // هذا يعمل لكل من طلبات المتصفح و SSR
        $apiKey = $request->header('X-Frontend-Key');
        if ($apiKey && $this->isValidFrontendKey($apiKey)) {
            return true;
        }

        // 2. التحقق من Origin header (للطلبات من Frontend browser)
        $origin = $request->header('Origin');
        if ($origin && $this->isAllowedOrigin($origin)) {
            return true;
        }

        // 3. التحقق من Referer header مع X-Requested-With
        $referer = $request->header('Referer');
        $xRequestedWith = $request->header('X-Requested-With');
        if ($referer && $this->isAllowedReferer($referer) && $xRequestedWith === 'XMLHttpRequest') {
            return true;
        }

        // 4. السماح للطلبات المصادق عليها (auth:sanctum)
        if ($request->bearerToken() && $request->user()) {
            return true;
        }

        // 5. في بيئة التطوير المحلية فقط، السماح بمرونة أكبر
        if (app()->environment('local')) {
            $ip = $request->ip();
            if (in_array($ip, ['127.0.0.1', '::1']) && $xRequestedWith === 'XMLHttpRequest') {
                return true;
            }
        }

        // 6. الطلبات من Server-Side (Next.js SSR)
        // SSR requests don't have Origin header, check User-Agent and X-Requested-With
        if ($this->isServerSideRequest($request)) {
            return true;
        }

        return false;
    }

    /**
     * التحقق من طلبات Server-Side Rendering
     * SSR requests from Next.js come without Origin header but with specific User-Agent
     */
    protected function isServerSideRequest(Request $request): bool
    {
        $userAgent = $request->userAgent() ?? '';
        $xRequestedWith = $request->header('X-Requested-With');

        // Next.js و Node.js server-side requests identifiers
        $serverAgents = ['node-fetch', 'undici', 'node', 'Next.js'];
        $isServerAgent = false;

        foreach ($serverAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                $isServerAgent = true;
                break;
            }
        }

        // إذا كان الطلب من Node.js/Next.js ولديه X-Requested-With header
        if ($isServerAgent && $xRequestedWith === 'XMLHttpRequest') {
            return true;
        }

        // طلبات SSR بدون Origin header ولكن مع X-Requested-With من نفس الخادم
        // هذا يسمح لطلبات Next.js SSR التي لا تحتوي على User-Agent محدد
        if (!$request->header('Origin') && $xRequestedWith === 'XMLHttpRequest') {
            // تحقق إضافي: يجب أن يكون من IP موثوق (الخادم نفسه أو Vercel/Cloudflare)
            $ip = $request->ip();
            $trustedIps = $this->getTrustedServerIps();

            if (in_array($ip, $trustedIps)) {
                return true;
            }
        }

        return false;
    }

    /**
     * الحصول على قائمة IPs الموثوقة للخوادم
     */
    protected function getTrustedServerIps(): array
    {
        $ips = ['127.0.0.1', '::1'];

        // إضافة IPs من البيئة إذا كانت موجودة
        $envIps = env('TRUSTED_SERVER_IPS', '');
        if ($envIps) {
            $additional = array_filter(array_map('trim', explode(',', $envIps)));
            $ips = array_merge($ips, $additional);
        }

        return $ips;
    }

    /**
     * التحقق من Origin المسموح
     */
    protected function isAllowedOrigin(string $origin): bool
    {
        // إضافة النطاقات من البيئة
        $envOrigins = env('ALLOWED_ORIGINS', '');
        if ($envOrigins) {
            $additional = array_filter(array_map('trim', explode(',', $envOrigins)));
            $this->allowedOrigins = array_merge($this->allowedOrigins, $additional);
        }

        return in_array($origin, $this->allowedOrigins, true);
    }

    /**
     * التحقق من Referer المسموح
     */
    protected function isAllowedReferer(string $referer): bool
    {
        $parsedUrl = parse_url($referer);
        $host = $parsedUrl['host'] ?? '';
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $fullHost = $host . $port;

        foreach ($this->allowedReferers as $allowed) {
            if ($fullHost === $allowed || str_ends_with($fullHost, '.' . $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * التحقق من Host المسموح
     */
    protected function isAllowedHost(string $host): bool
    {
        $allowedHosts = [
            'api.alemancenter.com',
            'alemancenter.com',
            'www.alemancenter.com',
            'localhost',
        ];

        foreach ($allowedHosts as $allowed) {
            if ($host === $allowed || str_starts_with($host, $allowed . ':')) {
                return true;
            }
        }

        return false;
    }

    /**
     * التحقق من مفتاح الفرونت إند
     */
    protected function isValidFrontendKey(string $key): bool
    {
        $expectedKey = env('FRONTEND_API_KEY');
        if (!$expectedKey) {
            return false;
        }

        return hash_equals($expectedKey, $key);
    }

    /**
     * التحقق من المسارات المستثناة
     */
    protected function isExcludedPath(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->excludedPaths as $excluded) {
            if (str_starts_with($path, $excluded) || $path === $excluded) {
                return true;
            }
        }

        return false;
    }

    /**
     * التحقق من تجاوز معدل الطلبات
     */
    protected function isRateLimited(Request $request): bool
    {
        // Use dedicated throttle middleware for /api/front/* routes
        if ($request->is('api/front/*')) {
            return false;
        }

        $enabled = env('FRONTEND_RATE_LIMIT', true);
        if (!$enabled) {
            return false;
        }

        $maxRequests = (int) env('FRONTEND_RATE_LIMIT_MAX', 100);
        $window = (int) env('FRONTEND_RATE_LIMIT_WINDOW', 60);

        $ip = $request->ip() ?? '0.0.0.0';
        $key = 'frontend_api_rate:' . sha1($ip);

        $bucket = Cache::get($key, ['count' => 0, 'start' => time()]);

        // إعادة تعيين النافذة إذا انتهت
        if ((time() - $bucket['start']) >= $window) {
            $bucket = ['count' => 0, 'start' => time()];
        }

        $bucket['count']++;
        Cache::put($key, $bucket, $window);

        return $bucket['count'] > $maxRequests;
    }

    /**
     * تسجيل محاولة وصول غير مصرح بها
     */
    protected function logUnauthorizedAccess(Request $request): void
    {
        $logEnabled = env('LOG_UNAUTHORIZED_API_ACCESS', true);
        if (!$logEnabled) {
            return;
        }

        Log::warning('Unauthorized API access attempt', [
            'ip' => $request->ip(),
            'path' => $request->path(),
            'method' => $request->method(),
            'origin' => $request->header('Origin'),
            'referer' => $request->header('Referer'),
            'user_agent' => $request->userAgent(),
            'headers' => $this->getSafeHeaders($request),
        ]);
    }

    /**
     * الحصول على headers آمنة للتسجيل
     */
    protected function getSafeHeaders(Request $request): array
    {
        $safe = ['Accept', 'Content-Type', 'X-Requested-With', 'Host'];
        $headers = [];

        foreach ($safe as $header) {
            $value = $request->header($header);
            if ($value) {
                $headers[$header] = $value;
            }
        }

        return $headers;
    }
}
