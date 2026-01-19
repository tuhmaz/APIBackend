<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Config;

class CompressResponse
{
  /**
   * Handle an incoming request.
   */
  public function handle(Request $request, Closure $next): Response
  {
    // التحقق مما إذا كان يجب ضغط الطلب
    if ($this->shouldCompress($request)) {
      $this->configureCompression($request);
    }

    $response = $next($request);

    // التحقق من ضغط المحتوى
    if (!$this->isCompressibleContent($response)) {
      return $response;
    }

    // إضافة ETag فقط. باقي الرؤوس تُدار في Middleware متخصصة أخرى
    $this->addETag($response);

    return $response;
  }

  /**
   * تحديد ما إذا كان يجب ضغط الطلب.
   */
  protected function shouldCompress(Request $request): bool
  {
    if (!Config::get('app.compression.enabled', true)) {
      return false;
    }

    // لا تضغط الطلبات غير GET
    if (!$request->isMethod('GET')) {
      return false;
    }

    // لا تضغط طلبات AJAX أو الطلبات التي تحتوي على رأس X-No-Compression
    if ($request->ajax() || $request->headers->has('X-No-Compression')) {
      return false;
    }

    // لا تضغط الطلبات الصغيرة جدًا
    if ($request->header('Content-Length') && (int)$request->header('Content-Length') < Config::get('app.compression.threshold', 1024)) {
      return false;
    }

    // التحقق من دعم المتصفح للضغط
    $acceptEncoding = $request->header('Accept-Encoding', '');
    return str_contains($acceptEncoding, 'gzip') ||
      str_contains($acceptEncoding, 'deflate') ||
      str_contains($acceptEncoding, 'br');
  }

  /**
   * تكوين إعدادات الضغط بناءً على قدرات المتصفح.
   */
  protected function configureCompression(Request $request): void
  {
    $acceptEncoding = $request->header('Accept-Encoding', '');
    $level = Config::get('app.compression.level', 7); // زيادة مستوى الضغط الافتراضي من 6 إلى 7

    // تحديد أفضل طريقة ضغط متاحة
    if (str_contains($acceptEncoding, 'br') && function_exists('brotli_compress')) {
      // استخدام Brotli إذا كان مدعومًا (أكثر كفاءة من gzip)
      ini_set('brotli.output_compression', 'On');
      ini_set('brotli.output_compression_level', $level);
    } else {
      // استخدام zlib (gzip/deflate) كخيار احتياطي
      // Verificar si ya está habilitada la compresión zlib a nivel de PHP
      if (ini_get('zlib.output_compression') !== 'On') {
        // Si no está habilitada, usamos ob_gzhandler que nos da más control
        if (Config::get('app.compression.handler', 'ob_gzhandler') === 'ob_gzhandler') {
          ob_start('ob_gzhandler');
        } else {
          // Alternativamente, activar la compresión zlib
          ini_set('zlib.output_compression', 'On');
          ini_set('zlib.output_compression_level', $level);
        }
      } else {
        // Si ya está habilitada, solo configuramos el nivel
        ini_set('zlib.output_compression_level', $level);
      }
    }
  }

  /**
   * تحديد ما إذا كان المحتوى قابل للضغط.
   */
  protected function isCompressibleContent(Response $response): bool
  {
    // التحقق من حجم المحتوى
    $content = $response->getContent();
    if (!$content || strlen($content) < Config::get('app.compression.threshold', 1024)) {
      return false;
    }

    // التحقق من نوع المحتوى
    $contentType = $response->headers->get('Content-Type', '');
    $allowedTypes = Config::get('app.compression.types', [
      'text/html', 'text/plain', 'text/css', 'text/javascript',
      'application/javascript', 'application/json', 'application/xml',
      'image/svg+xml'
    ]);

    // التحقق من تطابق نوع المحتوى مع الأنواع المسموح بها
    foreach ($allowedTypes as $type) {
      if (str_starts_with($contentType, $type)) {
        return true;
      }
    }

    return false;
  }

  /**
   * إضافة رؤوس التخزين المؤقت.
   */
  // إزالة منطق Cache-Control: سيُدار عبر Middleware مخصص مثل CacheControlMiddleware

  /**
   * إضافة رؤوس الأمان.
   */
  // إزالة رؤوس الأمان لتفادي التعارض مع SecurityHeaders

  /**
   * تحقق مما إذا كان يجب تمكين HSTS.
   */
  // إزالة تمكين HSTS من هنا

  /**
   * إضافة رأس HSTS.
   */
  // إزالة تعيين HSTS من هنا

  /**
   * إضافة ETag للتحقق من التغييرات.
   */
  protected function addETag(Response $response): void
  {
    $content = $response->getContent();
    if ($content) {
      $response->headers->set('ETag', '"' . md5($content) . '"');
    }
  }

  /**
   * إضافة رؤوس تحسين الأداء.
   */
  // إزالة رؤوس الأداء لتجنّب التعارض مع SecurityHeaders/PerformanceOptimizer
}
