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

class HomeApiController extends Controller
{
    /**
     * Helper to get database connection
     */
    private function getDatabase(Request $request): string
    {
        return $request->query('database', session('database', 'jo'));
    }

    /**
     * GET /api/home
     * الصفحة الرئيسية عبر API — تقويم + فئات + صفوف + أخبار
     */
    public function index(Request $request)
    {
        $db = $this->getDatabase($request);

        // تاريخ اليوم
        $today = Carbon::now();
        $currentMonth = $today->month;
        $currentYear = $today->year;

        // إعداد التقويم
        $firstDay = Carbon::create($currentYear, $currentMonth, 1);
        $daysInMonth = $firstDay->daysInMonth;

        $calendar = [];

        // days of previous month
        $prevMonth = $firstDay->copy()->subMonth();
        $prevMonthDays = $prevMonth->daysInMonth;
        $firstDayOfWeek = $firstDay->dayOfWeek;

        for ($i = $firstDayOfWeek - 1; $i >= 0; $i--) {
            $date = $prevMonth->format('Y-m-') . sprintf('%02d', $prevMonthDays - $i);
            $calendar[$date] = [];
        }

        // current month days
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $today->format('Y-m-') . sprintf('%02d', $day);

            $events = Event::on($db)
                ->whereDate('event_date', $date)
                ->get()
                ->map(fn($e) => [
                    'id' => $e->id,
                    'title' => $e->title,
                    'description' => $e->description,
                    'date' => $e->event_date,
                ]);

            $calendar[$date] = $events;
        }

        // next month days
        $lastDayOfMonth = Carbon::create($currentYear, $currentMonth, $daysInMonth)->dayOfWeek;
        $toAdd = 6 - $lastDayOfMonth;
        $nextMonth = $firstDay->copy()->addMonth();

        for ($i = 1; $i <= $toAdd; $i++) {
            $date = $nextMonth->format('Y-m-') . sprintf('%02d', $i);
            $calendar[$date] = [];
        }

        // جلب البيانات الأخرى
        $classes = SchoolClass::on($db)->get();
        $categories = Category::on($db)->orderBy('id')->get();
        $news = News::on($db)->with('category')->latest()->get();

        return new BaseResource([
            'current_month' => $currentMonth,
            'current_year' => $currentYear,
            'database' => $db,
            'calendar' => $calendar,
            'classes' => $classes,
            'categories' => $categories,
            'news' => $news,
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
