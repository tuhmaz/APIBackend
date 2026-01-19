<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecurityLog;
use App\Services\SecurityAlertService;
use App\Services\SecurityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Http\Resources\Api\SecurityLogResource;
use App\Http\Resources\BaseResource;

class SecurityMonitorApiController extends Controller
{
    protected SecurityAlertService $securityAlertService;
    protected SecurityLogService $securityLogService;

    public function __construct(
        SecurityAlertService $securityAlertService,
        SecurityLogService $securityLogService
    ) {
        $this->securityAlertService = $securityAlertService;
        $this->securityLogService = $securityLogService;
    }

    // -----------------------------------------------------------
    // 1) Dashboard overview (alerts + quick stats + charts)
    // -----------------------------------------------------------
    public function dashboard()
    {
        $data = Cache::remember('security_monitor_dashboard', 30, function () {
            return [
                'alerts_summary' => $this->securityAlertService->getSecurityAlertsSummary(),
                'stats' => $this->securityLogService->getQuickStats(),
                'recent_events' => SecurityLogResource::collection(
                    SecurityLog::with('user')
                        ->whereIn('severity', [
                            SecurityLog::SEVERITY_LEVELS['WARNING'],
                            SecurityLog::SEVERITY_LEVELS['DANGER'],
                            SecurityLog::SEVERITY_LEVELS['CRITICAL'],
                        ])
                        ->latest()
                        ->limit(10)
                        ->get()
                )->resolve(),
                'event_type_distribution' => $this->getEventTypeDistribution(),
                'severity_distribution' => $this->getSeverityDistribution(),
                'timeline_trends' => $this->getTimelineTrends(),
                'top_routes' => $this->getTopAttackedRoutes(),
            ];
        });

        return new BaseResource($data);
    }

