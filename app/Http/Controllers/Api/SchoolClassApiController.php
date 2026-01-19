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
     * تحديد الاتصال المناسب
     */
    private function connection(string $countryId): string
    {
        return $this->countries[$countryId] ?? 'jo';
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
     */
    public function index(Request $request)
    {
        $countryId = $request->query('country_id', '1');
        $connection = $this->connection($countryId);

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
     */
    public function show(Request $request, $id)
    {
        $countryId = $request->query('country_id', '1');
        $connection = $this->connection($countryId);

        // Use cache for individual school class
        $cacheKey = "school_class_{$connection}_{$id}";
        $schoolClass = $this->remember($cacheKey, 600, function () use ($connection, $id) {
            return SchoolClass::on($connection)
                ->with(['subjects' => function ($query) {
                    $query->withCount(['articles', 'files']);
                }, 'semesters'])
                ->findOrFail($id);
        });

        $schoolClass->loadMissing([
            'subjects' => function ($query) {
                $query->withCount(['articles', 'files']);
            },
            'semesters',
        ]);

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
        $countryId = $request->query('country_id', '1');
        $connection = $this->connection($countryId);

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
