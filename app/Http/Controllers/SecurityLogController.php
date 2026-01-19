<?php

namespace App\Http\Controllers;

use App\Models\SecurityLog;
use App\Services\SecurityLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class SecurityLogController extends Controller
{
    protected array $eventTypes = [
        'login_failed',
        'suspicious_activity',
        'blocked_access',
        'unauthorized_access',
        'password_reset',
        'account_locked',
        'permission_change',
    ];

    protected array $severityLevels = [
        'info',
        'warning',
        'danger',
        'critical',
    ];

    public function index(SecurityLogService $service)
    {
        $stats = $service->getQuickStats();

        $recentLogs = SecurityLog::with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('content.dashboard.security.index', [
            'stats' => $stats,
            'recentLogs' => $recentLogs,
        ]);
    }

    public function logs(Request $request)
    {
        $baseQuery = SecurityLog::query()
            ->with(['user', 'resolvedByUser'])
            ->when($request->event_type, fn ($q) => $q->where('event_type', $request->event_type))
            ->when($request->severity, fn ($q) => $q->where('severity', $request->severity))
            ->when($request->ip, fn ($q) => $q->where('ip_address', 'like', '%' . $request->ip . '%'))
            ->when($request->is_resolved !== null && $request->is_resolved !== '', function ($q) use ($request) {
                $q->where('is_resolved', $request->is_resolved === 'true');
            })
            ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('created_at', '<=', $request->date_to));

        $hasLogs = SecurityLog::query()->exists();
        $filteredCount = (clone $baseQuery)->count();

        $logs = $baseQuery
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('content.dashboard.security.logs', [
            'logs' => $logs,
            'eventTypes' => $this->eventTypes,
            'severityLevels' => $this->severityLevels,
            'hasLogs' => $hasLogs,
            'filteredCount' => $filteredCount,
        ]);
    }

    public function analytics(Request $request)
    {
        $range = $request->range ?? 'week';

        $startDate = match ($range) {
            'today' => now()->startOfDay(),
            'week' => now()->subDays(7)->startOfDay(),
            'month' => now()->subDays(30)->startOfDay(),
            'custom' => Carbon::parse($request->start_date)->startOfDay(),
            default => now()->subDays(7)->startOfDay(),
        };

        $endDate = $range === 'custom'
            ? Carbon::parse($request->end_date)->endOfDay()
            : now();

        $durationDays = max(1, $startDate->diffInDays($endDate) + 1);
        $previousStart = (clone $startDate)->subDays($durationDays);
        $previousEnd = (clone $startDate)->subSecond();

        $currentStats = $this->scoreStats($startDate, $endDate);
        $previousStats = $this->scoreStats($previousStart, $previousEnd);

        $securityScore = $this->computeScore(
            $currentStats['avg_risk'],
            $currentStats['total_events'],
            $currentStats['resolved_events'],
            $currentStats['avg_resolution_time']
        );

        $previousScore = $this->computeScore(
            $previousStats['avg_risk'],
            $previousStats['total_events'],
            $previousStats['resolved_events'],
            $previousStats['avg_resolution_time']
        );

        $scoreChange = $securityScore - $previousScore;

        $eventDistribution = SecurityLog::whereBetween('created_at', [$startDate, $endDate])
            ->select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->get();

        $eventsTimeline = SecurityLog::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                $row->date = Carbon::parse($row->date)->format('Y-m-d');
                return $row;
            });

        $topAttackedRoutes = SecurityLog::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('route')
            ->where('route', '!=', '')
            ->select('route', DB::raw('COUNT(*) as count'), DB::raw('MAX(created_at) as last_attack'))
            ->groupBy('route')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $row->last_attack = Carbon::parse($row->last_attack);
                return $row;
            });

        $geoDistribution = SecurityLog::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->selectRaw('
                country_code,
                MAX(city) as city,
                COUNT(*) as events_count,
                COUNT(DISTINCT CASE WHEN event_type = "blocked_access" AND is_resolved = 0 THEN ip_address END) as blocked_ips_count
            ')
            ->groupBy('country_code')
            ->orderByDesc('events_count')
            ->limit(10)
            ->get();

        $avgResolutionMinutes = SecurityLog::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_minutes')
            ->value('avg_minutes');

        $avgResponseTime = $avgResolutionMinutes !== null
            ? round((float) $avgResolutionMinutes, 1) . ' min'
            : 'N/A';

        $totalEvents = (int) $currentStats['total_events'];
        $resolvedEvents = (int) $currentStats['resolved_events'];
        $resolutionRate = $totalEvents > 0 ? round(($resolvedEvents / $totalEvents) * 100, 1) : 0;

        $pendingIssues = SecurityLog::whereBetween('created_at', [$startDate, $endDate])
            ->where('is_resolved', false)
            ->count();

        $responseTimeTrend = SecurityLog::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('resolved_at')
            ->selectRaw('DATE(resolved_at) as date, AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_time')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                $row->date = Carbon::parse($row->date)->format('Y-m-d');
                $row->avg_time = round((float) $row->avg_time, 1);
                return $row;
            });

        return view('content.dashboard.security.analytics', [
            'securityScore' => $securityScore,
            'scoreChange' => $scoreChange,
            'eventDistribution' => $eventDistribution,
            'eventsTimeline' => $eventsTimeline,
            'topAttackedRoutes' => $topAttackedRoutes,
            'geoDistribution' => $geoDistribution,
            'avgResponseTime' => $avgResponseTime,
            'resolutionRate' => $resolutionRate,
            'pendingIssues' => $pendingIssues,
            'responseTimeTrend' => $responseTimeTrend,
        ]);
    }

    public function show(SecurityLog $log, Request $request)
    {
        $log->loadMissing(['user', 'resolvedByUser']);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $log,
            ]);
        }

        return redirect()->route('dashboard.security.logs', ['ip' => $log->ip_address]);
    }

    public function resolve(SecurityLog $log, Request $request)
    {
        $validated = $request->validate([
            'notes' => 'required|string',
        ]);

        $log->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
            'resolution_notes' => $validated['notes'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Log resolved successfully'),
            ]);
        }

        return back()->with('success', __('Log resolved successfully'));
    }

    public function destroy(SecurityLog $log, Request $request)
    {
        $log->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Log deleted'),
            ]);
        }

        return back()->with('success', __('Log deleted'));
    }

    public function destroyAll(Request $request)
    {
        SecurityLog::truncate();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('All logs deleted'),
            ]);
        }

        return back()->with('success', __('All logs deleted'));
    }

    public function export(Request $request)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="security-logs.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $logs = SecurityLog::with('user')
            ->when($request->event_type, fn ($q) => $q->where('event_type', $request->event_type))
            ->when($request->severity, fn ($q) => $q->where('severity', $request->severity))
            ->when($request->is_resolved !== null && $request->is_resolved !== '', function ($q) use ($request) {
                $q->where('is_resolved', $request->is_resolved === 'true');
            })
            ->latest()
            ->get();

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID',
                'Time',
                'IP Address',
                'Event Type',
                'Description',
                'User',
                'Route',
                'Severity',
                'Status',
                'Risk Score',
                'Country',
                'City',
                'Attack Type',
                'Occurrences',
            ]);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at,
                    $log->ip_address,
                    $log->event_type,
                    $log->description,
                    $log->user ? $log->user->name : 'System',
                    $log->route,
                    $log->severity,
                    $log->is_resolved ? 'Resolved' : 'Pending',
                    $log->risk_score,
                    $log->country_code,
                    $log->city,
                    $log->attack_type,
                    $log->occurrence_count,
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function blockedIps()
    {
        $blockedLogsQuery = SecurityLog::query()
            ->select([
                'ip_address',
                DB::raw('MAX(country_code) as country_code'),
                DB::raw('MAX(city) as city'),
                DB::raw('MAX(attack_type) as attack_type'),
                DB::raw('MAX(risk_score) as max_risk_score'),
                DB::raw('COUNT(*) as attempts_count'),
                DB::raw('MAX(created_at) as last_attempt'),
            ])
            ->where('event_type', 'blocked_access')
            ->where('is_resolved', false)
            ->groupBy('ip_address')
            ->orderByDesc('last_attempt');

        $blockedLogs = $blockedLogsQuery->paginate(25);

        $totalBlocked = SecurityLog::where('event_type', 'blocked_access')
            ->where('is_resolved', false)
            ->distinct('ip_address')
            ->count('ip_address');

        $highRiskCount = SecurityLog::where('event_type', 'blocked_access')
            ->where('is_resolved', false)
            ->select('ip_address')
            ->groupBy('ip_address')
            ->havingRaw('MAX(risk_score) >= ?', [75])
            ->get()
            ->count();

        $recentlyBlocked = SecurityLog::where('event_type', 'blocked_access')
            ->where('is_resolved', false)
            ->where('created_at', '>=', now()->subDay())
            ->distinct('ip_address')
            ->count('ip_address');

        $avgRiskScore = round((float) SecurityLog::where('event_type', 'blocked_access')
            ->where('is_resolved', false)
            ->avg('risk_score'), 1);

        return view('content.dashboard.security.blocked-ips', [
            'blockedLogs' => $blockedLogs,
            'totalBlocked' => $totalBlocked,
            'highRiskCount' => $highRiskCount,
            'recentlyBlocked' => $recentlyBlocked,
            'avgRiskScore' => $avgRiskScore,
        ]);
    }

    public function trustedIps()
    {
        $trustedLogs = SecurityLog::with('user')
            ->where('event_type', 'trusted_access')
            ->where('is_resolved', true)
            ->latest()
            ->paginate(25);

        $totalTrusted = SecurityLog::where('event_type', 'trusted_access')
            ->where('is_resolved', true)
            ->distinct('ip_address')
            ->count('ip_address');

        return view('content.dashboard.security.trusted-ips', [
            'trustedLogs' => $trustedLogs,
            'totalTrusted' => $totalTrusted,
        ]);
    }

    public function blockIp(Request $request)
    {
        $validated = $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'required|string',
        ]);

        SecurityLog::create([
            'ip_address' => $validated['ip_address'],
            'event_type' => 'blocked_access',
            'description' => $validated['reason'],
            'user_id' => Auth::id(),
            'severity' => 'warning',
            'risk_score' => 75,
            'is_resolved' => false,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('IP blocked successfully'),
            ]);
        }

        return back()->with('success', __('IP blocked successfully'));
    }

    public function trustIp(Request $request)
    {
        $validated = $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'required|string',
        ]);

        SecurityLog::create([
            'ip_address' => $validated['ip_address'],
            'event_type' => 'trusted_access',
            'description' => $validated['reason'],
            'user_id' => Auth::id(),
            'severity' => 'info',
            'risk_score' => 0,
            'is_resolved' => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('IP trusted successfully'),
            ]);
        }

        return back()->with('success', __('IP trusted successfully'));
    }

    public function unblockIp(Request $request)
    {
        $validated = $request->validate([
            'ip_address' => 'required|ip',
        ]);

        SecurityLog::where('ip_address', $validated['ip_address'])
            ->where('event_type', 'blocked_access')
            ->where('is_resolved', false)
            ->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolved_by' => Auth::id(),
                'resolution_notes' => 'Manually unblocked',
            ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('IP unblocked successfully'),
            ]);
        }

        return back()->with('success', __('IP unblocked successfully'));
    }

    public function untrustIp(Request $request)
    {
        $validated = $request->validate([
            'ip_address' => 'required|ip',
        ]);

        SecurityLog::where('ip_address', $validated['ip_address'])
            ->where('event_type', 'trusted_access')
            ->where('is_resolved', true)
            ->update([
                'is_resolved' => false,
                'resolved_at' => null,
                'resolved_by' => null,
            ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => __('IP removed from trusted list'),
            ]);
        }

        return back()->with('success', __('IP removed from trusted list'));
    }

    public function ipDetails(string $ip, Request $request)
    {
        $logs = SecurityLog::where('ip_address', $ip)
            ->with('user')
            ->latest()
            ->get();

        $locations = $logs
            ->filter(fn ($log) => !empty($log->country_code))
            ->groupBy('country_code')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'cities' => $group->pluck('city')->filter()->unique()->values(),
                ];
            });

        $stats = [
            'first_seen' => $logs->last()?->created_at,
            'last_seen' => $logs->first()?->created_at,
            'total_events' => $logs->count(),
            'event_types' => $logs->groupBy('event_type')->map(fn ($g) => $g->count()),
            'risk_scores' => [
                'current' => (int) ($logs->first()?->risk_score ?? 0),
                'average' => (float) ($logs->avg('risk_score') ?? 0),
                'max' => (int) ($logs->max('risk_score') ?? 0),
            ],
            'locations' => $locations,
            'user_agents' => $logs->pluck('user_agent')->filter()->unique()->values(),
            'routes' => $logs->pluck('route')->filter()->unique()->values(),
            'users' => $logs->pluck('user.name')->filter()->unique()->values(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'ip' => $ip,
                    'stats' => $stats,
                    'logs' => $logs,
                ],
            ]);
        }

        return view('content.dashboard.security.partials.ip-details', [
            'ip' => $ip,
            'stats' => $stats,
            'logs' => $logs,
        ]);
    }

    protected function computeScore(float $avgRisk, int $total, int $resolved, float $avgTime): int
    {
        if ($total === 0) {
            return 100;
        }

        $riskScore = max(0, 100 - ($avgRisk * 10));
        $resolutionRate = ($resolved / $total) * 100;
        $responseScore = max(0, 100 - (($avgTime / 120) * 100));

        return (int) round(
            $riskScore * 0.3 +
            $resolutionRate * 0.4 +
            $responseScore * 0.3
        );
    }

    protected function scoreStats(Carbon $startDate, Carbon $endDate): array
    {
        $row = SecurityLog::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                AVG(risk_score) as avg_risk,
                COUNT(*) as total_events,
                COUNT(CASE WHEN is_resolved = 1 THEN 1 END) as resolved_events,
                AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_resolution_time
            ')
            ->first();

        return [
            'avg_risk' => (float) ($row->avg_risk ?? 0),
            'total_events' => (int) ($row->total_events ?? 0),
            'resolved_events' => (int) ($row->resolved_events ?? 0),
            'avg_resolution_time' => (float) ($row->avg_resolution_time ?? 0),
        ];
    }
}
