<?php

namespace App\Http\Controllers\Dashboard\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\SecurityLog;
use App\Models\BannedIp;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SecurityController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            // keep query param name 'level' for backward compat; map to severity in queries
            'level' => $request->input('level'),
            'search' => $request->input('search'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $query = SecurityLog::with('user')
            ->when($filters['level'], function($q) use ($filters) {
                $q->where('severity', $filters['level']);
            })
            ->when($filters['search'], function($q) use ($filters) {
                $q->where(function($query) use ($filters) {
                    $query->where('event_type', 'like', '%' . $filters['search'] . '%')
                          ->orWhere('description', 'like', '%' . $filters['search'] . '%')
                          ->orWhere('ip_address', 'like', '%' . $filters['search'] . '%');
                });
            })
            ->when($filters['date_from'], function($q) use ($filters) {
                $q->where('created_at', '>=', Carbon::parse($filters['date_from'])->startOfDay());
            })
            ->when($filters['date_to'], function($q) use ($filters) {
                $q->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
            });

        $securityLogs = $query->latest()->paginate(25)->withQueryString();

        // Get security stats
        $stats = [
            'total' => SecurityLog::count(),
            'critical' => SecurityLog::where('severity', SecurityLog::SEVERITY_LEVELS['CRITICAL'])->count(),
            'danger' => SecurityLog::where('severity', SecurityLog::SEVERITY_LEVELS['DANGER'])->count(),
            'warning' => SecurityLog::where('severity', SecurityLog::SEVERITY_LEVELS['WARNING'])->count(),
            'warnings' => SecurityLog::where('severity', SecurityLog::SEVERITY_LEVELS['WARNING'])->count(), // Alias for view compatibility
            'today' => SecurityLog::whereDate('created_at', today())->count(),
            'banned_ips' => BannedIp::where(function($q) {
                $q->whereNull('banned_until')
                  ->orWhere('banned_until', '>', now());
            })->count(),
        ];

        // Get recent threats
        $recentThreats = SecurityLog::where('severity', SecurityLog::SEVERITY_LEVELS['CRITICAL'])
            ->orWhere('severity', SecurityLog::SEVERITY_LEVELS['DANGER'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get threat types
        $threatTypes = SecurityLog::select('event_type')
            ->selectRaw('count(*) as count')
            ->groupBy('event_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'event_type');

        return view('content.dashboard.monitoring.security', [
            'logs' => $securityLogs,
            'filters' => $filters,
            'stats' => $stats,
            'recentThreats' => $recentThreats,
            'threatTypes' => $threatTypes,
            'levels' => [ // remains 'levels' for view compatibility
                SecurityLog::SEVERITY_LEVELS['INFO'] => 'معلومات',
                SecurityLog::SEVERITY_LEVELS['WARNING'] => 'تحذير',
                SecurityLog::SEVERITY_LEVELS['DANGER'] => 'خطر',
                SecurityLog::SEVERITY_LEVELS['CRITICAL'] => 'حرج'
            ]
        ]);
    }

    public function show($id)
    {
        $log = SecurityLog::with('user')->findOrFail($id);
        
        // Get related logs from the same IP
        $relatedLogs = SecurityLog::where('ip_address', $log->ip_address)
            ->where('id', '!=', $log->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Check if IP is banned
        $isBanned = BannedIp::where('ip', $log->ip_address)
            ->where(function($q) {
                $q->whereNull('banned_until')
                  ->orWhere('banned_until', '>', now());
            })
            ->exists();

        return view('content.dashboard.monitoring.security-details', [
            'log' => $log,
            'relatedLogs' => $relatedLogs,
            'isBanned' => $isBanned
        ]);
    }

    public function export(Request $request)
    {
        $logs = SecurityLog::query()
            ->when($request->has('level'), function($q) use ($request) {
                $q->where('severity', $request->level);
            })
            ->when($request->has('date_from'), function($q) use ($request) {
                $q->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
            })
            ->when($request->has('date_to'), function($q) use ($request) {
                $q->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=security-logs-' . now()->format('Y-m-d') . '.csv',
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'التاريخ',
                'المستوى',
                'نوع الحدث',
                'عنوان IP',
                'المستخدم',
                'المسار',
                'الوصف'
            ]);

            // Add data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $this->getLevelLabel($log->severity),
                    $log->event_type,
                    $log->ip_address,
                    $log->user ? $log->user->name : 'زائر',
                    $log->route,
                    strip_tags($log->description)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function getLevelLabel($level)
    {
        $levels = [
            SecurityLog::SEVERITY_LEVELS['INFO'] => 'معلومات',
            SecurityLog::SEVERITY_LEVELS['WARNING'] => 'تحذير',
            SecurityLog::SEVERITY_LEVELS['DANGER'] => 'خطر',
            SecurityLog::SEVERITY_LEVELS['CRITICAL'] => 'حرج'
        ];

        return $levels[$level] ?? $level;
    }
}
