<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EnsureGoogleLoginPersists
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        // التحقق من أن المستخدم مسجل دخوله بعد Google login
        if (Auth::check() && $request->session()->has('google_login_completed')) {
            // تحديث وقت آخر نشاط
            $user = Auth::user();
            $user->update([
                'last_activity' => now(),
                'is_online' => true,
                'status' => 'online'
            ]);
            
            Log::info('Google login session maintained', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        }

        return $next($request);
    }
}
