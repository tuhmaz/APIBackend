<?php

namespace App\Http\Middleware;

use App\Models\CachePerformanceLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CachePerformanceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // تسجيل عمليات الكاش قبل المعالجة
        $this->logCacheOperations('before_request', $request);
        
        $response = $next($request);
        
        // تسجيل عمليات الكاش بعد المعالجة
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $this->logCacheOperations('after_request', $request, [
            'response_time' => ($endTime - $startTime) * 1000, // milliseconds
            'memory_usage' => ($endMemory - $startMemory) / 1024, // KB
        ]);
        
        return $response;
    }

    private function logCacheOperations($operation, Request $request, $metrics = [])
    {
        try {
            // تسجيل معلومات الطلب والكاش
            CachePerformanceLog::create([
                'cache_key' => $this->generateCacheKey($request),
                'operation' => $operation,
                'response_time_ms' => $metrics['response_time'] ?? null,
                'memory_usage_kb' => $metrics['memory_usage'] ?? null,
                'ttl' => 3600, // قيمة افتراضية
                'user_id' => auth()->id() ?? 'guest',
            ]);
        } catch (\Exception $e) {
            // تجاهل أخطاء التسجيل لتجنب تعطيل التطبيق
            \Log::warning('Cache performance logging failed: ' . $e->getMessage());
        }
    }

    private function generateCacheKey(Request $request)
    {
        return 'request_' . md5($request->getPathInfo() . serialize($request->query()));
    }
}
