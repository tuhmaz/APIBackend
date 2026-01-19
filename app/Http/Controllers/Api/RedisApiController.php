<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Models\RedisLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use App\Http\Resources\BaseResource;

class RedisApiController extends Controller
{
    /**
     * GET /api/redis/keys
     * عرض جميع المفاتيح مع إمكانية البحث
     */
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return (new BaseResource(['message' => 'Unauthorized']))
                ->response($request)
                ->setStatusCode(401);
        }

        $search = $request->query('search');
        $pattern = $search ? "*$search*" : '*';
        $keys = Redis::keys($pattern);

        $result = [];
        foreach ($keys as $key) {
            $result[] = [
                'key'   => $key,
                'value' => Redis::get($key),
                'ttl'   => Redis::ttl($key),
            ];
        }

        return (new BaseResource(['count' => count($result), 'data' => $result]));
    }

    /**
     * GET /api/redis/test
     * فحص اتصال Redis
     */
    public function testConnection()
    {
        try {
            Redis::ping();
            return new BaseResource(['message' => 'Redis connected successfully.']);
        } catch (\Exception $e) {
            return (new BaseResource(['message' => 'Redis connection failed: ' . $e->getMessage()]))
                ->response(request())
                ->setStatusCode(500);
        }
    }

    /**
     * POST /api/redis
     * إضافة مفتاح جديد إلى Redis
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key'   => 'required|string|max:255',
            'value' => 'required|string',
            'ttl'   => 'nullable|integer|min:1',
        ]);

        Redis::set($validated['key'], $validated['value']);

        if (!empty($validated['ttl'])) {
            Redis::expire($validated['key'], $validated['ttl']);
        }

        // سجل العملية
        $this->logAction($validated['key'], $validated['value'], $validated['ttl'] ?? null, 'add');

        return new BaseResource(['message' => 'Key added successfully.']);
    }

    /**
     * DELETE /api/redis/{key}
     * حذف مفتاح من Redis
     */
    public function destroy($key)
    {
        $value = Redis::get($key);
        $ttl   = Redis::ttl($key);

        Redis::del($key);

        $this->logAction($key, $value, $ttl, 'delete');

        return new BaseResource(['message' => "Key '{$key}' deleted successfully."]);
    }

    /**
     * DELETE /api/redis/expired
     * حذف المفاتيح المنتهية
     */
    public function cleanExpired()
    {
        $keys = Redis::keys('*');
        $deleted = 0;

        foreach ($keys as $key) {
            if (Redis::ttl($key) === -2) {
                Redis::del($key);
                $deleted++;
            }
        }

        return new BaseResource(['deleted' => $deleted, 'message' => 'Expired keys cleaned successfully.']);
    }

    /**
     * GET /api/redis/info
     * إرجاع معلومات Redis
     */
    public function info()
    {
        $info = Redis::info();

        return new BaseResource(['data' => $info]);
    }

    /**
     * عمليات التسجيل في redis_logs
     */
    private function logAction($key, $value, $ttl, $action)
    {
        RedisLog::create([
            'key'   => $key,
            'value' => $value,
            'ttl'   => $ttl,
            'action' => $action,
            'user'   => Auth::user()->name ?? 'System',
        ]);
    }

    /**
     * GET /api/redis/env
     * جلب إعدادات Redis من ملف .env
     */
    public function envSettings()
    {
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            return (new BaseResource(['message' => 'ENV file not found']))
                ->response(request())
                ->setStatusCode(404);
        }

        $data = [];
        foreach (File::lines($envPath) as $line) {
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $data[trim($key)] = trim($value);
            }
        }

        return new BaseResource(['data' => $data]);
    }

    /**
     * POST /api/redis/env
     * تحديث إعدادات Redis في ملف .env
     */
    public function updateEnvSettings(Request $request)
    {
        $updates = $request->only(['REDIS_HOST', 'REDIS_PORT', 'REDIS_PASSWORD', 'REDIS_DB']);

        $envPath = base_path('.env');
        $content = File::get($envPath);

        foreach ($updates as $key => $value) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        }

        File::put($envPath, $content);

        Artisan::call('config:clear');
        Artisan::call('cache:clear');

        return new BaseResource(['message' => 'Environment updated successfully.']);
    }
}
