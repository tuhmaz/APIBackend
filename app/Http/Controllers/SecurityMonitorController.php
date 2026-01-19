<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SecurityLog;
use App\Services\SecurityAlertService;
use App\Services\SecurityLogService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class SecurityMonitorController extends Controller
{
    /**
     * خدمة تنبيهات الأمان
     *
     * @var SecurityAlertService
     */
    protected $securityAlertService;

    /**
     * خدمة سجلات الأمان
     *
     * @var SecurityLogService
     */
    protected $securityLogService;

    /**
     * إنشاء مثيل جديد لوحدة التحكم.
     *
     * @param SecurityAlertService $securityAlertService
     * @param SecurityLogService $securityLogService
     * @return void
     */
    public function __construct(SecurityAlertService $securityAlertService, SecurityLogService $securityLogService)
    {
        $this->securityAlertService = $securityAlertService;
        $this->securityLogService = $securityLogService;
    }

    /**
     * عرض لوحة مراقبة الأمان.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        // الحصول على ملخص التنبيهات الأمنية
        $alertsSummary = $this->securityAlertService->getSecurityAlertsSummary();
        
        // الحصول على الإحصائيات السريعة
        $stats = $this->securityLogService->getQuickStats();
        
        // الحصول على أحدث الأحداث الأمنية (نستخدم الـ accessor مباشرة في العرض)
        $recentEvents = SecurityLog::with('user')
            ->latest()
            ->limit(10)
            ->get();
        
        // الحصول على توزيع أنواع الأحداث
        $eventTypeDistribution = SecurityLog::select('event_type', DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('event_type')
            ->orderBy('count', 'desc')
            ->get();
        
        // الحصول على توزيع مستويات الخطورة
        $severityDistribution = SecurityLog::select('severity', DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('severity')
            ->orderBy('count', 'desc')
            ->get();
        
        // الحصول على الاتجاهات الزمنية للأحداث الأمنية
        $timelineTrends = $this->getTimelineTrends();

        // تحضير البيانات لرسم الرسوم البيانية في JavaScript
        $chartData = [
            'securityScore' => $stats['security_score'] ?? 85,
            'timelineTrends' => $timelineTrends,
            'eventTypeDistribution' => $eventTypeDistribution,
        ];

        // الحصول على أكثر المسارات تعرضاً للهجمات
        $routes = SecurityLog::select('route', DB::raw('count(*) as count'), 'severity')
            ->whereNotNull('route')
            ->groupBy('route', 'severity')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
        
        return view('content.dashboard.security.monitor', compact(
            'alertsSummary',
            'stats',
            'recentEvents',
            'eventTypeDistribution',
            'severityDistribution',
            'timelineTrends',
            'routes'
        ));
    }

    /**
     * عرض قائمة التنبيهات الأمنية.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
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
                        'file_upload_violation',
                    ]);
            })
            ->when($request->filled('event_type'), function ($q) use ($request) {
                return $q->where('event_type', $request->event_type);
            })
            ->when($request->filled('severity'), function ($q) use ($request) {
                return $q->where('severity', $request->severity);
            })
            ->when($request->filled('is_resolved'), function ($q) use ($request) {
                return $q->where('is_resolved', $request->is_resolved === 'true');
            })
            ->when($request->filled('date_from'), function ($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            });
        
        $alerts = $query->latest()->paginate(15);
        
        return view('content.dashboard.security.alerts', [
            'alerts' => $alerts,
            'eventTypes' => SecurityLog::EVENT_TYPES,
            'severityLevels' => SecurityLog::SEVERITY_LEVELS
        ]);
    }

    /**
     * عرض تفاصيل تنبيه أمني.
     *
     * @param SecurityLog $log
     * @return \Illuminate\Http\Response
     */
    public function showAlert(SecurityLog $log)
    {
        // الحصول على سجلات مشابهة
        $similarLogs = SecurityLog::where('event_type', $log->event_type)
            ->where('ip_address', $log->ip_address)
            ->where('id', '!=', $log->id)
            ->latest()
            ->limit(5)
            ->get();
        
        // الحصول على سجلات من نفس عنوان IP
        $ipLogs = SecurityLog::where('ip_address', $log->ip_address)
            ->where('id', '!=', $log->id)
            ->latest()
            ->limit(10)
            ->get();
        
        return view('content.dashboard.security.alert-details', compact('log', 'similarLogs', 'ipLogs'));
    }

    /**
     * تحديث حالة تنبيه أمني.
     *
     * @param SecurityLog $log
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function updateAlert(SecurityLog $log, Request $request)
    {
        $request->validate([
            'is_resolved' => 'required|boolean',
            'resolution_notes' => 'nullable|string|max:1000',
        ]);
        
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
        
        return response()->json([
            'message' => __('تم تحديث حالة التنبيه بنجاح'),
            'status' => 'success'
        ]);
    }

    /**
     * الحصول على اتجاهات الأحداث الأمنية على مدار الوقت.
     *
     * @return array
     */
    protected function getTimelineTrends()
    {
        $startDate = now()->subDays(30)->startOfDay();
        $endDate = now()->endOfDay();
        
        $criticalEvents = SecurityLog::where('severity', SecurityLog::SEVERITY_LEVELS['CRITICAL'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->map(function ($item) {
                return $item->count;
            });
        
        $warningEvents = SecurityLog::where('severity', SecurityLog::SEVERITY_LEVELS['WARNING'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->map(function ($item) {
                return $item->count;
            });
        
        $dates = [];
        $criticalCounts = [];
        $warningCounts = [];
        
        for ($date = clone $startDate; $date <= $endDate; $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            $dates[] = $dateString;
            $criticalCounts[] = $criticalEvents[$dateString] ?? 0;
            $warningCounts[] = $warningEvents[$dateString] ?? 0;
        }
        
        return [
            'dates' => $dates,
            'critical' => $criticalCounts,
            'warning' => $warningCounts,
        ];
    }

    /**
     * تصدير تقرير الأمان.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    /**
     * تشغيل فحص أمني شامل.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function runScan()
    {
        try {
            // تشغيل الأمر عبر Artisan
            Artisan::call('security:scan');

            // الحصول على نتيجة الفحص
            $output = Artisan::output();

            // تحديث درجة الأمان في الإحصائيات
            $stats = $this->securityLogService->getQuickStats();
            
            return response()->json([
                'status' => 'success',
                'message' => 'تم تشغيل الفحص الأمني بنجاح',
                'output' => $output,
                'security_score' => $stats['security_score'] ?? 85
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء تشغيل الفحص الأمني',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'report_type' => 'required|in:alerts,logs,summary',
        ]);
        
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        
        $filename = 'security_report_' . $request->report_type . '_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];
        
        $callback = function () use ($request, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            
            if ($request->report_type === 'alerts') {
                // تصدير التنبيهات الأمنية
                fputcsv($file, [
                    'ID', 'Event Type', 'Description', 'IP Address', 'User ID', 'Severity',
                    'Risk Score', 'Is Resolved', 'Created At', 'Resolved At', 'Resolution Notes'
                ]);
                
                SecurityLog::where(function ($q) {
                    $q->where('severity', SecurityLog::SEVERITY_LEVELS['CRITICAL'])
                        ->orWhere('severity', SecurityLog::SEVERITY_LEVELS['DANGER']);
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->chunk(100, function ($logs) use ($file) {
                    foreach ($logs as $log) {
                        fputcsv($file, [
                            $log->id,
                            $log->event_type,
                            $log->description,
                            $log->ip_address,
                            $log->user_id,
                            $log->severity,
                            $log->risk_score,
                            $log->is_resolved ? 'Yes' : 'No',
                            $log->created_at,
                            $log->resolved_at,
                            $log->resolution_notes,
                        ]);
                    }
                });
            } elseif ($request->report_type === 'logs') {
                // تصدير جميع سجلات الأمان
                fputcsv($file, [
                    'ID', 'Event Type', 'Description', 'IP Address', 'User ID', 'Route',
                    'Severity', 'Created At'
                ]);
                
                SecurityLog::whereBetween('created_at', [$startDate, $endDate])
                ->chunk(100, function ($logs) use ($file) {
                    foreach ($logs as $log) {
                        fputcsv($file, [
                            $log->id,
                            $log->event_type,
                            $log->description,
                            $log->ip_address,
                            $log->user_id,
                            $log->route,
                            $log->severity,
                            $log->created_at,
                        ]);
                    }
                });
            } else {
                // تصدير ملخص الأمان
                fputcsv($file, ['Security Summary Report']);
                fputcsv($file, ['Period', $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d')]);
                fputcsv($file, ['Generated At', now()->format('Y-m-d H:i:s')]);
                fputcsv($file, []);
                
                // إحصائيات عامة
                fputcsv($file, ['General Statistics']);
                
                $totalEvents = SecurityLog::whereBetween('created_at', [$startDate, $endDate])->count();
                $criticalEvents = SecurityLog::where('severity', SecurityLog::SEVERITY_LEVELS['CRITICAL'])
                    ->whereBetween('created_at', [$startDate, $endDate])->count();
                $unresolvedIssues = SecurityLog::where('is_resolved', false)
                    ->whereBetween('created_at', [$startDate, $endDate])->count();
                
                fputcsv($file, ['Total Events', $totalEvents]);
                fputcsv($file, ['Critical Events', $criticalEvents]);
                fputcsv($file, ['Unresolved Issues', $unresolvedIssues]);
                fputcsv($file, []);
                
                // توزيع أنواع الأحداث
                fputcsv($file, ['Event Type Distribution']);
                
                $eventTypes = SecurityLog::select('event_type', DB::raw('count(*) as count'))
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('event_type')
                    ->orderBy('count', 'desc')
                    ->get();
                
                foreach ($eventTypes as $eventType) {
                    fputcsv($file, [$eventType->event_type, $eventType->count]);
                }
                
                fputcsv($file, []);
                
                // توزيع مستويات الخطورة
                fputcsv($file, ['Severity Distribution']);
                
                $severities = SecurityLog::select('severity', DB::raw('count(*) as count'))
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('severity')
                    ->orderBy('count', 'desc')
                    ->get();
                
                foreach ($severities as $severity) {
                    fputcsv($file, [$severity->severity, $severity->count]);
                }
                
                fputcsv($file, []);
                
                // أكثر عناوين IP نشاطًا
                fputcsv($file, ['Top Active IP Addresses']);
                
                $topIPs = SecurityLog::select('ip_address', DB::raw('count(*) as count'))
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('ip_address')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get();
                
                foreach ($topIPs as $ip) {
                    fputcsv($file, [$ip->ip_address, $ip->count]);
                }
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
