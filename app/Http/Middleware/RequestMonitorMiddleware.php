<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\SecurityLog;
use App\Services\SecurityAlertService;

class RequestMonitorMiddleware
{
    /**
     * خدمة تنبيهات الأمان
     *
     * @var SecurityAlertService
     */
    protected $securityAlertService;

    /**
     * قائمة المسارات الحساسة التي تتطلب مراقبة إضافية
     *
     * @var array
     */
    protected $sensitivePaths = [
        'admin',
        'api',
        'login',
        'register',
        'password/reset',
        'settings',
        'users',
        'profile',
        'secure-upload',
        'payment',
    ];

    /**
     * إنشاء مثيل جديد للوسيط.
     *
     * @param SecurityAlertService $securityAlertService
     * @return void
     */
    public function __construct(SecurityAlertService $securityAlertService)
    {
        $this->securityAlertService = $securityAlertService;
    }

    /**
     * معالجة الطلب الوارد.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // تحقق مما إذا كان الطلب يستهدف مسارًا حساسًا
        $isSensitivePath = $this->isSensitivePath($request->path());
        
        // تحقق من وجود علامات مشبوهة في الطلب
        $suspiciousScore = $this->calculateSuspiciousScore($request);
        
        // تسجيل الطلبات المشبوهة فقط (تجاهل الطلبات العادية حتى للمسارات الحساسة)
        if ($suspiciousScore >= 70) {
            
            $log = SecurityLog::create([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'event_type' => 'suspicious_activity',
                'description' => "طلب مشبوه (درجة: {$suspiciousScore}) إلى المسار: {$request->path()}",
                'user_id' => auth()->id(),
                'route' => $request->route() ? $request->route()->getName() : $request->path(),
                'request_data' => $this->sanitizeRequestData($request),
                'severity' => SecurityLog::SEVERITY_LEVELS['WARNING'],
                'is_resolved' => false,
                'risk_score' => $suspiciousScore,
            ]);
            
            // إذا كان النشاط مشبوهًا بدرجة كافية، قم بمعالجة الحدث الأمني
            $this->securityAlertService->processSecurityEvent($log);
        }
        
        return $next($request);
    }

    /**
     * التحقق مما إذا كان المسار حساسًا.
     *
     * @param  string  $path
     * @return bool
     */
    protected function isSensitivePath(string $path): bool
    {
        foreach ($this->sensitivePaths as $sensitivePath) {
            if (strpos($path, $sensitivePath) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * حساب درجة الاشتباه للطلب.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function calculateSuspiciousScore(Request $request): int
    {
        $score = 0;
        
        // التحقق من وكيل المستخدم
        $userAgent = $request->userAgent();
        if (empty($userAgent)) {
            $score += 20;
        } elseif (strpos($userAgent, 'curl') !== false || strpos($userAgent, 'wget') !== false) {
            $score += 15;
        } elseif (strpos($userAgent, 'bot') !== false && strpos($userAgent, 'googlebot') === false) {
            $score += 10;
        }
        
        // التحقق من رأس الإحالة
        $referer = $request->header('referer');
        if (empty($referer) && !$request->isMethod('GET')) {
            $score += 10;
        }
        
        // التحقق من طريقة الطلب
        if ($request->isMethod('PUT') || $request->isMethod('DELETE')) {
            $score += 5;
        }
        
        // التحقق من معلمات الطلب المشبوهة
        $params = $request->all();
        foreach ($params as $key => $value) {
            // البحث عن معلمات تحتوي على أنماط مشبوهة
            if (is_string($value)) {
                if (strlen($value) > 500) {
                    $score += 10;
                }
                
                if (preg_match('/(\.\.|\/var\/www|\\/etc\\/passwd|\/bin\/bash|\\.\\/|\\.\\.\\\\)/', $value)) {
                    $score += 25;
                }
                
                if (preg_match('/(select|union|insert|update|delete|drop|alter|exec|system|eval)/', strtolower($value))) {
                    $score += 20;
                }
            }
        }
        
        // التحقق من الرؤوس المشبوهة
        $headers = $request->headers->all();
        foreach ($headers as $header => $value) {
            if (preg_match('/(x-forwarded-for|x-real-ip|forwarded)/', strtolower($header))) {
                $score += 5;
            }
        }
        
        // التحقق من وجود ملفات في الطلب
        if ($request->hasFile('file')) {
            $score += 10;
        }
        
        return min($score, 100);
    }

    /**
     * تنظيف بيانات الطلب قبل التخزين.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function sanitizeRequestData(Request $request): string
    {
        try {
            $data = $request->except(['password', 'password_confirmation', 'token', '_token', 'credit_card', 'card_number']);
            
            // تحويل البيانات إلى الشكل المناسب للتخزين
            $sanitizedData = $this->recursiveSanitize($data);
            
            // تحويل البيانات إلى JSON
            $jsonData = json_encode($sanitizedData, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            
            // إذا فشل التحويل إلى JSON، نقوم بتخزين رسالة خطأ
            if ($jsonData === false) {
                return json_encode([
                    'error' => 'Failed to encode request data',
                    'data_type' => gettype($data)
                ]);
            }
            
            return $jsonData;
        } catch (\Exception $e) {
            // في حالة حدوث أي خطأ، نقوم بتخزين معلومات الخطأ
            return json_encode([
                'error' => $e->getMessage(),
                'data_type' => gettype($data)
            ]);
        }
    }
    
    /**
     * معالجة البيانات بشكل متكرر للتأكد من إمكانية تحويلها إلى JSON
     *
     * @param mixed $data
     * @return mixed
     */
    protected function recursiveSanitize($data)
    {
        if (is_array($data) || is_object($data)) {
            $result = [];
            foreach ((array) $data as $key => $value) {
                // تجاهل المفاتيح الخاصة في الكائنات
                if ($key[0] === '\0') {
                    continue;
                }
                
                // معالجة القيم المتداخلة
                $result[$key] = $this->recursiveSanitize($value);
            }
            return $result;
        } elseif (is_string($data)) {
            // تقليص النصوص الطويلة
            return strlen($data) > 1000 ? substr($data, 0, 1000) . '... [truncated]' : $data;
        } elseif (is_resource($data)) {
            // تحويل الموارد إلى وصف نصي
            return 'Resource of type: ' . get_resource_type($data);
        } elseif ($data instanceof \Closure) {
            // تحويل الدوال المغلقة إلى وصف نصي
            return 'Closure';
        } else {
            // إرجاع القيم الأخرى كما هي
            return $data;
        }
    }
}
