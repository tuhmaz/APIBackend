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
 *
 * Configuration via .env:
 * - CORS_ALLOWED_ORIGINS: comma-separated list of allowed origins (https://example.com,https://www.example.com)
 * - FRONTEND_URL: main frontend URL
 * - APP_URL: API URL
 */
class FrontendApiGuard
{
    /**
     * المسارات المستثناة من الحماية (مثل webhooks)
     */
    protected array $excludedPaths = [
        'api/auth/google/callback',
        'api/auth/email/verify',
        'api/ping',
    ];

    /**
     * الحصول على النطاقات المسموح بها للـ Origin من البيئة
     */
    protected function getAllowedOrigins(): array
    {
        // Default localhost origins for development
        $origins = [
            'http://localhost:3000',
            'http://localhost:3001',
        ];

        // Add from CORS_ALLOWED_ORIGINS env (comma-separated)
        // This is the single source of truth for allowed origins
        $corsOrigins = env('CORS_ALLOWED_ORIGINS', '');
        if ($corsOrigins && $corsOrigins !== '*') {
            $origins = array_merge($origins, array_filter(array_map('trim', explode(',', $corsOrigins))));
        }

        // Add FRONTEND_URL if set (auto-add www variant)
        $frontendUrl = env('FRONTEND_URL');
        if ($frontendUrl) {
            $origins[] = $frontendUrl;
            $parsed = parse_url($frontendUrl);
            if (isset($parsed['host']) && !str_starts_with($parsed['host'], 'www.')) {
                $origins[] = ($parsed['scheme'] ?? 'https') . '://www.' . $parsed['host'];
            }
        }

        return array_unique(array_filter($origins));
    }

    /**
     * الحصول على النطاقات المسموح بها للـ Referer من البيئة
     */
    protected function getAllowedReferers(): array
    {
        $referers = [
            'localhost:3000',
            'localhost:3001',
        ];

        // Extract hosts from allowed origins
        foreach ($this->getAllowedOrigins() as $origin) {
            $parsed = parse_url($origin);
            if (isset($parsed['host'])) {
                $host = $parsed['host'];
                $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
                $referers[] = $host . $port;
            }
        }

        return array_unique(array_filter($referers));
    }

    /**
     * الحصول على الـ Hosts المسموح بها من البيئة
     */
    protected function getAllowedHosts(): array
    {
        $hosts = ['localhost'];

        // Add from APP_URL
        $appUrl = env('APP_URL');
        if ($appUrl) {
            $parsed = parse_url($appUrl);
            if (isset($parsed['host'])) {
                $hosts[] = $parsed['host'];
            }
        }

        // Add from FRONTEND_URL
        $frontendUrl = env('FRONTEND_URL');
        if ($frontendUrl) {
            $parsed = parse_url($frontendUrl);
            if (isset($parsed['host'])) {
                $hosts[] = $parsed['host'];
                // Add www variant
                if (!str_starts_with($parsed['host'], 'www.')) {
                    $hosts[] = 'www.' . $parsed['host'];
                }
            }
        }

        // Add hosts from allowed origins
        foreach ($this->getAllowedOrigins() as $origin) {
            $parsed = parse_url($origin);
            if (isset($parsed['host'])) {
                $hosts[] = $parsed['host'];
            }
        }

        return array_unique(array_filter($hosts));
    }

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

        // 5. السماح للطلبات من localhost/السيرفر الداخلي (SSR internal requests)
        // هذا يسمح لـ Next.js SSR بالاتصال عبر localhost للأداء الأفضل
        $ip = $request->ip();
        if (in_array($ip, ['127.0.0.1', '::1']) && $xRequestedWith === 'XMLHttpRequest') {
            return true;
        }

        // 5.1 السماح للـ IPs الموثوقة من السيرفر
        $trustedServerIps = env('TRUSTED_SERVER_IPS', '');
        if ($trustedServerIps && $xRequestedWith === 'XMLHttpRequest') {
            $serverIps = array_filter(array_map('trim', explode(',', $trustedServerIps)));
            if (in_array($ip, $serverIps, true)) {
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
        return in_array($origin, $this->getAllowedOrigins(), true);
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

        foreach ($this->getAllowedReferers() as $allowed) {
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
        $allowedHosts = $this->getAllowedHosts();

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

        // Never rate-limit file downloads/info (often proxied through SSR and can spike).
        if (
            $request->is('api/articles/file/*/download') ||
            $request->is('api/download/*') ||
            $request->is('api/files/*/info')
        ) {
            return false;
        }

        $enabled = env('FRONTEND_RATE_LIMIT', true);
        if (!$enabled) {
            return false;
        }

        $ip = $this->getClientIpForRateLimiting($request);

        $isSsr = $this->isServerSideRequest($request);

        // SSR requests typically originate from the Next.js server (single IP) and can spike under load.
        // Use a separate allowlist + higher limit for SSR only (doesn't affect normal users).
        if ($isSsr) {
            if ($this->isSsrIpAllowlisted($ip)) {
                return false;
            }

            $maxRequests = (int) env('SSR_RATE_LIMIT_MAX', 2000);
            $window = (int) env('SSR_RATE_LIMIT_WINDOW', 60);
            $key = 'frontend_api_rate:ssr:' . sha1($ip);
        } else {
            $maxRequests = (int) env('FRONTEND_RATE_LIMIT_MAX', 100);
            $window = (int) env('FRONTEND_RATE_LIMIT_WINDOW', 60);
            $key = 'frontend_api_rate:' . sha1($ip);
        }

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
     * SSR allowlist (comma-separated IPs; CIDR is not supported here).
     * Example: SSR_TRUSTED_IPS="152.53.208.71,127.0.0.1"
     */
    protected function isSsrIpAllowlisted(string $ip): bool
    {
        $raw = (string) env('SSR_TRUSTED_IPS', '');
        $raw = trim($raw);
        if ($raw === '' || $ip === '') {
            return false;
        }

        $entries = array_filter(array_map('trim', explode(',', $raw)));
        foreach ($entries as $entry) {
            if ($entry !== '' && hash_equals($entry, $ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * تسجيل محاولة وصول غير مصرح بها
     */
    /**
     * Resolve a stable client IP for rate limiting (supports common proxy headers).
     */
    protected function getClientIpForRateLimiting(Request $request): string
    {
        $cf = $request->header('CF-Connecting-IP');
        if (is_string($cf) && trim($cf) !== '') {
            return trim($cf);
        }

        $xff = $request->header('X-Forwarded-For');
        if (is_string($xff) && trim($xff) !== '') {
            $first = trim(explode(',', $xff)[0] ?? '');
            if ($first !== '') {
                return $first;
            }
        }

        $xri = $request->header('X-Real-IP');
        if (is_string($xri) && trim($xri) !== '') {
            return trim($xri);
        }

        return $request->ip() ?? '0.0.0.0';
    }

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
