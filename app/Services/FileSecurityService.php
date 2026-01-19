<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileSecurityService
{
    /**
     * قائمة امتدادات الملفات المسموح بها
     */
    protected $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    /**
     * قائمة امتدادات المستندات المسموح بها
     */
    protected $allowedDocumentExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
    
    /**
     * قائمة أنواع MIME المسموح بها للصور
     */
    protected $allowedImageMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml'
    ];
    
    /**
     * قائمة أنواع MIME المسموح بها للمستندات
     */
    protected $allowedDocumentMimeTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain'
    ];
    
    /**
     * الحد الأقصى لحجم الملف (بالبايت) - 10 ميجابايت
     */
    protected $maxFileSize = 10 * 1024 * 1024;
    
    /**
     * فحص الملف للتأكد من أنه آمن
     *
     * @param UploadedFile $file الملف المرفوع
     * @param string $type نوع الملف (image أو document)
     * @return array نتيجة الفحص [is_safe, message]
     */
    public function scanFile(UploadedFile $file, string $type = 'image'): array
    {
        // التحقق من صلاحية الملف
        if (!$file->isValid()) {
            return [false, 'الملف غير صالح أو تالف.'];
        }
        
        // التحقق من حجم الملف
        if ($file->getSize() > $this->maxFileSize) {
            return [false, 'حجم الملف يتجاوز الحد المسموح به (10 ميجابايت).'];
        }
        
        // التحقق من امتداد الملف ونوع MIME
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        
        if ($type === 'image') {
            if (!in_array($extension, $this->allowedImageExtensions)) {
                return [false, 'امتداد الملف غير مسموح به. الامتدادات المسموح بها هي: ' . implode(', ', $this->allowedImageExtensions)];
            }
            
            if (!in_array($mimeType, $this->allowedImageMimeTypes)) {
                return [false, 'نوع MIME للملف غير مسموح به.'];
            }
            
            // فحص إضافي للصور
            return $this->scanImage($file);
        } else {
            if (!in_array($extension, $this->allowedDocumentExtensions)) {
                return [false, 'امتداد الملف غير مسموح به. الامتدادات المسموح بها هي: ' . implode(', ', $this->allowedDocumentExtensions)];
            }
            
            if (!in_array($mimeType, $this->allowedDocumentMimeTypes)) {
                return [false, 'نوع MIME للملف غير مسموح به.'];
            }
            
            // فحص إضافي للمستندات
            return $this->scanDocument($file);
        }
    }
    
    /**
     * فحص الصورة للتأكد من أنها آمنة
     *
     * @param UploadedFile $file ملف الصورة
     * @return array نتيجة الفحص [is_safe, message]
     */
    protected function scanImage(UploadedFile $file): array
    {
        try {
            // التحقق من أن الملف صورة حقيقية
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo === false) {
                return [false, 'الملف ليس صورة صالحة.'];
            }
            
            // التحقق من وجود أكواد PHP مخفية في الصورة
            $content = file_get_contents($file->getRealPath());
            $suspiciousPatterns = [
                '<?php',
                '<?=',
                'eval(',
                'base64_decode(',
                'gzinflate(',
                'exec(',
                'system(',
                'passthru(',
                'shell_exec('
            ];
            
            foreach ($suspiciousPatterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    Log::warning('تم اكتشاف نمط مشبوه في ملف صورة', [
                        'pattern' => $pattern,
                        'filename' => $file->getClientOriginalName(),
                        'ip' => request()->ip()
                    ]);
                    return [false, 'تم اكتشاف محتوى مشبوه في الصورة.'];
                }
            }
            
            return [true, 'الصورة آمنة.'];
        } catch (\Exception $e) {
            Log::error('خطأ أثناء فحص الصورة', [
                'error' => $e->getMessage(),
                'filename' => $file->getClientOriginalName()
            ]);
            return [false, 'حدث خطأ أثناء فحص الصورة.'];
        }
    }
    
    /**
     * فحص المستند للتأكد من أنه آمن
     *
     * @param UploadedFile $file ملف المستند
     * @return array نتيجة الفحص [is_safe, message]
     */
    protected function scanDocument(UploadedFile $file): array
    {
        try {
            // التحقق من وجود أكواد مشبوهة في المستند
            $content = file_get_contents($file->getRealPath());
            $suspiciousPatterns = [
                '<?php',
                '<?=',
                '<script>',
                'javascript:',
                'vbscript:',
                'data:text/html',
                'ActiveXObject',
                'eval(',
                'document.cookie',
                'document.write(',
                'window.location'
            ];
            
            foreach ($suspiciousPatterns as $pattern) {
                if (stripos($content, $pattern) !== false) {
                    Log::warning('تم اكتشاف نمط مشبوه في ملف مستند', [
                        'pattern' => $pattern,
                        'filename' => $file->getClientOriginalName(),
                        'ip' => request()->ip()
                    ]);
                    return [false, 'تم اكتشاف محتوى مشبوه في المستند.'];
                }
            }
            
            // التحقق من وجود ماكروز في ملفات Office
            $extension = strtolower($file->getClientOriginalExtension());
            if (in_array($extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) {
                // فحص بسيط للماكروز - يمكن استبداله بمكتبة متخصصة في الإنتاج
                $macroPatterns = [
                    'VBA',
                    'macro',
                    'Sub ',
                    'Function ',
                    'AutoExec',
                    'AutoOpen',
                    'Document_Open',
                    'Workbook_Open'
                ];
                
                foreach ($macroPatterns as $pattern) {
                    if (stripos($content, $pattern) !== false) {
                        Log::warning('تم اكتشاف ماكرو محتمل في ملف Office', [
                            'pattern' => $pattern,
                            'filename' => $file->getClientOriginalName(),
                            'ip' => request()->ip()
                        ]);
                        return [false, 'تم اكتشاف ماكرو محتمل في المستند.'];
                    }
                }
            }
            
            return [true, 'المستند آمن.'];
        } catch (\Exception $e) {
            Log::error('خطأ أثناء فحص المستند', [
                'error' => $e->getMessage(),
                'filename' => $file->getClientOriginalName()
            ]);
            return [false, 'حدث خطأ أثناء فحص المستند.'];
        }
    }
    
    /**
     * تخزين الملف بشكل آمن خارج المجلد العام
     *
     * @param UploadedFile $file الملف المرفوع
     * @param string $type نوع الملف (image أو document)
     * @return array معلومات الملف المخزن [path, url, filename]
     */
    public function securelyStoreFile(UploadedFile $file, string $type = 'image'): array
    {
        // إنشاء اسم عشوائي للملف مع الاحتفاظ بالامتداد الأصلي
        $originalExtension = $file->getClientOriginalExtension();
        $filename = Str::random(40) . '_' . time() . '.' . $originalExtension;
        
        // تحديد مسار التخزين بناءً على نوع الملف
        $storagePath = $type === 'image' ? 'secure-images' : 'secure-documents';
        
        // تخزين الملف في المسار المحدد
        $path = $file->storeAs($storagePath, $filename, 'private');
        
        // إنشاء رابط مؤقت للملف (يمكن استخدام وسيط خاص للتحقق من الصلاحية)
        $url = route('secure.file.view', [
            'path' => $path,
            'token' => $this->generateSecureToken($path)
        ]);
        
        return [
            'path' => $path,
            'url' => $url,
            'filename' => $filename
        ];
    }
    
    /**
     * إنشاء رمز آمن للملف
     *
     * @param string $path مسار الملف
     * @return string الرمز الآمن
     */
    protected function generateSecureToken(string $path): string
    {
        // إنشاء رمز آمن باستخدام مسار الملف ومفتاح التطبيق
        return hash_hmac('sha256', $path, config('app.key'));
    }
}
