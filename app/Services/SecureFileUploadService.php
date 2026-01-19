<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Exception;

class SecureFileUploadService
{
    /**
     * القائمة البيضاء لأنواع الملفات المسموح بها
     */
    protected $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/x-pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        // Added to match attachments validation
        'text/csv',
        'application/rtf',
        'text/rtf',
        'application/vnd.oasis.opendocument.text',       // odt
        'application/vnd.oasis.opendocument.spreadsheet',// ods
        'application/vnd.oasis.opendocument.presentation',// odp
        'application/x-7z-compressed',                   // 7z
        'application/x-tar',                             // tar
        'application/gzip',                              // gz
        'application/x-gzip',                            // gz (alt)
    ];

    /**
     * الحد الأقصى لحجم الملف (بالبايت) - 50 ميجابايت
     */
    protected $maxFileSize = 50 * 1024 * 1024; // 50MB لمواءمة التحقق في ArticleController

    /**
     * تخزين الملف بشكل آمن
     *
     * @param UploadedFile $file الملف المرفوع
     * @param string $directory المجلد الذي سيتم تخزين الملف فيه
     * @param bool $processImage هل يجب معالجة الملف كصورة
     * @param string|null $customFilename اسم مخصص للملف (اختياري)
     * @return string مسار الملف المخزن
     * @throws Exception في حالة وجود خطأ أمني
     */
    public function securelyStoreFile(UploadedFile $file, string $directory, bool $processImage = true, string $customFilename = null): string
    {
        // التحقق من أن الملف موجود وغير فارغ
        if (!$file || !$file->isValid()) {
            throw new Exception('الملف غير صالح');
        }

        // التحقق من نوع الملف
        if (!$this->isAllowedMimeType($file)) {
            throw new Exception('نوع الملف غير مسموح به');
        }

        // التحقق من حجم الملف
        if ($file->getSize() > $this->maxFileSize) {
            throw new Exception('حجم الملف أكبر من الحد المسموح به');
        }

        // فحص محتوى الملف للتأكد من أنه آمن (تخطي للملفات الثنائية المعروفة مثل PDF وأوفيس)
        if (!$this->scanFileContent($file)) {
            throw new Exception('محتوى الملف غير آمن');
        }

        // إنشاء اسم آمن للملف
        $safeFilename = $customFilename ? $this->sanitizeFilename($customFilename) : $this->generateSafeFilename($file);

        // معالجة الصور إذا كان الملف صورة
        if ($processImage && $this->isImage($file)) {
            try {
                return $this->processAndStoreImage($file, $directory, $safeFilename);
            } catch (Exception $e) {
                Log::warning('Image processing failed, falling back to original file storage', [
                    'error' => $e->getMessage(),
                    'directory' => $directory,
                ]);

                $fallbackName = Str::random(10) . '_' . time() . '.' . strtolower($file->getClientOriginalExtension());
                return $file->storeAs($directory, $fallbackName, 'public');
            }
        }

        // تخزين الملف العادي
        $path = $file->storeAs($directory, $safeFilename, 'public');
        return $path;
    }

    /**
     * تحسين اسم الملف المخصص
     */
    protected function sanitizeFilename(string $filename): string
    {
        // السماح بالأحرف العربية والأجنبية والأرقام والرموز الأساسية
        $filename = preg_replace('/[^\x{0600}-\x{06FF}a-zA-Z0-9._\-]/u', '', $filename);
        
        // إضافة الامتداد إذا لم يكن موجودًا
        if (strpos($filename, '.') === false) {
            // لا يمكننا تحديد نوع الملف هنا بدون الكائن الأصلي، افتراض امتداد نصي آمن
            $filename .= '.txt';
        }
        
        return $filename;
    }

    /**
     * التحقق من أن نوع الملف مسموح به
     */
    protected function isAllowedMimeType(UploadedFile $file): bool
    {
        // التحقق من نوع MIME الحقيقي للملف وليس الامتداد فقط
        $mimeType = $file->getMimeType();
        if (in_array($mimeType, $this->allowedMimeTypes)) {
            return true;
        }

        // بعض المتصفحات/العملاء يرسلون application/octet-stream لملفات معروفة
        if ($mimeType === 'application/octet-stream') {
            $ext = strtolower($file->getClientOriginalExtension());
            $safeExts = ['pdf','doc','docx','xls','xlsx','ppt','pptx','zip','rar','txt','jpg','jpeg','png','gif','webp',
                         // Added extensions to align with validation
                         'csv','rtf','odt','ods','odp','7z','tar','gz'];
            return in_array($ext, $safeExts);
        }

        return false;
    }

    /**
     * فحص محتوى الملف للتأكد من أنه آمن
     */
    protected function scanFileContent(UploadedFile $file): bool
    {
        $mime = $file->getMimeType();
        $ext = strtolower($file->getClientOriginalExtension());

        // تخطي فحص النصوص الضارة للملفات الثنائية المعروفة لتجنب الإيجابيات الكاذبة
        $binaryTypes = [
            'application/pdf','application/x-pdf',
            'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint','application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip','application/x-zip-compressed','application/x-rar-compressed',
            // Added to match attachments validation
            'text/csv','application/rtf','text/rtf',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.presentation',
            'application/x-7z-compressed','application/x-tar','application/gzip','application/x-gzip',
        ];
        $binaryExts = ['pdf','doc','docx','xls','xlsx','ppt','pptx','zip','rar',
                       // Added
                       'csv','rtf','odt','ods','odp','7z','tar','gz'];
        if (in_array($mime, $binaryTypes) || in_array($ext, $binaryExts)) {
            return true;
        }

        // قراءة أول 1024 بايت من الملف للتحقق من وجود أكواد PHP أو JavaScript ضارة
        $content = file_get_contents($file->getRealPath(), false, null, 0, 1024);
        
        // البحث عن أكواد PHP
        if (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false) {
            return false;
        }
        
        // البحث عن أكواد JavaScript ضارة
        $suspiciousPatterns = [
            '<script',
            'javascript:',
            'eval(',
            'document.cookie',
            'onerror=',
            'onload=',
            'onclick=',
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * إنشاء اسم آمن للملف
     */
    protected function generateSafeFilename(UploadedFile $file): string
    {
        $extension = $this->isImage($file) ? 'webp' : $file->getClientOriginalExtension();
        return Str::random(10) . '_' . time() . '.' . $extension;
    }

    /**
     * التحقق من أن الملف هو صورة
     */
    protected function isImage(UploadedFile $file): bool
    {
        $imageMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($file->getMimeType(), $imageMimeTypes);
    }

    /**
     * معالجة وتخزين الصورة
     */
    protected function processAndStoreImage(UploadedFile $file, string $directory, string $filename): string
    {
        // معالجة الصورة وتحويلها إلى WebP مع ضغط 75%
        $image = Image::read($file->getRealPath());
        
        // التحقق من أن الملف هو صورة حقيقية
        if (!$image->width() || !$image->height()) {
            throw new Exception('الملف ليس صورة صالحة');
        }
        
        // تحويل الصورة إلى WebP
        $encoded = $image->toWebp(75);
        
        // المسار الكامل للملف
        $fullPath = $directory . '/' . $filename;
        
        // حفظ الصورة في التخزين العام
        Storage::disk('public')->put($fullPath, (string) $encoded);
        
        return $fullPath;
    }
}
