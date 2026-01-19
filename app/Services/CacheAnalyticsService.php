<?php

namespace App\Services;

use App\Models\CachePerformanceLog;
use App\Models\RedisLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class CacheAnalyticsService
{
    public function generatePerformanceReport($hours = 24)
    {
        $logs = CachePerformanceLog::recent($hours)->get();
        
        return [
            'summary' => [
                'total_operations' => $logs->count(),
                'cache_hits' => $logs->where('operation', 'hit')->count(),
                'cache_misses' => $logs->where('operation', 'miss')->count(),
                'hit_ratio' => $this->calculateHitRatio($logs),
                'avg_response_time' => $logs->avg('response_time_ms'),
                'total_memory_usage' => $logs->sum('memory_usage_kb'),
            ],
            'top_keys' => $this->getTopKeys($logs),
            'performance_trends' => $this->getPerformanceTrends($logs),
            'memory_analysis' => $this->getMemoryAnalysis($logs),
        ];
    }

    public function identifyProblematicKeys($threshold = 100)
    {
        return CachePerformanceLog::select('cache_key')
            ->selectRaw('AVG(response_time_ms) as avg_response_time')
            ->selectRaw('COUNT(*) as operation_count')
            ->groupBy('cache_key')
            ->having('avg_response_time', '>', $threshold)
            ->orderBy('avg_response_time', 'desc')
            ->get();
    }

    public function getUnusedKeys($days = 7)
    {
        $redis = Redis::connection('cache');
        $allKeys = $redis->keys('*');
        
        $usedKeys = CachePerformanceLog::where('created_at', '>=', now()->subDays($days))
            ->distinct()
            ->pluck('cache_key')
            ->toArray();
            
        return array_diff($allKeys, $usedKeys);
    }

    public function optimizeCache()
    {
        $recommendations = [];
        
        // تحديد المفاتيح غير المستخدمة
        $unusedKeys = $this->getUnusedKeys();
        if (count($unusedKeys) > 0) {
            $recommendations[] = [
                'type' => 'cleanup',
                'message' => 'Found ' . count($unusedKeys) . ' unused cache keys',
                'keys' => array_slice($unusedKeys, 0, 10), // أول 10 مفاتيح
                'action' => 'delete_unused_keys'
            ];
        }
        
        // تحديد المفاتيح البطيئة
        $slowKeys = $this->identifyProblematicKeys(50);
        if ($slowKeys->count() > 0) {
            $recommendations[] = [
                'type' => 'performance',
                'message' => 'Found ' . $slowKeys->count() . ' slow cache keys',
                'keys' => $slowKeys->take(5)->toArray(),
                'action' => 'optimize_slow_keys'
            ];
        }
        
        // تحليل استهلاك الذاكرة
        $memoryAnalysis = $this->getMemoryAnalysis(CachePerformanceLog::recent(24)->get());
        if ($memoryAnalysis['high_memory_keys']->count() > 0) {
            $recommendations[] = [
                'type' => 'memory',
                'message' => 'Found keys consuming high memory',
                'keys' => $memoryAnalysis['high_memory_keys']->take(5)->toArray(),
                'action' => 'optimize_memory_usage'
            ];
        }
        
        return $recommendations;
    }

    private function calculateHitRatio($logs)
    {
        $hits = $logs->where('operation', 'hit')->count();
        $total = $logs->whereIn('operation', ['hit', 'miss'])->count();
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    private function getTopKeys($logs)
    {
        return $logs->groupBy('cache_key')
            ->map(function ($keyLogs) {
                return [
                    'key' => $keyLogs->first()->cache_key,
                    'operations' => $keyLogs->count(),
                    'avg_response_time' => $keyLogs->avg('response_time_ms'),
                    'total_memory' => $keyLogs->sum('memory_usage_kb'),
                ];
            })
            ->sortByDesc('operations')
            ->take(10)
            ->values();
    }

    private function getPerformanceTrends($logs)
    {
        return $logs->groupBy(function ($log) {
            return $log->created_at->format('Y-m-d H:00');
        })->map(function ($hourLogs, $hour) {
            return [
                'hour' => $hour,
                'operations' => $hourLogs->count(),
                'avg_response_time' => $hourLogs->avg('response_time_ms'),
                'hit_ratio' => $this->calculateHitRatio($hourLogs),
            ];
        })->values();
    }

    private function getMemoryAnalysis($logs)
    {
        $highMemoryKeys = $logs->groupBy('cache_key')
            ->map(function ($keyLogs) {
                return [
                    'key' => $keyLogs->first()->cache_key,
                    'avg_memory' => $keyLogs->avg('memory_usage_kb'),
                    'max_memory' => $keyLogs->max('memory_usage_kb'),
                ];
            })
            ->where('avg_memory', '>', 1024) // أكبر من 1MB
            ->sortByDesc('avg_memory');

        return [
            'total_memory_usage' => $logs->sum('memory_usage_kb'),
            'avg_memory_per_key' => $logs->avg('memory_usage_kb'),
            'high_memory_keys' => $highMemoryKeys,
        ];
    }
}
