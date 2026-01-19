<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\CacheAnalyticsService;
use App\Services\CacheMonitoringService;
use App\Services\CacheOptimizationService;
use App\Models\CachePerformanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class RedisCacheController extends Controller
{
    protected $cacheAnalytics;
    protected $cacheMonitoring;
    protected $cacheOptimization;

    public function __construct(
        CacheAnalyticsService $cacheAnalytics,
        CacheMonitoringService $cacheMonitoring,
        CacheOptimizationService $cacheOptimization
    ) {
        $this->cacheAnalytics = $cacheAnalytics;
        $this->cacheMonitoring = $cacheMonitoring;
        $this->cacheOptimization = $cacheOptimization;
    }

    /**
     * عرض صفحة مراقبة الكاش
     */
    public function monitoring(Request $request)
    {
        $hours = $request->get('hours', 24);
        
        // الحصول على الإحصائيات
        $stats = $this->cacheMonitoring->getCacheStats();
        
        // تقرير الأداء
        $report = $this->cacheAnalytics->generatePerformanceReport($hours);
        
        // توصيات التحسين
        $recommendations = $this->cacheAnalytics->optimizeCache();
        
        // بيانات الرسوم البيانية
        $chartData = $this->prepareChartData($hours);
        
        return view('content.dashboard.redis.monitoring', compact(
            'stats',
            'report', 
            'recommendations',
            'chartData'
        ));
    }

    /**
     * الحصول على الإحصائيات عبر AJAX
     */
    public function getStats()
    {
        $stats = $this->cacheMonitoring->getCacheStats();
        return response()->json($stats);
    }

    /**
     * تحسين الكاش
     */
    public function optimize()
    {
        try {
            $this->cacheOptimization->optimizeRedisConnection();
            $this->cacheOptimization->consolidateConnections();
            $this->cacheOptimization->setupOptimizedCache();
            
            return response()->json([
                'success' => true,
                'message' => __('Cache optimization completed successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error occurred during optimization: ') . $e->getMessage()
            ]);
        }
    }

    /**
     * تحليل مفتاح محدد
     */
    public function analyzeKey(Request $request)
    {
        $key = $request->input('key');
        
        $analysis = CachePerformanceLog::where('cache_key', $key)
            ->selectRaw('
                COUNT(*) as operations,
                AVG(response_time_ms) as avg_response_time,
                AVG(memory_usage_kb) as memory_usage,
                AVG(ttl) as ttl
            ')
            ->first();
            
        return response()->json($analysis);
    }

    /**
     * تطبيق توصية التحسين
     */
    public function applyRecommendation(Request $request)
    {
        $action = $request->input('action');
        
        try {
            switch ($action) {
                case 'delete_unused_keys':
                    $this->cacheOptimization->consolidateConnections();
                    break;
                    
                case 'optimize_slow_keys':
                    $this->cacheOptimization->optimizeRedisConnection();
                    break;
                    
                case 'optimize_memory_usage':
                    $this->cacheOptimization->setupOptimizedCache();
                    break;
                    
                default:
                    throw new \Exception('Unknown optimization action');
            }
            
            return response()->json([
                'success' => true,
                'message' => __('Optimization applied successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error occurred: ') . $e->getMessage()
            ]);
        }
    }

    /**
     * تنظيف المفاتيح المنتهية الصلاحية
     */
    public function cleanExpiredKeys()
    {
        try {
            $redis = Redis::connection('cache');
            $keys = $redis->keys('*');
            $deletedCount = 0;
            
            foreach ($keys as $key) {
                $ttl = $redis->ttl($key);
                if ($ttl == -2) { // المفتاح منتهي الصلاحية
                    $redis->del($key);
                    $deletedCount++;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => __('Deleted :count expired keys', ['count' => $deletedCount])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error occurred: ') . $e->getMessage()
            ]);
        }
    }

    /**
     * إضافة مفتاح جديد
     */
    public function addKey(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required|string',
            'ttl' => 'nullable|integer|min:1'
        ]);

        try {
            $key = $request->input('key');
            $value = $request->input('value');
            $ttl = $request->input('ttl', 3600);

            Cache::put($key, $value, $ttl);
            
            // تسجيل العملية
            $this->cacheMonitoring->logCacheOperation($key, 'set', null, strlen($value) / 1024);

            return response()->json([
                'success' => true,
                'message' => __('Key added successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error occurred: ') . $e->getMessage()
            ]);
        }
    }

    /**
     * حذف مفتاح
     */
    public function deleteKey($key)
    {
        try {
            Cache::forget($key);
            
            // تسجيل العملية
            $this->cacheMonitoring->logCacheOperation($key, 'delete');

            return response()->json([
                'success' => true,
                'message' => __('Key deleted successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error occurred: ') . $e->getMessage()
            ]);
        }
    }

    /**
     * تحضير بيانات الرسوم البيانية
     */
    private function prepareChartData($hours)
    {
        $logs = CachePerformanceLog::where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at')
            ->get();

        // بيانات الأداء بالساعة
        $performanceData = $logs->groupBy(function ($log) {
            return $log->created_at->format('H:00');
        })->map(function ($hourLogs) {
            return $hourLogs->avg('response_time_ms');
        });

        // بيانات استهلاك الذاكرة
        $memoryData = $logs->groupBy('cache_key')
            ->map(function ($keyLogs) {
                return [
                    'key' => $keyLogs->first()->cache_key,
                    'memory' => $keyLogs->sum('memory_usage_kb')
                ];
            })
            ->sortByDesc('memory')
            ->take(5);

        return [
            'performance' => $performanceData->values()->toArray(),
            'hours' => $performanceData->keys()->toArray(),
            'memory_series' => $memoryData->pluck('memory')->toArray(),
            'memory_labels' => $memoryData->map(function ($item) {
                return substr($item['key'], 0, 20) . '...';
            })->toArray()
        ];
    }

    /**
     * تصدير تقرير الأداء
     */
    public function exportReport(Request $request)
    {
        $hours = $request->get('hours', 24);
        $report = $this->cacheAnalytics->generatePerformanceReport($hours);
        
        $filename = 'cache_performance_report_' . now()->format('Y-m-d_H-i-s') . '.json';
        
        return response()->json($report)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * البحث في المفاتيح
     */
    public function searchKeys(Request $request)
    {
        $search = $request->get('search', '');
        $redis = Redis::connection('cache');
        
        if (empty($search)) {
            $keys = $redis->keys('*');
        } else {
            $keys = $redis->keys('*' . $search . '*');
        }
        
        $results = [];
        foreach (array_slice($keys, 0, 50) as $key) { // أول 50 نتيجة
            $results[] = [
                'key' => $key,
                'ttl' => $redis->ttl($key),
                'type' => $redis->type($key),
                'size' => strlen($redis->get($key)) / 1024 // KB
            ];
        }
        
        return response()->json($results);
    }

    /**
     * الحصول على تفاصيل مفتاح
     */
    public function getKeyDetails($key)
    {
        $redis = Redis::connection('cache');
        
        $details = [
            'key' => $key,
            'value' => $redis->get($key),
            'ttl' => $redis->ttl($key),
            'type' => $redis->type($key),
            'size' => strlen($redis->get($key)) / 1024,
            'created_at' => null // Redis لا يحفظ تاريخ الإنشاء
        ];
        
        // البحث في سجلات الأداء
        $performanceLog = CachePerformanceLog::where('cache_key', $key)
            ->latest()
            ->first();
            
        if ($performanceLog) {
            $details['last_accessed'] = $performanceLog->created_at;
            $details['operations_count'] = CachePerformanceLog::where('cache_key', $key)->count();
        }
        
        return response()->json($details);
    }
}
