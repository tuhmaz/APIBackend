<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\FileSecurityService;
use Illuminate\Support\Facades\Log;

class SecureFileUpload
{
    /**
     * خدمة أمان الملفات
     */
    protected $fileSecurityService;

    /**
     * إنشاء مثيل جديد من الوسيط
     */
    public function __construct(FileSecurityService $fileSecurityService)
    {
        $this->fileSecurityService = $fileSecurityService;
    }

    /**
     * معالجة الطلب
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $fileType  نوع الملف (image أو document)
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $fileType = 'image')
    {
        // التحقق من وجود ملفات في الطلب
        if ($request->hasFile('file') || $request->hasFile('files')) {
            $files = $request->hasFile('file') ? [$request->file('file')] : $request->file('files');
            
            foreach ($files as $file) {
                // فحص الملف للتأكد من أنه آمن
                [$isSafe, $message] = $this->fileSecurityService->scanFile($file, $fileType);
                
                if (!$isSafe) {
                    Log::warning('تم رفض ملف غير آمن', [
                        'filename' => $file->getClientOriginalName(),
                        'ip' => $request->ip(),
                        'user_id' => $request->user() ? $request->user()->id : 'guest',
                        'reason' => $message
                    ]);
                    
                    if ($request->expectsJson()) {
                        return response()->json(['error' => $message], 400);
                    }
                    
                    return redirect()->back()->withErrors(['file' => $message]);
                }
            }
        }
        
        return $next($request);
    }
}