    // -----------------------------------------------------------
    // 2) Alerts list
    // -----------------------------------------------------------
    public function alerts(Request $request)
    {
        $query = SecurityLog::with('user')
            ->where(function ($q) {
                $q->where('severity', SecurityLog::SEVERITY_LEVELS['CRITICAL'])
                  ->orWhere('severity', SecurityLog::SEVERITY_LEVELS['DANGER'])
                  ->orWhereIn('event_type', [
                        'suspicious_activity',
                        'blocked_access',
                        'sql_injection_attempt',
                        'xss_attempt',
                        'brute_force_attempt',
                        'file_upload_violation'
                  ]);
            })
            ->when($request->event_type, fn($q) => $q->where('event_type', $request->event_type))
            ->when($request->severity, fn($q) => $q->where('severity', $request->severity))
            ->when($request->is_resolved !== null, fn($q) => $q->where('is_resolved', $request->is_resolved))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to));

        return SecurityLogResource::collection($query->latest()->paginate(20))
            ->additional(['success' => true]);
    }

    // -----------------------------------------------------------
    // 3) Alert details
    // -----------------------------------------------------------
    public function showAlert($id)
    {
        $log = SecurityLog::with('user')->findOrFail($id);

        return (new SecurityLogResource($log))
            ->additional([
                'similar_logs' => SecurityLogResource::collection(
                    SecurityLog::where('event_type', $log->event_type)
                        ->where('ip_address', $log->ip_address)
                        ->where('id', '!=', $log->id)
                        ->latest()->limit(10)->get()
                ),
                'ip_logs' => SecurityLogResource::collection(
                    SecurityLog::where('ip_address', $log->ip_address)
                        ->where('id', '!=', $log->id)
                        ->latest()->limit(10)->get()
                ),
            ]);
    }

    // -----------------------------------------------------------
    // 4) Update alert resolved status
    // -----------------------------------------------------------
    public function updateAlert($id, Request $request)
    {
        $request->validate([
            'is_resolved' => 'required|boolean',
            'resolution_notes' => 'nullable|string|max:1000'
        ]);

        $log = SecurityLog::findOrFail($id);

        $log->is_resolved = $request->is_resolved;

        if ($request->is_resolved) {
            $log->resolved_at = now();
            $log->resolved_by = Auth::id();
            $log->resolution_notes = $request->resolution_notes;
        } else {
            $log->resolved_at = null;
            $log->resolved_by = null;
            $log->resolution_notes = null;
        }

        $log->save();

        return new BaseResource([
            'message' => 'Alert updated successfully'
        ]);
    }

    // -----------------------------------------------------------
    // 5) Timeline Trends (Charts)
    // -----------------------------------------------------------
    protected function getTimelineTrends(): array
    {
        $from = now()->subDays(30)->startOfDay();
        $to = now()->endOfDay();

        $crit = $this->getDailyCounts(SecurityLog::SEVERITY_LEVELS['CRITICAL'], $from, $to);
        $warn = $this->getDailyCounts(SecurityLog::SEVERITY_LEVELS['WARNING'], $from, $to);

        $dates = [];
        $critical = [];
        $warning = [];

        for ($date = $from->copy(); $date <= $to; $date->addDay()) {
            $d = $date->format('Y-m-d');
            $dates[] = $d;
            $critical[] = $crit[$d] ?? 0;
            $warning[] = $warn[$d] ?? 0;
        }

        return [
            'dates' => $dates,
            'critical' => $critical,
            'warning' => $warning
        ];
    }

    private function getDailyCounts(string $severity, $from, $to)
    {
        return SecurityLog::where('severity', $severity)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');
    }

    // -----------------------------------------------------------
    // 6) Security Scan execution
    // -----------------------------------------------------------
    public function runScan()
    {
        try {
            Artisan::call('security:scan');
            $output = Artisan::output();

            return new BaseResource([
                'message' => 'Security scan executed successfully',
                'output' => $output,
                'security_score' => $this->securityLogService->getQuickStats()['security_score']
            ]);

        } catch (\Exception $e) {
            return (new BaseResource([
                'message' => 'Security scan failed',
                'error' => $e->getMessage()
            ]))->response(request())->setStatusCode(500);
        }
    }

    // -----------------------------------------------------------
    // 7) Export report (JSON)
    // -----------------------------------------------------------
    public function exportReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'type'       => 'required|in:alerts,logs,summary',
        ]);

        $from = Carbon::parse($request->start_date)->startOfDay();
        $to = Carbon::parse($request->end_date)->endOfDay();

        if ($request->type === 'alerts') {
            return new BaseResource([
                'alerts' => SecurityLogResource::collection(
                    SecurityLog::where('severity', '>=', 3)
                        ->whereBetween('created_at', [$from, $to])
                        ->get()
                )
            ]);
        }

        if ($request->type === 'logs') {
            return new BaseResource([
                'logs' => SecurityLogResource::collection(
                    SecurityLog::whereBetween('created_at', [$from, $to])->get()
                )
            ]);
        }

        return new BaseResource([
            'summary' => [
                'total' => SecurityLog::whereBetween('created_at', [$from, $to])->count(),
                'critical' => SecurityLog::where('severity', 4)->count(),
                'distribution' => SecurityLog::select('event_type', DB::raw('COUNT(*) as c'))
                    ->whereBetween('created_at', [$from, $to])
                    ->groupBy('event_type')
                    ->get()
            ]
        ]);
    }

    // -----------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------
    private function getEventTypeDistribution()
    {
        return SecurityLog::select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->get();
    }

    private function getSeverityDistribution()
    {
        return SecurityLog::select('severity', DB::raw('COUNT(*) as count'))
            ->groupBy('severity')
            ->orderByDesc('count')
            ->get();
    }

    private function getTopAttackedRoutes()
    {
        return SecurityLog::whereNotNull('route')
            ->select('route', DB::raw('COUNT(*) as count'))
            ->groupBy('route')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
    }
}
