<?php

namespace App\Notifications;

use App\Models\SecurityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CriticalSecurityAlert extends Notification implements ShouldQueue
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
        // إرسال الإشعارات الحرجة عبر قنوات متعددة
        return ['mail', 'database'];
    }

    /**
     * الحصول على تمثيل البريد الإلكتروني للإشعار.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = route('security.logs.show', $this->securityLog->id);

        return (new MailMessage)
            ->subject('تنبيه أمني حرج: ' . $this->securityLog->event_type)
            ->greeting('تنبيه أمني حرج!')
            ->line('تم اكتشاف حدث أمني حرج يتطلب اهتمامك الفوري.')
            ->line('نوع الحدث: ' . $this->securityLog->event_type)
            ->line('الوصف: ' . $this->securityLog->description)
            ->line('عنوان IP: ' . $this->securityLog->ip_address)
            ->line('وقت الحدث: ' . $this->securityLog->created_at->format('Y-m-d H:i:s'))
            ->line('مستوى الخطورة: ' . $this->securityLog->severity)
            ->action('عرض التفاصيل', $url)
            ->line('يرجى اتخاذ الإجراء المناسب على الفور.');
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
            'type' => 'critical_security_alert',
            'event_type' => $this->securityLog->event_type,
            'description' => $this->securityLog->description,
            'ip_address' => $this->securityLog->ip_address,
            'severity' => $this->securityLog->severity,
            'created_at' => $this->securityLog->created_at->format('Y-m-d H:i:s'),
            'url' => route('security.logs.show', $this->securityLog->id),
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
