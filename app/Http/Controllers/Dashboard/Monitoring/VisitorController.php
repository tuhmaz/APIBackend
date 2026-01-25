<?php

namespace App\Http\Controllers\Dashboard\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\VisitorSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class VisitorController extends Controller
{
    public function index(Request $request)
    {
        $timeframe = $request->input('timeframe', 'today');
        $now = now();
        $activityWindowMinutes = (int) config('monitoring.visitor_active_minutes', 5);
        $includeBots = $request->boolean('include_bots', false);
        $perPageParam = $request->input('visitors_per_page', 20);
        $showAllVisitors = $perPageParam === 'all' || (string) $perPageParam === '0';
        $perPageVisitors = $showAllVisitors ? null : (int) $perPageParam;
        if ($perPageVisitors !== null) {
            $perPageVisitors = max(5, min(100, $perPageVisitors));
        }

        // Determine date range for historical views if needed by other parts of the page
        $startDate = $now->copy()->startOfDay(); // Default to today
        $endDate = $now->copy()->endOfDay();
        switch ($timeframe) {
            case 'hour':
                $startDate = $now->copy()->subHour();
                $endDate = $now->copy();
                break;
            case 'today':
                // $startDate and $endDate already set
                break;
            case 'yesterday':
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
                break;
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                break;
            case 'month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;
        }

        // Base query for all currently active visitors
        $activeVisitorsBaseQuery = VisitorSession::active($activityWindowMinutes)->with('user');

        // Get all active visitors for stats, locations, pages
        // Clone the query to avoid issues with ->get() modifying the original query instance before pagination
        $allActiveVisitorsCollection = (clone $activeVisitorsBaseQuery)
            // non-null user_id first, then by last activity
            ->orderByRaw('user_id IS NULL')
            ->orderBy('last_activity', 'desc')
            ->get();

        // Paginate for display
        $paginatedQuery = (clone $activeVisitorsBaseQuery);
        if (!$includeBots) {
            $paginatedQuery->humans();
        }

        $paginatedQuery = $paginatedQuery
            ->orderByRaw('user_id IS NULL')
            ->orderBy('last_activity', 'desc');

        if ($showAllVisitors) {
            $allVisitors = $paginatedQuery->get();
            $count = $allVisitors->count();
            $paginatedVisitors = new LengthAwarePaginator(
                $allVisitors,
                $count,
                max(1, $count),
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $paginatedVisitors = $paginatedQuery
                ->paginate($perPageVisitors)
                ->withQueryString();
        }

        // Get visitor statistics from the complete collection
        $stats = [
            'total' => $allActiveVisitorsCollection->count(),
            'members' => $allActiveVisitorsCollection->whereNotNull('user_id')->count(),
            'guests' => $allActiveVisitorsCollection->whereNull('user_id')->count(),
            'bots' => $allActiveVisitorsCollection->where('is_bot', true)->count(),
            'desktop' => $allActiveVisitorsCollection->where('is_desktop', true)->count(),
            'mobile' => $allActiveVisitorsCollection->where('is_mobile', true)->count(),
        ];

        // Get visitor locations from the complete collection
        $locations = $allActiveVisitorsCollection->groupBy('country')
            ->map(function($sessions, $country) {
                return [
                    'count' => $sessions->count(),
                    // 'sessions' => $sessions // Avoid passing large session collections to the view if not strictly needed for location list
                ];
            })
            ->sortByDesc('count');

        // Get page views from the complete collection
        $pages = $allActiveVisitorsCollection->groupBy('url')
            ->map(function($sessions, $url) {
                return $sessions->count();
            })
            ->sortDesc()
            ->take(10);

        // Top members by visits within selected timeframe (humans only) with pagination
        $perPage = (int) $request->integer('per_page', 20);
        $perPage = max(5, min(50, $perPage)); // clamp between 5 and 50
        $sort = in_array($request->input('sort'), ['visits', 'recent']) ? $request->input('sort') : 'visits';
        $q = trim((string) $request->input('q', ''));

        // If filtering by user text, prefetch matching user IDs
        $matchingUserIds = null;
        if ($q !== '') {
            $matchingUserIds = User::query()
                ->where(function($qq) use ($q) {
                    $qq->where('name', 'like', "%$q%")
                       ->orWhere('email', 'like', "%$q%");
                })
                ->pluck('id');
        }

        $topMembers = VisitorSession::query()
            ->members()
            ->humans()
            ->whereBetween('last_activity', [$startDate, $endDate])
            ->when($matchingUserIds, function($query) use ($matchingUserIds) {
                $query->whereIn('user_id', $matchingUserIds);
            })
            ->selectRaw('user_id, COUNT(*) as visits, MAX(last_activity) as last_activity')
            ->with(['user'])
            ->groupBy('user_id')
            ->when($sort === 'recent', function($q2) {
                $q2->orderByDesc('last_activity');
            }, function($q2) {
                $q2->orderByDesc('visits');
            })
            ->paginate($perPage)
            ->withQueryString();

        // Enrich with latest session metadata per user (flag/city/platform/browser)
        $memberIds = collect($topMembers->items())->pluck('user_id')->filter()->unique()->values();
        $latestSessions = VisitorSession::query()
            ->whereIn('user_id', $memberIds)
            ->orderBy('last_activity', 'desc')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        return view('content.dashboard.monitoring.visitors', [
            'visitors' => $paginatedVisitors,          // Paginated list for display
            'onlineVisitors' => $allActiveVisitorsCollection, // Full collection of active visitors (use cautiously in view due to size)
            'stats' => $stats,
            'locations' => $locations,
            'pages' => $pages,
            'includeBots' => $includeBots,
            'visitorsPerPage' => $showAllVisitors ? 'all' : $perPageVisitors,
            'topMembers' => $topMembers,
            'topMembersLatestSessions' => $latestSessions,
            'topMembersSort' => $sort,
            'topMembersQuery' => $q,
            'topMembersPerPage' => $perPage,
            'timeframe' => $timeframe,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    public function show($id)
    {
        $session = VisitorSession::with('user')->findOrFail($id);
        
        // Get user's recent activity
        $recentActivity = $session->user_id 
            ? VisitorSession::where('user_id', $session->user_id)
                ->where('id', '!=', $session->id)
                ->orderBy('last_activity', 'desc')
                ->limit(10)
                ->get()
            : collect();

        return view('content.dashboard.monitoring.visitor-details', [
            'session' => $session,
            'recentActivity' => $recentActivity
        ]);
    }

    public function destroy($id)
    {
        $session = VisitorSession::findOrFail($id);
        $session->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'تم حذف جلسة الزائر بنجاح.'
        ]);
    }


    public function prune(Request $request)
    {
        $minutes = (int) $request->input('minutes', config('monitoring.visitor_prune_minutes', 30));
        $minutes = max(1, $minutes);
        $onlyBots = $request->boolean('only_bots', false);

        $query = VisitorSession::query()->where('last_activity', '<', now()->subMinutes($minutes));
        if ($onlyBots) {
            $query->where('is_bot', true);
        }

        $deleted = $query->delete();

        return redirect()
            ->back()
            ->with('success', __('Pruned :count sessions.', ['count' => $deleted]));
    }
}
