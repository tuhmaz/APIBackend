<?php

namespace App\Notifications;

use App\Models\SecurityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SecurityThresholdAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * سجل الأمان المرتبط بهذا التنبيه
     *
     * @var SecurityLog
     */
    protected $securityLog;

    /**
     * إنشاء مثيل جديد للإشعار.
     *
     * @param SecurityLog $securityLog
     * @return void
     */
    public function __construct(SecurityLog $securityLog)
    {
        $this->securityLog = $securityLog;
    }

    /**
     * الحصول على قنوات تسليم الإشعار.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // إرسال تنبيهات العتبة عبر قاعدة البيانات فقط
        return ['database'];
    }

    /**
     * الحصول على تمثيل قاعدة البيانات للإشعار.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'id' => $this->securityLog->id,
            'type' => 'security_threshold_alert',
            'event_type' => $this->securityLog->event_type,
            'description' => $this->securityLog->description,
            'ip_address' => $this->securityLog->ip_address,
            'severity' => $this->securityLog->severity,
            'created_at' => $this->securityLog->created_at->format('Y-m-d H:i:s'),
            'url' => route('security.logs.show', $this->securityLog->id),
            'message' => 'تم تجاوز عتبة الأحداث الأمنية لنوع الحدث: ' . $this->securityLog->event_type,
        ];
    }

    /**
     * الحصول على مصفوفة للبحث عن الإشعار.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'security_log_id' => $this->securityLog->id,
            'event_type' => $this->securityLog->event_type,
            'description' => $this->securityLog->description,
            'ip_address' => $this->securityLog->ip_address,
            'severity' => $this->securityLog->severity,
        ];
    }
}
