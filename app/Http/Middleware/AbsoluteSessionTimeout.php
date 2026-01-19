<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AbsoluteSessionTimeout
{
    /**
     * تنفيذ مهلة مطلقة للجلسة بغض النظر عن نشاط المستخدم
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int|null  $timeout  المهلة بالدقائق
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $timeout = null)
    {
        // التحقق مما إذا كان المستخدم مسجل الدخول
        if (Auth::check()) {
            // استخدام المهلة المحددة أو القيمة الافتراضية (8 ساعات)
            $timeout = $timeout ? (int) $timeout : 480;
            
            // الحصول على وقت بدء الجلسة
            $sessionStartTime = $request->session()->get('absolute_session_start_time');
            
            // إذا لم يكن وقت بدء الجلسة موجودًا، قم بتعيينه الآن
            if (!$sessionStartTime) {
                $request->session()->put('absolute_session_start_time', Carbon::now()->timestamp);
            } else {
                // التحقق مما إذا كانت الجلسة قد تجاوزت المهلة المطلقة
                $sessionStartTime = Carbon::createFromTimestamp($sessionStartTime);
                $currentTime = Carbon::now();
                
                // إذا تجاوزت الجلسة المهلة المطلقة، قم بتسجيل الخروج
                if ($currentTime->diffInMinutes($sessionStartTime) >= $timeout) {
                    Log::info('تم تسجيل الخروج بسبب تجاوز المهلة المطلقة للجلسة', [
                        'user_id' => Auth::id(),
                        'session_duration' => $currentTime->diffInMinutes($sessionStartTime),
                        'timeout' => $timeout
                    ]);
                    
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    return redirect()->route('login')
                        ->with('info', 'انتهت صلاحية جلستك. يرجى تسجيل الدخول مرة أخرى.');
                }
            }
        }
        
        return $next($request);
    }
}
