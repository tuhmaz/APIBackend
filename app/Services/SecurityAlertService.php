<?php

namespace App\Services;

use App\Models\SecurityLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Carbon\Carbon;

class SecurityAlertService
{
    /**
     * قائمة بأنواع الأحداث التي تتطلب تنبيهًا فوريًا
     */
    protected $criticalEventTypes = [
        'blocked_access',
        'suspicious_activity',
        'unauthorized_access',
        'brute_force_attempt',
        'sql_injection_attempt',
        'xss_attempt',
        'file_upload_violation',
        'api_abuse',
        'admin_account_change',
    ];

    /**
     * عتبات التنبيه للأحداث المختلفة
     */
    protected $alertThresholds = [
        'failed_login' => [
            'count' => 5,
            'period' => 10, // بالدقائق
            'cooldown' => 60, // فترة التهدئة بالدقائق
        ],
        'suspicious_activity' => [
            'count' => 3,
            'period' => 30,
            'cooldown' => 120,
        ],
        'blocked_access' => [
            'count' => 2,
            'period' => 60,
            'cooldown' => 240,
        ],
    ];

    /**
     * معالجة حدث أمني جديد وإرسال تنبيهات إذا لزم الأمر
     *
     * @param SecurityLog $log
     * @return void
     */
    public function processSecurityEvent(SecurityLog $log): void
    {
        // التحقق من الأحداث الحرجة التي تتطلب تنبيهًا فوريًا
        if (in_array($log->event_type, $this->criticalEventTypes) || $log->severity === SecurityLog::SEVERITY_LEVELS['CRITICAL']) {
            $this->sendImmediateAlert($log);
            return;
        }

        // التحقق من تجاوز العتبات للأحداث المتكررة
        if (isset($this->alertThresholds[$log->event_type])) {
            $this->checkThresholdAlert($log);
        }
    }

    /**
     * إرسال تنبيه فوري للأحداث الحرجة
     *
     * @param SecurityLog $log
     * @return void
     */
    protected function sendImmediateAlert(SecurityLog $log): void
    {
        // تجنب إرسال تنبيهات متكررة للحدث نفسه
        $cacheKey = "security_alert:{$log->event_type}:{$log->ip_address}";
        if (Cache::has($cacheKey)) {
            return;
        }

        // تخزين مؤقت لمنع التنبيهات المتكررة
        Cache::put($cacheKey, true, now()->addMinutes(30));

        // تسجيل التنبيه في السجلات
        Log::channel('security')->alert(
            "تنبيه أمني حرج: {$log->event_type}",
            [
                'log_id' => $log->id,
                'ip_address' => $log->ip_address,
                'user_id' => $log->user_id,
                'severity' => $log->severity,
                'description' => $log->description,
            ]
        );

        // إرسال تنبيه للمسؤولين
        $this->notifyAdmins($log, true);
    }

    /**
     * التحقق من تجاوز العتبات للأحداث المتكررة
     *
     * @param SecurityLog $log
     * @return void
     */
    protected function checkThresholdAlert(SecurityLog $log): void
    {
        $threshold = $this->alertThresholds[$log->event_type];
        $cacheKey = "security_threshold:{$log->event_type}:{$log->ip_address}";
        
        // التحقق من فترة التهدئة
        if (Cache::has("{$cacheKey}:cooldown")) {
            return;
        }

        // حساب عدد الأحداث في الفترة المحددة
        $count = SecurityLog::where('event_type', $log->event_type)
            ->where('ip_address', $log->ip_address)
            ->where('created_at', '>=', now()->subMinutes($threshold['period']))
            ->count();

        // التحقق من تجاوز العتبة
        if ($count >= $threshold['count']) {
            // تسجيل التنبيه في السجلات
            Log::channel('security')->warning(
                "تنبيه تجاوز العتبة: {$log->event_type}",
                [
                    'ip_address' => $log->ip_address,
                    'count' => $count,
                    'period' => $threshold['period'],
                    'threshold' => $threshold['count'],
                ]
            );

            // إرسال تنبيه للمسؤولين
            $this->notifyAdmins($log, false);

            // تعيين فترة التهدئة
            Cache::put("{$cacheKey}:cooldown", true, now()->addMinutes($threshold['cooldown']));
        }
    }

    /**
     * إرسال تنبيه للمسؤولين
     *
     * @param SecurityLog $log
     * @param bool $isCritical
     * @return void
     */
    protected function notifyAdmins(SecurityLog $log, bool $isCritical): void
    {
        // الحصول على المسؤولين
        $admins = User::role('admin')->get();

        // إرسال التنبيه عبر القنوات المختلفة (بريد إلكتروني، إشعارات، SMS، إلخ)
        foreach ($admins as $admin) {
            try {
                // يمكن استخدام نظام الإشعارات في Laravel
                if ($isCritical) {
                    // للأحداث الحرجة، استخدم قنوات متعددة
                    Notification::send($admin, new \App\Notifications\CriticalSecurityAlert($log));
                } else {
                    // للأحداث العادية، استخدم الإشعارات داخل التطبيق فقط
                    Notification::send($admin, new \App\Notifications\SecurityThresholdAlert($log));
                }
            } catch (\Exception $e) {
                Log::error("فشل في إرسال تنبيه أمني", [
                    'admin_id' => $admin->id,
                    'log_id' => $log->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * الحصول على ملخص التنبيهات الأمنية للفترة المحددة
     *
     * @param int $hours
     * @return array
     */
    public function getSecurityAlertsSummary(int $hours = 24): array
    {
        $startTime = now()->subHours($hours);
        
        $criticalAlerts = SecurityLog::where('created_at', '>=', $startTime)
            ->where(function ($query) {
                $query->whereIn('event_type', $this->criticalEventTypes)
                    ->orWhere('severity', SecurityLog::SEVERITY_LEVELS['CRITICAL']);
            })
            ->count();
            
        $thresholdAlerts = 0;
        foreach ($this->alertThresholds as $eventType => $threshold) {
            $ips = SecurityLog::where('event_type', $eventType)
                ->where('created_at', '>=', $startTime)
                ->distinct('ip_address')
                ->pluck('ip_address');
                
            foreach ($ips as $ip) {
                $count = SecurityLog::where('event_type', $eventType)
                    ->where('ip_address', $ip)
                    ->where('created_at', '>=', $startTime)
                    ->count();
                    
                if ($count >= $threshold['count']) {
                    $thresholdAlerts++;
                }
            }
        }
        
        return [
            'critical_alerts' => $criticalAlerts,
            'threshold_alerts' => $thresholdAlerts,
            'total_alerts' => $criticalAlerts + $thresholdAlerts,
            'period_hours' => $hours,
        ];
    }
}
