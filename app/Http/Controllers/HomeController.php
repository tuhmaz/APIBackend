<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\Event;
use App\Models\SchoolClass;
use App\Models\Category;
use App\Models\News;
use App\Models\File;
use Illuminate\Support\Facades\Log;


class HomeController extends Controller
{
  public function setDatabase(Request $request)
  {
    $request->validate([
      'database' => 'required|string|in:jo,sa,eg,ps'
    ]);


    $request->session()->put('database', $request->input('database'));


    return redirect()->route('home');
  }

  /**
   * Get the current database connection
   */
  private function getDatabaseConnection()
  {
    $database = session('database', 'jo');
    Log::info('Current Database Connection:', ['database' => $database]);
    return $database;
  }

  /**
   *
   */
  public function index(Request $request)
  {
      // Disable output buffering
      if (ob_get_level()) {
          ob_end_clean();
      }

      // Get current database connection from session
      $database = session('database', config('database.default'));

      // Get the current date
      $currentDate = Carbon::now();
      $currentMonth = $currentDate->month;
      $currentYear = $currentDate->year;

      // Get the first day of the month
      $firstDay = Carbon::createFromDate($currentYear, $currentMonth, 1);
      $daysInMonth = $currentDate->daysInMonth;

      // Create calendar array
      $calendar = [];

      // Add days from previous month to align with correct day of week
      $previousMonth = Carbon::createFromDate($currentYear, $currentMonth, 1)->subMonth();
      $daysInPreviousMonth = $previousMonth->daysInMonth;
      $firstDayOfWeek = $firstDay->dayOfWeek;

      // Add previous month's days
      for ($i = $firstDayOfWeek - 1; $i >= 0; $i--) {
          $day = $daysInPreviousMonth - $i;
          $date = $previousMonth->format('Y-m-') . sprintf('%02d', $day);
          $calendar[$date] = [];
      }

      // Add current month's days with events
      for ($day = 1; $day <= $daysInMonth; $day++) {
          $date = $currentDate->format('Y-m-') . sprintf('%02d', $day);

          // Get events for this day from the current database connection
          $events = Event::on($database)
              ->whereDate('event_date', $date)
              ->get()
              ->map(function($event) {
                  return [
                      'id' => $event->id,
                      'title' => $event->title,
                      'description' => $event->description,
                      'date' => $event->event_date,
                  ];
              });

          $calendar[$date] = $events;
      }

      // Add next month's days to complete the calendar grid
      $lastDayOfWeek = Carbon::createFromDate($currentYear, $currentMonth, $daysInMonth)->dayOfWeek;
      $daysToAdd = 6 - $lastDayOfWeek;
      $nextMonth = Carbon::createFromDate($currentYear, $currentMonth, 1)->addMonth();

      for ($day = 1; $day <= $daysToAdd; $day++) {
          $date = $nextMonth->format('Y-m-') . sprintf('%02d', $day);
          $calendar[$date] = [];
      }

      // Get classes for the filter
      $classes = SchoolClass::on($database)->get();

      // Get categories from the current database
      $categories = Category::on($database)
          ->orderBy('id')
          ->get();

      // Get news with categories
      $news = News::on($database)
          ->with('category')
          ->orderBy('created_at', 'desc')
          ->get();

      // Prepare view data
      $viewData = [
          'classes' => $classes,
          'calendar' => $calendar,
          'categories' => $categories,
          'news' => $news,
          'currentMonth' => $currentMonth,
          'currentYear' => $currentYear,
          'database' => $database,
          'icons' => $this->getIcons()
      ];

      // Add user data if authenticated
      if (Auth::check()) {
          $viewData['user'] = Auth::user();
      }

      // Return response without compression
      // Return normal HTML response (do not force Content-Encoding)
      return response()->view('content.frontend.home', $viewData);

  }

  /**
   * Get icons array for different grade levels
   */
  private function getIcons()
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

  public function about()
  {
    return view('about');
  }

  public function contact()
  {
    return view('contact');
  }

  /**
   * Get calendar events for a specific month and year
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function getCalendarEvents(Request $request)
  {
    try {
        $month = $request->input('month');
        $year = $request->input('year');

        // Get current database connection from session
        $database = session('database', config('database.default'));

        // Create date objects for the first and last day of the month
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // Get events for the specified month
        $events = Event::on($database)
            ->whereBetween('event_date', [$startDate, $endDate])
            ->get()
            ->map(function($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'date' => $event->event_date,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $events
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch calendar events'
        ], 500);
    }
  }

  /**
   * Get event details by ID
   *
   * @param int $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function getEventDetails($id)
  {
    try {
        $database = session('database', config('database.default'));
        $event = Event::on($database)->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'date' => $event->event_date,
                'formatted_date' => $event->event_date->format('Y-m-d'),
                'formatted_date_arabic' => $event->event_date->translatedFormat('l j F Y'),
                'is_today' => $event->event_date->isToday(),
                'is_upcoming' => $event->event_date->isFuture(),
                'days_until' => now()->diffInDays($event->event_date, false),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Event not found'
        ], 404);
    }
  }
}
