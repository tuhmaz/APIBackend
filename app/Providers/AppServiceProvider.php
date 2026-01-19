<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;
use Laravel\Passport\Passport;
use App\Models\Article;
use App\Observers\ArticleObserver;
use App\Models\Post;
use App\Observers\PostObserver;
use App\Models\News;
use App\Observers\NewsObserver;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use Illuminate\Pagination\Paginator;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    // ...

    public function boot(): void
    {
        // تفعيل HTTPS لجميع الروابط في بيئة الإنتاج
        if (App::environment('production') && Config::get('secure-connections.force_https', true)) {
            URL::forceScheme('https');

            // تعيين ملفات الكوكيز لتكون آمنة في بيئة الإنتاج
            Config::set('session.secure', true);
            Config::set('session.http_only', true);
            Config::set('session.same_site', 'lax');

            // تعيين ملفات الكوكيز الخاصة بالمصادقة لتكون آمنة
            Config::set('sanctum.middleware.encrypt_cookies', true);
        }

        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $frontendBase = rtrim(config('app.frontend_url', config('app.url')), '/');
            $email = $notifiable->getEmailForPasswordReset();
            return $frontendBase . '/reset-password/' . $token . '?email=' . urlencode($email);
        });

        // Configure VarDumper
        VarDumper::setHandler(function ($var) {
            $dumper = new HtmlDumper();
            $dumper->setStyles([
                'default' => 'background-color:#fff; color:#222; line-height:1.2em; font-weight:normal; font:12px Monaco, Consolas, monospace; word-wrap: break-word; white-space: pre-wrap; position:relative; z-index:100000',
                'search-input' => 'id:dump-search; name:dump-search;'
            ]);
            $cloner = new VarCloner();
            $dumper->dump($cloner->cloneVar($var));
        });

        // تجنب الوصول إلى قاعدة البيانات خلال عملية الترحيل
        if ($this->app->runningInConsole() &&
            (strpos($this->app['request']->server('argv')[1] ?? '', 'migrate') !== false)) {
            return;
        }

        try {
            // Use Bootstrap 5 pagination views across the app
            Paginator::useBootstrapFive();
            // Load dynamic settings from database and merge into config
            if (Schema::hasTable('settings')) {
                // Cache settings for 1 hour to reduce database queries
                $dbSettings = \Illuminate\Support\Facades\Cache::remember('app_settings', 3600, function () {
                    return Setting::pluck('value','key')->toArray();
                });
                Config::set('settings', array_merge(config('settings', []), $dbSettings));
                // Set application locale from session or settings
                $locale = session('locale') ?? config('settings.site_language');
                if (in_array($locale, ['en', 'ar'])) {
                    app()->setLocale($locale);
                }
            }

            // Load Passport keys (only if package is installed and keys path exists)
            if (class_exists(\Laravel\Passport\Passport::class)) {
                $passportKeysPath = base_path('app/secrets/oauth');
                if (is_dir($passportKeysPath)) {
                    \Laravel\Passport\Passport::loadKeysFrom($passportKeysPath);
                }
            }

            // Custom Vite styles
            Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
                if ($src !== null) {
                    return [
                        'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' :
                                  (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
                    ];
                }
                return [];
            });

            // Share dynamic settings with all views
            View::share('siteSettings', config('settings'));

            // Register observers
            Article::observe(ArticleObserver::class);
            Post::observe(PostObserver::class);
            News::observe(NewsObserver::class);

        } catch (\Exception $e) {
            // Log the error but don't stop the application
            Log::error('Error in AppServiceProvider boot: ' . $e->getMessage());
        }
    }

    // ...
}
