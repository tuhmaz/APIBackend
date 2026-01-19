<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class HttpsProtocol
{
    /**
     * توجيه جميع الطلبات إلى HTTPS في بيئة الإنتاج
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // تطبيق HTTPS فقط في بيئة الإنتاج وعندما يكون force_https مفعل
        if (!$request->secure() && App::environment('production') && Config::get('secure-connections.force_https', true)) {
            // إعادة توجيه الطلب إلى HTTPS
            return redirect()->secure($request->getRequestUri());
        }

        // تمرير الطلب دون إضافة رؤوس أمان هنا (ستُدار بالكامل عبر SecurityHeaders)
        return $next($request);
    }
}
