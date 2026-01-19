<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class ErrorLogService
{
    /**
     * الحصول على أحدث الأخطاء من ملف السجل
     * 
     * @return array
     */
    public function getRecentErrors()
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            if (!File::exists($logFile)) {
                return [
                    'count' => 0,
                    'trend' => 0,
                    'recent' => []
                ];
            }

            // قراءة كامل ملف السجل (أو آخر 1000 سطر)
            $logs = file($logFile);
            if (count($logs) > 1000) {
                $logs = array_slice($logs, -1000);
            }
            
            $errors = [];
            $errorCount = 0;
            
            // نمط محسن للتعرف على أنماط مختلفة من رسائل الخطأ في Laravel
            $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(\w+)\.(ERROR|WARNING|NOTICE|DEPRECATED|ALERT|CRITICAL|EMERGENCY):(.*?)(?:\{|\[\]|$)/s';

            // معالجة كل سطر في ملف السجل
            foreach ($logs as $index => $log) {
                if (preg_match($pattern, $log, $matches)) {
                    $errorCount++;
                    $timestamp = $matches[1];
                    $channel = $matches[2];
                    $type = $matches[3];
                    $rawMessage = trim($matches[4]);
                    
                    // استخراج الرسالة الأساسية
                    $message = $rawMessage;
                    
                    // استخراج معلومات الملف والسطر
                    $file = '';
                    $line = '';
                    
                    // استخراج معلومات الاستثناء إذا وجدت
                    if (preg_match('/\{"exception":"\[object\]\s*\(([^\(]+)\(code:\s*\d+\):\s*([^\)]+)\s+at\s+([^:]+):(\d+)\)/', $log, $exceptionMatches)) {
                        $exceptionType = $exceptionMatches[1];
                        $exceptionMessage = $exceptionMatches[2];
                        $file = $exceptionMatches[3];
                        $line = $exceptionMatches[4];
                        
                        // استخدام رسالة الاستثناء إذا كانت أكثر وضوحاً
                        if (!empty($exceptionMessage) && strlen($exceptionMessage) > 5) {
                            $message = $exceptionMessage;
                        }
                    }
                    // استخراج معلومات الملف والسطر من أنماط أخرى
                    elseif (preg_match('/at\s+([^:]+):(\d+)/', $log, $fileMatches)) {
                        $file = $fileMatches[1];
                        $line = $fileMatches[2];
                    } 
                    elseif (preg_match('/in\s+([^\s]+)\s+line\s+(\d+)/', $log, $fileMatches)) {
                        $file = $fileMatches[1];
                        $line = $fileMatches[2];
                    }
                    
                    // استخراج معلومات SQL إذا كان الخطأ متعلق بقاعدة البيانات
                    if (strpos($log, 'SQLSTATE') !== false) {
                        if (preg_match('/SQLSTATE\[([^\]]+)\]:\s*([^\(]+)\s*\(([^\)]+)/', $log, $sqlMatches)) {
                            $sqlState = $sqlMatches[1];
                            $sqlError = trim($sqlMatches[2]);
                            $message = $sqlError;
                            
                            // استخراج استعلام SQL إذا وجد
                            if (preg_match('/SQL:\s*([^\)]+)\)/', $log, $sqlQueryMatches)) {
                                $sqlQuery = $sqlQueryMatches[1];
                                $file = 'SQL Query';
                                $line = $sqlState;
                            }
                        }
                    }
                    
                    // استخراج معرف المستخدم إن وجد
                    $userId = null;
                    if (preg_match('/"userId":(\d+)/', $log, $userMatches)) {
                        $userId = $userMatches[1];
                    }
                    
                    // تنظيف الرسالة
                    $message = trim($message);
                    if (empty($message)) {
                        $message = "$type error";
                    }
                    
                    // إنشاء معرف فريد للخطأ
                    $id = md5($timestamp . $type . $message . $file . $line);
                    
                    $errors[] = [
                        'id' => $id,
                        'timestamp' => $timestamp,
                        'type' => ucfirst(strtolower($type)),
                        'message' => $message,
                        'file' => $file,
                        'line' => $line,
                        'user_id' => $userId
                    ];
                    
                    // اقتصار على أحدث 20 خطأ لتحسين الأداء
                    if (count($errors) >= 20) {
                        break;
                    }
                }
            }

            // ترتيب الأخطاء حسب الوقت (الأحدث أولاً)
            usort($errors, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });

            // حساب الاتجاه
            $now = time();
            $lastHour = array_filter($errors, function($error) use ($now) {
                return strtotime($error['timestamp']) > ($now - 3600);
            });
            $previousHour = array_filter($errors, function($error) use ($now) {
                return strtotime($error['timestamp']) <= ($now - 3600) && 
                       strtotime($error['timestamp']) > ($now - 7200);
            });

            $trend = count($lastHour) - count($previousHour);

            // إذا لم يتم العثور على أي أخطاء، قم بإنشاء رسالة توضيحية
            if (empty($errors) && $errorCount === 0) {
                // التحقق من وجود محتوى في ملف السجل
                $logContent = file_get_contents($logFile);
                if (empty(trim($logContent))) {
                    $errors[] = [
                        'id' => md5('empty_log'),
                        'timestamp' => date('Y-m-d H:i:s'),
                        'type' => 'Info',
                        'message' => 'ملف السجل فارغ. لم يتم تسجيل أي أخطاء بعد.',
                        'file' => 'laravel.log',
                        'line' => '0',
                        'user_id' => null
                    ];
                } else {
                    $errors[] = [
                        'id' => md5('no_errors_found'),
                        'timestamp' => date('Y-m-d H:i:s'),
                        'type' => 'Info',
                        'message' => 'لم يتم العثور على أخطاء في ملف السجل. قد يكون تنسيق ملف السجل غير متوافق مع نمط البحث.',
                        'file' => 'laravel.log',
                        'line' => '0',
                        'user_id' => null
                    ];
                }
            }

            return [
                'count' => $errorCount,
                'trend' => $trend,
                'recent' => array_slice($errors, 0, 10) // Return only the 10 most recent errors
            ];

        } catch (\Exception $e) {
            Log::error('Error in ErrorLogService::getRecentErrors: ' . $e->getMessage());
            return [
                'count' => 0,
                'trend' => 0,
                'recent' => [[
                    'id' => md5('error_service_exception'),
                    'timestamp' => date('Y-m-d H:i:s'),
                    'type' => 'Error',
                    'message' => 'حدث خطأ أثناء قراءة ملف السجل: ' . $e->getMessage(),
                    'file' => 'ErrorLogService.php',
                    'line' => '0',
                    'user_id' => null
                ]]
            ];
        }
    }

    /**
     * حذف خطأ من ملف السجل باستخدام معرفه
     * 
     * @param string $errorId
     * @return bool
     */
    public function deleteError($errorId)
    {
        try {
            // طريقة مباشرة وبسيطة للحذف - تعمل بشكل أفضل
            $logFile = storage_path('logs/laravel.log');
            if (!File::exists($logFile)) {
                return false;
            }
            
            // قراءة محتوى الملف كاملاً
            $content = file_get_contents($logFile);
            
            // حفظ نسخة احتياطية من الملف قبل التعديل
            File::put($logFile . '.bak', $content);
            
            // تقسيم المحتوى إلى سطور
            $lines = explode("\n", $content);
            $newLines = [];
            $deleted = false;
            
            // البحث عن السطر المطلوب حذفه
            foreach ($lines as $line) {
                // تخطي السطور الفارغة
                if (empty(trim($line))) {
                    $newLines[] = $line;
                    continue;
                }
                
                // البحث عن معرف الخطأ في السطر
                if (strpos($line, $errorId) !== false) {
                    $deleted = true;
                    continue; // تخطي هذا السطر (حذفه)
                }
                
                // إضافة السطر إلى المصفوفة الجديدة
                $newLines[] = $line;
            }
            
            // كتابة المحتوى الجديد إلى الملف
            $newContent = implode("\n", $newLines);
            File::put($logFile, $newContent);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error deleting error log: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * مسح جميع سجلات الأخطاء
     * 
     * @return bool
     */
    public function clearAllErrors()
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            
            if (!File::exists($logFile)) {
                return false;
            }
            
            // إنشاء ملف سجل جديد فارغ
            File::put($logFile, '');
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error clearing all error logs: ' . $e->getMessage());
            return false;
        }
    }
}
