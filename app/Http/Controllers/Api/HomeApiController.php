<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\Event;
use App\Models\SchoolClass;
use App\Models\Category;
use App\Models\News;
use App\Http\Resources\BaseResource;
use App\Services\DatabaseManager;

class HomeApiController extends Controller
{
    /**
     * Helper to get database connection
     * Uses centralized DatabaseManager
     */
    private function getDatabase(Request $request): string
    {
        // Use header-based country detection first, fallback to query param
        $countryId = $request->header('X-Country-Id');
        if ($countryId) {
            return DatabaseManager::getConnection($countryId);
        }

        return $request->query('database', session('database', 'jo'));
    }

    /**
     * GET /api/home
     * الصفحة الرئيسية عبر API — تقويم + فئات + صفوف + أخبار
     * OPTIMIZED: Single query for all month events instead of 31 queries
     */
    public function index(Request $request)
    {
        $db = $this->getDatabase($request);

        // تاريخ اليوم
        $today = Carbon::now();
        $currentMonth = $today->month;
        $currentYear = $today->year;

        // Cache key for this request
        $cacheKey = "home_data_{$db}_{$currentYear}_{$currentMonth}";

        // Try to get from cache (5 minutes)
        $homeData = Cache::remember($cacheKey, 300, function () use ($db, $today, $currentMonth, $currentYear) {
            // إعداد التقويم
            $firstDay = Carbon::create($currentYear, $currentMonth, 1);
            $daysInMonth = $firstDay->daysInMonth;

            // OPTIMIZATION: Fetch ALL events for the month in ONE query
            $monthStart = $firstDay->copy()->startOfMonth();
            $monthEnd = $firstDay->copy()->endOfMonth();

            $allEvents = Event::on($db)
                ->whereBetween('event_date', [$monthStart, $monthEnd])
                ->get()
                ->groupBy(fn($e) => Carbon::parse($e->event_date)->format('Y-m-d'))
                ->map(fn($events) => $events->map(fn($e) => [
                    'id' => $e->id,
                    'title' => $e->title,
                    'description' => $e->description,
                    'date' => $e->event_date,
                ]));

            $calendar = [];

            // days of previous month
            $prevMonth = $firstDay->copy()->subMonth();
            $prevMonthDays = $prevMonth->daysInMonth;
            $firstDayOfWeek = $firstDay->dayOfWeek;

            for ($i = $firstDayOfWeek - 1; $i >= 0; $i--) {
                $date = $prevMonth->format('Y-m-') . sprintf('%02d', $prevMonthDays - $i);
                $calendar[$date] = [];
            }

            // current month days - use pre-fetched events
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $today->format('Y-m-') . sprintf('%02d', $day);
                $calendar[$date] = $allEvents->get($date, collect())->toArray();
            }

            // next month days
            $lastDayOfMonth = Carbon::create($currentYear, $currentMonth, $daysInMonth)->dayOfWeek;
            $toAdd = 6 - $lastDayOfMonth;
            $nextMonth = $firstDay->copy()->addMonth();

            for ($i = 1; $i <= $toAdd; $i++) {
                $date = $nextMonth->format('Y-m-') . sprintf('%02d', $i);
                $calendar[$date] = [];
            }

            // جلب البيانات الأخرى with eager loading
            $classes = SchoolClass::on($db)
                ->with(['subjects', 'semesters']) // Eager load relationships
                ->get();

            $categories = Category::on($db)
                ->with(['children', 'parent']) // Eager load relationships
                ->orderBy('id')
                ->get();

            $news = News::on($db)
                ->with('category')
                ->latest()
                ->limit(10) // Limit news for performance
                ->get();

            return [
                'calendar' => $calendar,
                'classes' => $classes,
                'categories' => $categories,
                'news' => $news,
            ];
        });

        return new BaseResource([
            'current_month' => $currentMonth,
            'current_year' => $currentYear,
            'database' => $db,
            'calendar' => $homeData['calendar'],
            'classes' => $homeData['classes'],
            'categories' => $homeData['categories'],
            'news' => $homeData['news'],
            'icons' => $this->icons(),
            'user' => Auth::check() ? Auth::user() : null
        ]);
    }

    /**
     * GET /api/home/calendar
     */
    public function getCalendarEvents(Request $request)
    {
        try {
            $month = $request->query('month', now()->month);
            $year = $request->query('year', now()->year);
            $db = $this->getDatabase($request);

            // Cache calendar events for 5 minutes
            $cacheKey = "calendar_events_{$db}_{$year}_{$month}";
            $events = Cache::remember($cacheKey, 300, function () use ($db, $year, $month) {
                $start = Carbon::create($year, $month, 1)->startOfMonth();
                $end = $start->copy()->endOfMonth();

                return Event::on($db)
                    ->whereBetween('event_date', [$start, $end])
                    ->get()
                    ->map(fn($e) => [
                        'id' => $e->id,
                        'title' => $e->title,
                        'description' => $e->description,
                        'date' => $e->event_date,
                    ]);
            });

            return new BaseResource([
                'events' => $events
            ]);

        } catch (\Throwable $e) {
            return (new BaseResource(['message' => 'Failed to fetch calendar events']))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * GET /api/home/event/{id}
     */
    public function getEventDetails(Request $request, $id)
    {
        try {
            $db = $this->getDatabase($request);
            $event = Event::on($db)->find($id);

            if (!$event) {
                return (new BaseResource(['message' => 'Event not found']))
                    ->response($request)
                    ->setStatusCode(404);
            }

            return new BaseResource([
                'event' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'date' => $event->event_date,
                    'formatted' => [
                        'ymd' => $event->event_date->format('Y-m-d'),
                        'arabic' => $event->event_date->translatedFormat('l j F Y')
                    ],
                    'is_today' => $event->event_date->isToday(),
                    'is_upcoming' => $event->event_date->isFuture(),
                    'days_until' => now()->diffInDays($event->event_date, false)
                ]
            ]);

        } catch (\Throwable $e) {
            return (new BaseResource(['message' => 'Failed to find event']))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    private function icons()
    {
        return [
            '1' => 'page-icon ti tabler-number-1',
            '2' => 'page-icon ti tabler-number-2',
            '3' => 'page-icon ti tabler-number-3',
            '4' => 'page-icon ti tabler-number-4',
            '5' => 'page-icon ti tabler-number-5',
            '6' => 'page-icon ti tabler-number-6',
            '7' => 'page-icon ti tabler-number-7',
            '8' => 'page-icon ti tabler-number-8',
            '9' => 'page-icon ti tabler-number-9',
            '10' => 'page-icon ti tabler-number-0',
            '11' => 'page-icon ti tabler-number-1',
            '12' => 'page-icon ti tabler-number-2',
            'default' => 'page-icon ti tabler-school'
        ];
    }
}
