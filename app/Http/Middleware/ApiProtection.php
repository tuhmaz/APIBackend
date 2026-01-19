<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class ApiProtection
{
    /**
     * حماية واجهة API من الوصول غير المصرح به
     * يتحقق من وجود مفتاح API صالح ويسمح فقط للتطبيقات المحمولة بالوصول
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Allow preflight requests without API checks
        if ($request->isMethod('OPTIONS')) {
            return response()->noContent();
        }

        $headersCfg = Config::get('api_keys.headers', ['key' => 'X-Api-Key', 'client' => 'User-Agent']);
        $userAgent = $request->header($headersCfg['client'], $request->header('User-Agent'));
        // دعم التوافق السابق مع X-API-KEY (من الترويسة أولاً)
        $apiKey = $request->header($headersCfg['key'], $request->header('X-API-KEY'));
        // خيارياً: السماح بقراءة المفتاح من الاستعلام/الجسم إذا كانت الترويسة غير موجودة ومفعّل من الإعدادات
        if (!$apiKey && (bool) Config::get('api_keys.allow_query_key', false)) {
            $apiKey = $request->query($headersCfg['key'])
                ?? $request->query('X-API-KEY')
                ?? $request->query('api_key')
                ?? $request->input($headersCfg['key'])
                ?? $request->input('X-API-KEY')
                ?? $request->input('api_key');
        }
        
        // الحصول على مفتاح API من ملف التكوين
        $expectedApiKey = Config::get('api_keys.key');
        
        // الحصول على قائمة العملاء المسموح بهم من ملف التكوين
        $allowedClients = Config::get('api_keys.allowed_clients', []);
        $allowPostman = (bool) Config::get('api_keys.allow_postman', app()->environment('local', 'development', 'testing'));
        if ($allowPostman) {
            $allowedClients[] = 'PostmanRuntime';
        }
        
        // التحقق مما إذا كان يجب تسجيل محاولات الوصول غير المصرح بها
        $logUnauthorizedAttempts = (bool) Config::get('api_keys.security.log_unauthorized_attempts', true);
        
        // التحقق مما إذا كان يجب التحقق من نوع العميل
        $checkClientType = (bool) Config::get('api_keys.security.check_client_type', true);

        // التحقق من القائمة البيضاء لعناوين IP إن كانت مفعّلة
        $ipWhitelist = Config::get('api_keys.security.ip_whitelist', []);
        if (!empty($ipWhitelist) && !in_array($request->ip(), $ipWhitelist, true)) {
            if ($logUnauthorizedAttempts) {
                Log::warning('رفض الوصول إلى API - IP غير موجود في القائمة البيضاء', [
                    'ip' => $request->ip(), 'path' => $request->path()
                ]);
            }
            return response()->json(['status' => false, 'message' => 'IP غير مسموح به'], 403);
        }

        // تطبيق تحديد المعدّل البسيط (Rate Limit) إن كان مفعلاً
        $rateCfg = Config::get('api_keys.security.rate_limit', ['enabled' => true, 'max' => 60, 'per_seconds' => 60]);
        if (!empty($rateCfg['enabled'])) {
            $window = max(1, (int) ($rateCfg['per_seconds'] ?? 60));
            $max = max(1, (int) ($rateCfg['max'] ?? 60));
            $bucketKey = 'api:rate:' . sha1(($request->ip() ?: '0.0.0.0') . '|' . ($request->path() ?: '/'));
            $now = time();
            $bucket = Cache::get($bucketKey, ['start' => $now, 'count' => 0]);
            if (($now - $bucket['start']) >= $window) {
                $bucket = ['start' => $now, 'count' => 0];
            }
            $bucket['count'] += 1;
            Cache::put($bucketKey, $bucket, $window);
            if ($bucket['count'] > $max) {
                return response()->json(['status' => false, 'message' => 'تم تجاوز الحد المسموح للطلبات'], 429);
            }
        }

        // التحقق من وجود API Key
        if (!$apiKey) {
            if ($logUnauthorizedAttempts) {
                Log::warning('محاولة وصول غير مصرح بها إلى API - مفتاح API غير موجود', [
                    'ip' => $request->ip(),
                    'user_agent' => $userAgent,
                    'path' => $request->path()
                ]);
            }
            
            return response()->json([
                'status' => false,
                'message' => 'مفتاح API مطلوب'
            ], 403);
        }
        
        // استخدام hash_equals لمقارنة مفاتيح API لمنع هجمات التوقيت
        if (!$expectedApiKey || !hash_equals($expectedApiKey, $apiKey)) {
            if ($logUnauthorizedAttempts) {
                Log::warning('محاولة وصول غير مصرح بها إلى API - مفتاح API غير صالح', [
                    'ip' => $request->ip(),
                    'user_agent' => $userAgent,
                    'path' => $request->path()
                ]);
            }
            
            return response()->json([
                'status' => false,
                'message' => 'مفتاح API غير صالح'
            ], 403);
        }

        // التحقق من التوقيع HMAC إن كان مفعلاً
        $sigCfg = Config::get('api_keys.security.signature', []);
        if (!empty($sigCfg['enabled'])) {
            $secret = (string) ($sigCfg['secret'] ?? '');
            $algo = (string) ($sigCfg['algorithm'] ?? 'sha256');
            $sigHeader = (string) ($sigCfg['header'] ?? 'X-Signature');
            $tsHeader = (string) ($sigCfg['timestamp_header'] ?? 'X-Timestamp');
            $nonceHeader = (string) ($sigCfg['nonce_header'] ?? 'X-Nonce');
            $allowedDrift = (int) ($sigCfg['allowed_drift'] ?? 300);

            $providedSig = (string) $request->header($sigHeader, '');
            $ts = (int) $request->header($tsHeader, 0);
            $nonce = (string) $request->header($nonceHeader, '');

            if (!$secret || !$providedSig || !$ts) {
                return response()->json(['status' => false, 'message' => 'توقيع مفقود أو غير صالح'], 403);
            }

            if (abs(time() - $ts) > $allowedDrift) {
                return response()->json(['status' => false, 'message' => 'انتهت صلاحية التوقيع'], 403);
            }

            $body = (string) $request->getContent();
            $payload = $ts . '\n' . $nonce . '\n' . $request->method() . '\n' . $request->path() . '\n' . $body;
            $computed = hash_hmac($algo, $payload, $secret);

            if (!hash_equals($computed, $providedSig)) {
                return response()->json(['status' => false, 'message' => 'توقيع غير صالح'], 403);
            }
        }

        // إذا كان التحقق من نوع العميل غير مفعل، نسمح بالطلب
        if (!$checkClientType) {
            return $next($request);
        }

        // التحقق من العملاء المسموح بهم
        $isAllowedClient = false;
        foreach ($allowedClients as $client) {
            if (stripos($userAgent, $client) !== false) {
                $isAllowedClient = true;
                break;
            }
        }

        // إذا لم يكن العميل مسموحًا به، نرفض الطلب
        if (!$isAllowedClient) {
            if ($logUnauthorizedAttempts) {
                Log::warning('محاولة وصول غير مصرح بها إلى API - عميل غير مسموح به', [
                    'ip' => $request->ip(),
                    'user_agent' => $userAgent,
                    'path' => $request->path()
                ]);
            }
            
            return response()->json([
                'status' => false,
                'message' => 'الوصول مسموح فقط من التطبيقات المحمولة المعتمدة'
            ], 403);
        }

        return $next($request);
    }
}
