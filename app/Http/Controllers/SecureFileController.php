<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FileSecurityService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class SecureFileController extends Controller
{
    /**
     * خدمة أمان الملفات
     */
    protected $fileSecurityService;

    /**
     * إنشاء مثيل جديد من وحدة التحكم
     */
    public function __construct(FileSecurityService $fileSecurityService)
    {
        $this->fileSecurityService = $fileSecurityService;
        $this->middleware('auth')->except(['view']);
    }

    /**
     * رفع ملف صورة آمن
     */
    public function uploadImage(Request $request)
    {
        // التحقق من صحة الطلب
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|image|max:10240', // 10 ميجابايت كحد أقصى
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        try {
            // فحص الملف للتأكد من أنه آمن
            $file = $request->file('file');
            [$isSafe, $message] = $this->fileSecurityService->scanFile($file, 'image');
            
            if (!$isSafe) {
                Log::warning('تم رفض ملف صورة غير آمن', [
                    'filename' => $file->getClientOriginalName(),
                    'ip' => $request->ip(),
                    'user_id' => $request->user()->id,
                    'reason' => $message
                ]);
                
                return response()->json(['error' => $message], 400);
            }
            
            // تخزين الملف بشكل آمن
            $fileInfo = $this->fileSecurityService->securelyStoreFile($file, 'image');
            
            Log::info('تم رفع ملف صورة آمن', [
                'user_id' => $request->user()->id,
                'filename' => $fileInfo['filename'],
                'path' => $fileInfo['path']
            ]);
            
            return response()->json([
                'url' => $fileInfo['url'],
                'filename' => $fileInfo['filename'],
                'message' => 'تم رفع الصورة بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ أثناء رفع ملف صورة', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);
            
            return response()->json(['error' => 'حدث خطأ أثناء رفع الصورة. الرجاء المحاولة مرة أخرى.'], 500);
        }
    }

    /**
     * رفع ملف مستند آمن
     */
    public function uploadDocument(Request $request)
    {
        // التحقق من صحة الطلب
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10 ميجابايت كحد أقصى
            'file' => 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        try {
            // فحص الملف للتأكد من أنه آمن
            $file = $request->file('file');
            [$isSafe, $message] = $this->fileSecurityService->scanFile($file, 'document');
            
            if (!$isSafe) {
                Log::warning('تم رفض ملف مستند غير آمن', [
                    'filename' => $file->getClientOriginalName(),
                    'ip' => $request->ip(),
                    'user_id' => $request->user()->id,
                    'reason' => $message
                ]);
                
                return response()->json(['error' => $message], 400);
            }
            
            // تخزين الملف بشكل آمن
            $fileInfo = $this->fileSecurityService->securelyStoreFile($file, 'document');
            
            Log::info('تم رفع ملف مستند آمن', [
                'user_id' => $request->user()->id,
                'filename' => $fileInfo['filename'],
                'path' => $fileInfo['path']
            ]);
            
            return response()->json([
                'url' => $fileInfo['url'],
                'filename' => $fileInfo['filename'],
                'message' => 'تم رفع المستند بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('خطأ أثناء رفع ملف مستند', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);
            
            return response()->json(['error' => 'حدث خطأ أثناء رفع المستند. الرجاء المحاولة مرة أخرى.'], 500);
        }
    }

    /**
     * عرض ملف آمن
     */
    public function view(Request $request)
    {
        // التحقق من صحة الطلب
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            abort(404);
        }

        $path = $request->input('path');
        $token = $request->input('token');
        
        // التحقق من صحة الرمز
        $expectedToken = hash_hmac('sha256', $path, config('app.key'));
        
        if (!hash_equals($expectedToken, $token)) {
            Log::warning('محاولة وصول غير مصرح به إلى ملف آمن', [
                'path' => $path,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            abort(403);
        }
        
        // التحقق من وجود الملف
        if (!Storage::disk('private')->exists($path)) {
            abort(404);
        }
        
        // تحديد نوع المحتوى
        $mimeType = Storage::disk('private')->mimeType($path);
        
        // تسجيل الوصول إلى الملف
        Log::info('تم الوصول إلى ملف آمن', [
            'path' => $path,
            'ip' => $request->ip(),
            'user_id' => $request->user() ? $request->user()->id : 'guest'
        ]);
        
        // إرجاع الملف
        return Response::make(Storage::disk('private')->get($path), 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
