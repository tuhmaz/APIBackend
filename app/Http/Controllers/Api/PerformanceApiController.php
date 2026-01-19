<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SystemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\BaseResource;

class PerformanceApiController extends Controller
{
    protected $systemService;

    public function __construct(SystemService $systemService)
    {
        $this->systemService = $systemService;
    }

    /**
     * Return full system metrics summary
     */
    public function summary(Request $request)
    {
        try {
            $stats = $this->systemService->getSystemInfo();

            if (!$stats) {
                throw new \Exception('System stats unavailable');
            }

            return new BaseResource([
                'metrics' => [
                    'cpu'    => $stats['cpu'] ?? [],
                    'memory' => $stats['memory'] ?? [],
                    'disk'   => $stats['disk'] ?? [],
                ],
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Performance summary error: ' . $e->getMessage());
            return (new BaseResource(['message' => 'Unable to load system metrics']))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * Live metrics (cached 5 seconds)
     */
    public function live(Request $request)
    {
        try {
            $cacheKey = 'system_metrics_live';
            $cacheFor = 5;

            $data = Cache::remember($cacheKey, $cacheFor, function () {
                $stats = $this->systemService->getSystemInfo();
                if (!$stats) {
                    throw new \Exception('Missing stats');
                }

                return [
                    'cpu' => [
                        'usage'   => $stats['cpu']['usage_percentage'] ?? 0,
                        'cores'   => $stats['cpu']['cores'] ?? 1,
                        'load'    => $stats['cpu']['load'][0] ?? 0,
                    ],
                    'memory' => $stats['memory'] ?? [],
                    'disk'   => $stats['disk'] ?? [],
                    'timestamp' => now()->toIso8601String(),
                ];
            });

            return new BaseResource($data);
        } catch (\Throwable $e) {
            Log::error("Performance live error: {$e->getMessage()}");
            return (new BaseResource(['message' => 'Unable to fetch metrics']))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * Raw stats (unfiltered)
     */
    public function raw(Request $request)
    {
        try {
            $stats = $this->systemService->getSystemInfo();
            return new BaseResource(['data' => $stats]);
        } catch (\Throwable $e) {
            Log::error("Performance raw error: {$e->getMessage()}");
            return (new BaseResource(['message' => 'Unable to fetch metrics data']))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * Average response time (internal)
     */
    public function responseTime()
    {
        try {
            $avg = $this->calculateAverageResponseTime();
            return new BaseResource(['average_ms' => $avg]);
        } catch (\Throwable $e) {
            return (new BaseResource(['message' => 'Unable to calculate average']))
                ->response(request())
                ->setStatusCode(500);
        }
    }

    /**
     * Cache efficiency
     */
    public function cacheStats()
    {
        try {
            return new BaseResource([
                'hit_ratio' => $this->cacheHitRatio(),
                'cache_size' => $this->cacheSize(),
            ]);
        } catch (\Throwable $e) {
            return (new BaseResource(['message' => 'Unable to fetch cache stats']))
                ->response(request())
                ->setStatusCode(500);
        }
    }

    /* ---------------------------------------------------------
       INTERNAL HELPERS (copied from original controller)
    ----------------------------------------------------------*/

    protected function calculateAverageResponseTime()
    {
        $key = 'average_response_time';
        $samples = Cache::get($key, []);

        $start = microtime(true);
        $this->systemService->getSystemStats();
        $end = microtime(true);

        $current = ($end - $start) * 1000;

        $samples[] = $current;
        if (count($samples) > 10) {
            array_shift($samples);
        }

        Cache::put($key, $samples, now()->addMinutes(5));
        return round(array_sum($samples) / count($samples), 2);
    }

    protected function cacheHitRatio()
    {
        $hits = Cache::get('cache_hits', 0);
        $misses = Cache::get('cache_misses', 0);
        $total = $hits + $misses;

        return $total === 0 ? 0 : round(($hits / $total) * 100, 2);
    }

    protected function cacheSize()
    {
        try {
            $size = 0;
            if (function_exists('shell_exec')) {
                $dir = storage_path('framework/cache');
                $output = shell_exec("du -sb $dir 2>/dev/null");
                if ($output) {
                    $size = (int) explode("\t", $output)[0];
                }
            }
            return $this->formatBytes($size);
        } catch (\Throwable) {
            return '0 B';
        }
    }

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}
