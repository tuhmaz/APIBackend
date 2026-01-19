<?php

namespace app\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheOptimizationService
{
    public function optimizeRedisConnection()
    {
        // تنظيف الكاش القديم
        Cache::flush();
        
        // إعادة تهيئة الاتصال
        $redis = Redis::connection('cache');
        $redis->config('SET', 'maxmemory', '256mb');
        $redis->config('SET', 'maxmemory-policy', 'allkeys-lru');
        $redis->config('SET', 'timeout', '300');
        
        return true;
    }

    public function consolidateConnections()
    {
        // إيقاف الاتصالات القديمة
        $oldConnections = ['jo_redis', 'sa_redis', 'eg_redis', 'ps_redis'];
        
        foreach ($oldConnections as $connection) {
            try {
                Redis::connection($connection)->disconnect();
            } catch (\Exception $e) {
                // تجاهل الأخطاء غير الحرجة
            }
        }
        
        return true;
    }

    public function setupOptimizedCache()
    {
        // إعداد الكاش المحسن
        Cache::extend('optimized-redis', function ($app) {
            return $app['cache']->repository(
                new \Illuminate\Cache\RedisStore(
                    $app['redis']->connection('cache'),
                    'optimized_',
                    3600
                )
            );
        });
        
        return true;
        }
    }
