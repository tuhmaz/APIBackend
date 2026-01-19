<?php

namespace App\Services;

use App\Models\RedisLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheMonitoringService
{
    public function logCacheOperation($key, $operation, $duration = null, $memory = null)
    {
        RedisLog::create([
            'key' => $key,
            'value' => $memory ?? $this->getMemoryUsage($key),
            'ttl' => Cache::ttl($key),
            'action' => $operation,
            'user' => auth()->user()->id ?? 'system',
        ]);
    }

    public function getCacheStats()
    {
        $redis = Redis::connection('cache');
        
        return [
            'memory_usage' => $redis->info('memory')['used_memory_human'],
            'keys_count' => $redis->dbSize(),
            'hit_ratio' => $this->calculateHitRatio(),
            'avg_ttl' => $this->calculateAvgTTL(),
        ];
    }

    private function getMemoryUsage($key)
    {
        $redis = Redis::connection('cache');
        $serialized = $redis->get($key);
        return strlen($serialized) / 1024; // KB
    }

    private function calculateHitRatio()
    {
        $redis = Redis::connection('cache');
        $info = $redis->info('stats');
        
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        
        return $hits + $misses > 0 ? round(($hits / ($hits + $misses)) * 100, 2) : 0;
    }

    private function calculateAvgTTL()
    {
        return 3600; // قيمة افتراضية
    }
}
