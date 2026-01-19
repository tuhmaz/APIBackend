<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use App\Http\Resources\BaseResource;
use App\Http\Requests\Activity\ActivityIndexRequest;

class ActivityApiController extends Controller
{
    /**
     * GET /api/activities
     * قائمة الأنشطة مع الفلترة والترقيم (pagination)
     */
    public function index(ActivityIndexRequest $request)
    {
        $validated = $request->validated();

        $page = $validated['page'] ?? 1;
        $perPage = 20;

        $filters = [
            'q'         => $validated['q'] ?? '',
            'type'      => $validated['type'] ?? '',
            'date_from' => $validated['date_from'] ?? null,
            'date_to'   => $validated['date_to'] ?? null,
        ];

        $activities = $this->queryActivities($page, $perPage, $filters);

        return new BaseResource([
            'data'      => ActivityResource::collection($activities),
            'pagination' => [
                'page'      => $page,
                'per_page'  => $perPage,
                'count'     => $activities->count(),
                'has_more'  => $activities->count() === $perPage,
            ]
        ]);
    }

    /**
     * نفس منطق النظام القديم loadMore ولكن بشكل API
     */
    public function loadMore(Request $request)
    {
        return $this->index($request);
    }

    /**
     * الاستعلام العام (فلترة + ترتيب + pagination)
     */
    private function queryActivities($page, $perPage, array $filters)
    {
        $offset = ($page - 1) * $perPage;

        $query = Activity::with('causer');

        // تاريخ البداية والنهاية
        $dateFrom = $filters['date_from']
            ? Carbon::parse($filters['date_from'])->startOfDay()
            : Carbon::now()->subHours(24);

        $dateTo = $filters['date_to']
            ? Carbon::parse($filters['date_to'])->endOfDay()
            : Carbon::now();

        $query->whereBetween('created_at', [$dateFrom, $dateTo]);

        // فلترة النوع
        if (!empty($filters['type'])) {
            $type = strtolower($filters['type']);

            $query->where(function ($q) use ($type) {
                $q->whereRaw('LOWER(subject_type) LIKE ?', ["%$type%"])
                  ->orWhereNull('subject_type');
            });
        }

        // فلترة البحث في النص والخصائص
        if (!empty($filters['q'])) {
            $search = $filters['q'];

            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%$search%")
                  ->orWhere('properties', 'like', "%$search%");
            });
        }

        // جلب السجلات
        return $query
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($perPage)
            ->get();
    }

    /**
     * DELETE /api/activities/clean
     * حذف كل الأنشطة الأقدم من 24 ساعة
     */
    public function cleanOldActivities()
    {
        $cutoff = Carbon::now()->subHours(24);

        $deleted = Activity::where('created_at', '<', $cutoff)->delete();

        return new BaseResource([
            'deleted' => $deleted,
            'message' => "تم حذف {$deleted} من السجلات القديمة."
        ]);
}
}
