<?php

return [
    /*
    |--------------------------------------------------------------------------
    | مفاتيح API
    |--------------------------------------------------------------------------
    |
    | هذا الملف يحتوي على مفاتيح API المستخدمة في التطبيق
    | يمكن تعديل هذه المفاتيح في ملف .env
    |
    */

    // مفتاح API الرئيسي للتطبيق (لا تضع قيمة افتراضية داخل المستودع)
    'key' => env('API_KEY'),
    
    // ترويسات القراءة من الطلب
    'headers' => [
        'key' => env('API_KEY_HEADER', 'X-Api-Key'),
        'client' => env('API_CLIENT_HEADER', 'User-Agent'),
    ],

    // قائمة بالعملاء المسموح لهم (CSV من .env)، مع قيم افتراضية منطقية لغير الإنتاج
    'allowed_clients' => array_values(array_filter(array_map('trim', explode(',', env(
        'API_ALLOWED_CLIENTS', 'Flutter,Dart,Mobile,Android,iPhone,iPad,Windows Phone'
    ))))),

    // السماح باستخدام PostmanRuntime للاختبار فقط (معطل تلقائياً في الإنتاج)
    'allow_postman' => filter_var(env('API_ALLOW_POSTMAN', env('APP_ENV') !== 'production'), FILTER_VALIDATE_BOOL),
    
    // السماح بقراءة مفتاح API من معلمة الاستعلام أو الجسم عند غياب الترويسة (افتراضياً معطل)
    // ملاحظة: يُفضَّل استخدام الترويسة دائماً، فعّل هذا فقط للاختبارات أو التوافق الرجعي
    'allow_query_key' => filter_var(env('API_ALLOW_QUERY_KEY', false), FILTER_VALIDATE_BOOL),
    
    // إعدادات الأمان الإضافية
    'security' => [
        // هل يتم تسجيل محاولات الوصول غير المصرح بها
        'log_unauthorized_attempts' => filter_var(env('API_LOG_UNAUTHORIZED', true), FILTER_VALIDATE_BOOL),

        // هل يتم التحقق من نوع العميل
        'check_client_type' => filter_var(env('API_CHECK_CLIENT_TYPE', true), FILTER_VALIDATE_BOOL),

        // تقييد بالـ IP (قائمة بيضاء) - CSV من .env
        'ip_whitelist' => array_values(array_filter(array_map('trim', explode(',', env('API_IP_WHITELIST', ''))))),

        // تحديد معدّل الطلبات لحماية الـ API
        'rate_limit' => [
            'enabled' => filter_var(env('API_RATE_LIMIT', true), FILTER_VALIDATE_BOOL),
            'max' => (int) env('API_RATE_LIMIT_MAX', 60),
            'per_seconds' => (int) env('API_RATE_LIMIT_WINDOW', 60),
        ],

        // تفعيل توقيع HMAC لحماية الجسم/المعلمات
        'signature' => [
            'enabled' => filter_var(env('API_SIGNATURE_ENABLED', false), FILTER_VALIDATE_BOOL),
            'secret' => env('API_SIGNATURE_SECRET'),
            'algorithm' => env('API_SIGNATURE_ALGO', 'sha256'),
            'header' => env('API_SIGNATURE_HEADER', 'X-Signature'),
            'timestamp_header' => env('API_TIMESTAMP_HEADER', 'X-Timestamp'),
            'nonce_header' => env('API_NONCE_HEADER', 'X-Nonce'),
            // الانحراف الزمني المسموح به للطلبات الموقّعة (بالثواني)
            'allowed_drift' => (int) env('API_SIGNATURE_DRIFT', 300),
        ],
    ],
];
