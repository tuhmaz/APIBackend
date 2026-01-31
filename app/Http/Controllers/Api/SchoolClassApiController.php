<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\Api\SchoolClassResource;
use App\Http\Resources\BaseResource;
use App\Services\DatabaseManager;

class SchoolClassApiController extends Controller
{
    /**
     * خريطة الدول إلى اتصالات قواعد البيانات
     */
    private array $countries = [
        '1' => 'jo',
        '2' => 'sa',
        '3' => 'eg',
        '4' => 'ps',
    ];

    /**
     * Country codes to IDs mapping
     */
    private array $countryCodes = [
        'jo' => '1',
        'sa' => '2',
        'eg' => '3',
        'ps' => '4',
    ];

    /**
     * تحديد الاتصال المناسب
     * يقرأ من: query param -> header -> default
     */
    private function connection(Request $request): string
    {
        // 1. Try query parameter first
        $countryId = $request->query('country_id');

        // 2. Try X-Country-Id header (from SSR requests)
        if (!$countryId) {
            $countryId = $request->header('X-Country-Id');
        }

        // 3. Try database query param (country code like 'jo', 'sa')
        if (!$countryId) {
            $database = $request->query('database');
            if ($database && isset($this->countryCodes[$database])) {
                return $database; // Return the connection name directly
            }
        }

        // 4. Try X-Country-Code header
        if (!$countryId) {
            $countryCode = $request->header('X-Country-Code');
            if ($countryCode && isset($this->countryCodes[$countryCode])) {
                return $countryCode; // Return the connection name directly
            }
        }

        // 5. Default to Jordan
        $countryId = $countryId ?: '1';

        return $this->countries[$countryId] ?? 'jo';
    }

    /**
     * Get country ID from request (for response)
     */
    private function getCountryId(Request $request): string
    {
        $countryId = $request->query('country_id') ?? $request->header('X-Country-Id');

        if (!$countryId) {
            $database = $request->query('database') ?? $request->header('X-Country-Code');
            if ($database && isset($this->countryCodes[$database])) {
                return $this->countryCodes[$database];
            }
        }

        return $countryId ?: '1';
    }

    private function clearCache(string $connection, ?int $classId = null): void
    {
        Cache::forget("school_classes_{$connection}");
        if ($classId !== null) {
            Cache::forget("school_class_{$connection}_{$classId}");
        }
    }

    private function remember(string $cacheKey, int $ttl, \Closure $callback)
    {
        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * GET /api/school-classes?country_id=1
     * جلب جميع الصفوف
     * Supports: query param (country_id, database) and headers (X-Country-Id, X-Country-Code)
     */
    public function index(Request $request)
    {
        $connection = $this->connection($request);
        $countryId = $this->getCountryId($request);

        // Use cache for school classes
        $cacheKey = "school_classes_{$connection}";
        $classes = $this->remember($cacheKey, 600, function () use ($connection) {
            return SchoolClass::on($connection)
                ->withCount('subjects')
                ->orderBy('grade_level')
                ->orderBy('grade_name')
                ->get();
        });

        return SchoolClassResource::collection($classes)
            ->additional([
                'success' => true,
                'country_id' => $countryId,
            ]);
    }

    /**
     * GET /api/school-classes/{id}?country_id=1
     * عرض صف واحد
     * Supports: query param (country_id, database) and headers (X-Country-Id, X-Country-Code)
     */
    public function show(Request $request, $id)
    {
        $connection = $this->connection($request);
        $countryId = $this->getCountryId($request);

        // Debug log for SSR troubleshooting
        if (config('app.debug')) {
            Log::debug('SchoolClass show request', [
                'id' => $id,
                'connection' => $connection,
                'country_id' => $countryId,
                'query_params' => $request->query(),
                'headers' => [
                    'X-Country-Id' => $request->header('X-Country-Id'),
                    'X-Country-Code' => $request->header('X-Country-Code'),
                    'X-Frontend-Key' => $request->header('X-Frontend-Key') ? 'present' : 'missing',
                ],
            ]);
        }

        // Get cache version for invalidation (set when articles are created/updated)
        $cacheVersion = Cache::get("filter_cache_version_{$connection}", 0);

        // Use cache with version - 60 seconds TTL for fresher data
        $cacheKey = "school_class_{$connection}_{$id}_v{$cacheVersion}";
        $schoolClass = $this->remember($cacheKey, 60, function () use ($connection, $id) {
            try {
                return SchoolClass::on($connection)
                    ->with(['subjects' => function ($query) {
                        // Count only published articles
                        $query->withCount([
                            'articles' => function ($q) {
                                $q->where('status', 1);
                            },
                            'files' => function ($q) {
                                $q->whereHas('article', function ($aq) {
                                    $aq->where('status', 1);
                                });
                            }
                        ]);
                    }, 'semesters'])
                    ->findOrFail($id);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                throw $e;
            } catch (\Exception $e) {
                Log::error("Failed to fetch school class {$id} ({$connection}): " . $e->getMessage());
                return null;
            }
        });

        if (!$schoolClass) {
            abort(404, 'Class not found or database error');
        }

        return new SchoolClassResource($schoolClass);
    }

    /**
     * POST /api/school-classes
     * إنشاء صف جديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'country_id' => 'required|string|in:1,2,3,4',
            'grade_name' => 'required|string|max:255',
            'grade_level' => 'required|integer|min:1|max:12',
        ]);

        $connection = $this->connection($validated['country_id']);

        DB::connection($connection)->beginTransaction();

        try {
            $class = new SchoolClass();
            $class->setConnection($connection);
            $class->grade_name = $validated['grade_name'];
            $class->grade_level = $validated['grade_level'];
            $class->save();

            DB::connection($connection)->commit();
            $this->clearCache($connection);

            return (new SchoolClassResource($class))
                ->additional([
                    'message' => 'School class created successfully.',
                ]);

        } catch (\Exception $e) {
            DB::connection($connection)->rollBack();

            Log::error('SchoolClass creation failed', [
                'error' => $e->getMessage()
            ]);

            return (new BaseResource(['message' => 'Error creating school class.']))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * PUT /api/school-classes/{id}
     * تحديث الصف
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'country_id' => 'required|string|in:1,2,3,4',
            'grade_name' => 'required|string|max:255',
            'grade_level' => 'required|integer|min:1|max:12',
        ]);

        $connection = $this->connection($validated['country_id']);

        $class = SchoolClass::on($connection)->findOrFail($id);

        DB::connection($connection)->beginTransaction();

        try {
            $class->grade_name = $validated['grade_name'];
            $class->grade_level = $validated['grade_level'];
            $class->save();

            DB::connection($connection)->commit();
            $this->clearCache($connection, (int) $class->id);

            return (new SchoolClassResource($class))
                ->additional([
                    'message' => 'School class updated successfully.',
                ]);

        } catch (\Exception $e) {
            DB::connection($connection)->rollBack();

            return (new BaseResource(['message' => 'Error updating school class.']))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * DELETE /api/school-classes/{id}?country_id=1
     * حذف الصف
     */
    public function destroy(Request $request, $id)
    {
        $connection = $this->connection($request);

        $class = SchoolClass::on($connection)->findOrFail($id);

        if ($class->subjects()->exists() || $class->semesters()->exists()) {
            return (new BaseResource(['message' => 'Cannot delete class with related subjects or semesters.']))
                ->response($request)
                ->setStatusCode(422);
        }

        $class->delete();
        $this->clearCache($connection, (int) $class->id);

        return new BaseResource([
            'message' => 'School class deleted successfully.'
        ]);
    }
}
