<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class IpSessionGuard
{
    /**
     * ربط الجلسة بعنوان IP للمستخدم لمنع سرقة الجلسات
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // التحقق مما إذا كان المستخدم مسجل الدخول
        if (Auth::check()) {
            // الحصول على عنوان IP المخزن في الجلسة
            $sessionIp = $request->session()->get('auth_ip');
            
            // الحصول على عنوان IP الحالي
            $currentIp = $request->ip();
            
            // إذا لم يكن هناك عنوان IP مخزن في الجلسة، قم بتخزينه
            if (!$sessionIp) {
                $request->session()->put('auth_ip', $currentIp);
            } 
            // إذا كان عنوان IP الحالي مختلفًا عن المخزن، قم بتسجيل الخروج
            else if ($sessionIp !== $currentIp) {
                Log::warning('تم اكتشاف تغيير في عنوان IP، تم تسجيل الخروج من الجلسة', [
                    'user_id' => Auth::id(),
                    'session_ip' => $sessionIp,
                    'current_ip' => $currentIp
                ]);
                
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return redirect()->route('login')
                    ->with('error', 'تم تسجيل خروجك لأسباب أمنية. يرجى تسجيل الدخول مرة أخرى.');
            }
            
            // إعادة إنشاء معرف الجلسة بشكل دوري (كل 30 دقيقة)
            if (!$request->session()->has('last_session_regenerate') || 
                time() - $request->session()->get('last_session_regenerate') > 1800) {
                
                $request->session()->regenerate();
                $request->session()->put('last_session_regenerate', time());
            }
        }
        
        return $next($request);
    }
}
