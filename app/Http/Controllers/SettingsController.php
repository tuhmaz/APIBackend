<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Support\AdSnippetSanitizer;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\SmtpTestService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;


class SettingsController extends Controller
{
    /**
     * Display the settings page
     */
    public function index()
    {
        $settings = [
            // General Settings
            'site_name' => Setting::get('site_name', config('settings.site_name')),
            'site_email' => Setting::get('site_email', config('settings.admin_email')),
            'site_description' => Setting::get('site_description', config('settings.site_description')),
            'site_logo' => Setting::get('site_logo', config('settings.site_logo')),
            'site_favicon' => Setting::get('site_favicon', config('settings.site_favicon')),
            'site_language' => Setting::get('site_language', config('settings.site_language')),
            'timezone' => Setting::get('timezone', config('settings.timezone')),

            // Appearance Settings
            'primary_color' => Setting::get('primary_color', '#696cff'),
            'secondary_color' => Setting::get('secondary_color', '#8592a3'),

            // SEO Settings
            'meta_title' => Setting::get('meta_title', config('settings.meta_title')),
            'meta_description' => Setting::get('meta_description', config('settings.meta_description')),
            'meta_keywords' => Setting::get('meta_keywords', config('settings.meta_keywords')),
            'robots_txt' => Setting::get('robots_txt', config('settings.robots_txt')),
            'sitemap_url' => Setting::get('sitemap_url', config('settings.sitemap_url')),
            'google_analytics_id' => Setting::get('google_analytics_id', config('settings.google_analytics_id')),
            'facebook_pixel_id' => Setting::get('facebook_pixel_id', config('settings.facebook_pixel_id')),
            'canonical_url' => Setting::get('canonical_url', config('settings.canonical_url')),

            // Ads Global
            'adsense_client' => Setting::get('adsense_client', ''),

            // Contact Settings
            'contact_email' => Setting::get('contact_email', config('settings.admin_email')),
            'contact_phone' => Setting::get('contact_phone', ''),
            'contact_address' => Setting::get('contact_address', ''),
            'social_facebook' => Setting::get('social_facebook', config('settings.facebook')),
            'social_twitter' => Setting::get('social_twitter', config('settings.twitter')),
            'social_linkedin' => Setting::get('social_linkedin', config('settings.linkedin')),
            'social_whatsapp' => Setting::get('social_whatsapp', config('settings.whatsapp')),
            'social_tiktok' => Setting::get('social_tiktok', config('settings.tiktok')),

            // Mail Settings
            'mail_mailer' => Setting::get('mail_mailer', config('settings.mail_mailer')),
            'mail_host' => Setting::get('mail_host', config('settings.mail_host')),
            'mail_port' => Setting::get('mail_port', config('settings.mail_port')),
            'mail_username' => Setting::get('mail_username', config('settings.mail_username')),
            'mail_password' => Setting::get('mail_password', config('settings.mail_password')),
            'mail_encryption' => Setting::get('mail_encryption', config('settings.mail_encryption')),
            'mail_from_address' => Setting::get('mail_from_address', config('settings.mail_from_address')),
            'mail_from_name' => Setting::get('mail_from_name', config('settings.mail_from_name')),

            // Notification Settings
            'notification_email' => Setting::get('notification_email', config('settings.notification_email')),
            'notification_sms' => Setting::get('notification_sms', config('settings.notification_sms')),
            'notification_push' => Setting::get('notification_push', config('settings.notification_push')),

            // Security Settings
            'two_factor_auth' => Setting::get('two_factor_auth', config('settings.two_factor_auth')),
            'auto_lock_time' => Setting::get('auto_lock_time', config('settings.auto_lock_time')),

            // ASD Settings
            'google_ads_desktop_home' => Setting::get('google_ads_desktop_home', config('settings.google_ads_desktop_home')),
            'google_ads_desktop_home_2' => Setting::get('google_ads_desktop_home_2', config('settings.google_ads_desktop_home_2')),
            'google_ads_mobile_home' => Setting::get('google_ads_mobile_home', config('settings.google_ads_mobile_home')),
            'google_ads_mobile_home_2' => Setting::get('google_ads_mobile_home_2', config('settings.google_ads_mobile_home_2')),
            'google_ads_desktop_classes' => Setting::get('google_ads_desktop_classes', config('settings.google_ads_desktop_classes')),
            'google_ads_desktop_classes_2' => Setting::get('google_ads_desktop_classes_2', config('settings.google_ads_desktop_classes_2')),
            'google_ads_desktop_subject' => Setting::get('google_ads_desktop_subject', config('settings.google_ads_desktop_subject')),
            'google_ads_desktop_subject_2' => Setting::get('google_ads_desktop_subject_2', config('settings.google_ads_desktop_subject_2')),
            'google_ads_desktop_article' => Setting::get('google_ads_desktop_article', config('settings.google_ads_desktop_article')),
            'google_ads_desktop_article_2' => Setting::get('google_ads_desktop_article_2', config('settings.google_ads_desktop_article_2')),
            'google_ads_desktop_news' => Setting::get('google_ads_desktop_news', config('settings.google_ads_desktop_news')),
            'google_ads_desktop_news_2' => Setting::get('google_ads_desktop_news_2', config('settings.google_ads_desktop_news_2')),
            'google_ads_desktop_download' => Setting::get('google_ads_desktop_download', config('settings.google_ads_desktop_download')),
            'google_ads_desktop_download_2' => Setting::get('google_ads_desktop_download_2', config('settings.google_ads_desktop_download_2')),
            'google_ads_mobile_classes' => Setting::get('google_ads_mobile_classes', config('settings.google_ads_mobile_classes')),
            'google_ads_mobile_classes_2' => Setting::get('google_ads_mobile_classes_2', config('settings.google_ads_mobile_classes_2')),
            'google_ads_mobile_subject' => Setting::get('google_ads_mobile_subject', config('settings.google_ads_mobile_subject')),
            'google_ads_mobile_subject_2' => Setting::get('google_ads_mobile_subject_2', config('settings.google_ads_mobile_subject_2')),
            'google_ads_mobile_article' => Setting::get('google_ads_mobile_article', config('settings.google_ads_mobile_article')),
            'google_ads_mobile_article_2' => Setting::get('google_ads_mobile_article_2', config('settings.google_ads_mobile_article_2')),
            'google_ads_mobile_news' => Setting::get('google_ads_mobile_news', config('settings.google_ads_mobile_news')),
            'google_ads_mobile_news_2' => Setting::get('google_ads_mobile_news_2', config('settings.google_ads_mobile_news_2')),
            'google_ads_mobile_download' => Setting::get('google_ads_mobile_download', config('settings.google_ads_mobile_download')),
            'google_ads_mobile_download_2' => Setting::get('google_ads_mobile_download_2', config('settings.google_ads_mobile_download_2')),
            
            // reCAPTCHA (NoCaptcha) Settings
            'recaptcha_site_key' => Setting::get('recaptcha_site_key', config('captcha.sitekey')),
            'recaptcha_secret_key' => Setting::get('recaptcha_secret_key', config('captcha.secret')),
        ];

        return view('content.dashboard.settings.index', compact('settings'));
    }

    /**
     * Update the settings
     */
    public function update(Request $request)
    {
        try {
            $data = $request->except('_token', '_method');
            $envUpdates = [];
            $pendingEnvUpdates = [];
            $isAjax = $request->ajax() || $request->wantsJson();
            $shouldClearConfig = false;

            // Handle file uploads first
            foreach ($data as $key => $value) {
                if ($request->hasFile($key)) {
                    $file = $request->file($key);

                    // Validate file
                    if (!in_array($file->getClientMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'])) {
                        throw new \Exception(__('نوع الملف غير مسموح به. يجب أن يكون الملف صورة'));
                    }

                    if ($file->getSize() > 2048 * 1024) { // 2MB
                        throw new \Exception(__('حجم الملف كبير جداً. يجب أن لا يتجاوز 2 ميجابايت'));
                    }

                    // Delete old file if exists
                    $oldValue = Setting::get($key);
                    if ($oldValue && Storage::disk('public')->exists($oldValue)) {
                        Storage::disk('public')->delete($oldValue);
                    }

                    // Store new file
                    $value = $file->store('settings', 'public');
                    $data[$key] = $value;
                }
            }

            // Handle reCAPTCHA settings
            if (isset($data['recaptcha_site_key'])) {
                $envUpdates['NOCAPTCHA_SITEKEY'] = $data['recaptcha_site_key'];
                Setting::updateOrCreate(
                    ['key' => 'recaptcha_site_key'],
                    ['value' => $data['recaptcha_site_key']]
                );
                unset($data['recaptcha_site_key']);
            }

            if (isset($data['recaptcha_secret_key'])) {
                $envUpdates['NOCAPTCHA_SECRET'] = $data['recaptcha_secret_key'];
                Setting::updateOrCreate(
                    ['key' => 'recaptcha_secret_key'],
                    ['value' => $data['recaptcha_secret_key']]
                );
                unset($data['recaptcha_secret_key']);
            }

            // Collect .env updates (reCAPTCHA) but do not write yet for AJAX
            if (!empty($envUpdates)) {
                $pendingEnvUpdates = array_merge($pendingEnvUpdates, $envUpdates);
                $shouldClearConfig = true;
            }
            $envUpdates = [];

            // Decode Base64-encoded AdSense snippet fields (client-side encodes with __B64__ prefix)
            foreach ($data as $key => $value) {
                if (is_string($key) && str_starts_with($key, 'google_ads_') && is_string($value)) {
                    $trimmed = trim($value);
                    if (str_starts_with($trimmed, '__B64__')) {
                        $encoded = substr($trimmed, 7);
                        // strict mode: returns false on invalid base64
                        $decoded = base64_decode($encoded, true);
                        if ($decoded !== false) {
                            $data[$key] = $decoded;
                        }
                    }
                }
            }

            $adsenseClient = $data['adsense_client'] ?? Setting::get('adsense_client', config('settings.adsense_client'));

            // Update all other settings
            foreach ($data as $key => $value) {
                if (is_string($key) && str_starts_with($key, 'google_ads_')) {
                    // Skip validation if the snippet is empty (allow clearing ads)
                    if (!empty(trim((string) $value))) {
                        try {
                            $value = AdSnippetSanitizer::sanitize($value, $adsenseClient, $key);
                            $data[$key] = $value;
                        } catch (ValidationException $e) {
                            Log::warning('Ad snippet validation failed', [
                                'setting_key' => $key,
                                'trimmed_snippet' => Str::limit(trim((string) $value), 120),
                            ]);
                            throw $e;
                        }
                    } else {
                        // Allow empty value (for clearing ad slots)
                        $data[$key] = '';
                    }
                }

                // Update database setting
                Setting::set($key, $value);

                // Handle language change
                if ($key === 'site_language' && in_array($value, ['en', 'ar'])) {
                    app()->setLocale($value);
                    session(['locale' => $value]);
                }

                // Add to env updates if it exists in settings config
                if (config()->has("settings.{$key}")) {
                    $envKey = $this->getEnvKey($key);
                    if ($envKey) {
                        $envUpdates[$envKey] = $value;
                    }
                }
            }

            // Collect .env updates (other mapped settings)
            if (!empty($envUpdates)) {
                $pendingEnvUpdates = array_merge($pendingEnvUpdates, $envUpdates);
                $shouldClearConfig = true;
            }

            if (isset($data['robots_txt'])) {
                $this->updateRobotsTxt($data['robots_txt']);
            }

            // Mark to clear config cache if mail settings were updated
            if ($this->mailSettingsWereUpdated($data)) {
                $shouldClearConfig = true;
            }

            if ($isAjax) {
                // Schedule .env updates and config clear AFTER the response to avoid connection resets
                if ($shouldClearConfig || !empty($pendingEnvUpdates)) {
                    $updates = $pendingEnvUpdates; // capture array for closure
                    dispatch(function () use ($updates) {
                        if (!empty($updates)) {
                            $envFile = base_path('.env');
                            $content = file_exists($envFile) ? file_get_contents($envFile) : '';
                            foreach ($updates as $key => $value) {
                                $key = strtoupper($key);
                                $value = str_replace('"', '"', str_replace('"', '"', (string) $value));
                                if (strpos($value, ' ') !== false) {
                                    $value = '"' . $value . '"';
                                }
                                if (preg_match("/^{$key}=.*/m", $content)) {
                                    $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
                                } else {
                                    $content .= "\n{$key}={$value}";
                                }
                            }
                            file_put_contents($envFile, $content);
                        }
                        Artisan::call('config:clear');
                    })->afterResponse();
                }
                return response()->json([
                    'success' => true,
                    'message' => __('تم تحديث الإعدادات بنجاح')
                ]);
            }

            if ($shouldClearConfig) {
                app()->terminating(function () use ($pendingEnvUpdates) {
                    // Apply any pending env updates for non-AJAX requests
                    if (!empty($pendingEnvUpdates)) {
                        $envFile = base_path('.env');
                        $content = file_exists($envFile) ? file_get_contents($envFile) : '';
                        foreach ($pendingEnvUpdates as $key => $value) {
                            $key = strtoupper($key);
                            $value = str_replace('"', '"', str_replace('"', '"', (string) $value));
                            if (strpos($value, ' ') !== false) {
                                $value = '"' . $value . '"';
                            }
                            if (preg_match("/^{$key}=.*/m", $content)) {
                                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
                            } else {
                                $content .= "\n{$key}={$value}";
                            }
                        }
                        file_put_contents($envFile, $content);
                    }
                    Artisan::call('config:clear');
                });
            }
            return redirect()->back()->with('success', __('تم تحديث الإعدادات بنجاح'));

        } catch (ValidationException $e) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => __('فشل التحقق من صحة بيانات الإعلان'),
                    'errors' => $e->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Settings update error: ' . $e->getMessage());

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => __('خطأ في تحديث الإعدادات: ') . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', __('خطأ في تحديث الإعدادات: ') . $e->getMessage());
        }
    }

    /**
     * Get the corresponding ENV key for a setting
     */
    protected function getEnvKey($key)
    {
        $mappings = [
            // General Settings
            'site_name' => 'APP_NAME',
            'site_url' => 'APP_URL',
            'site_email' => 'ADMIN_EMAIL',

            // Mail Settings
            'mail_mailer' => 'MAIL_MAILER',
            'mail_host' => 'MAIL_HOST',
            'mail_port' => 'MAIL_PORT',
            'mail_username' => 'MAIL_USERNAME',
            'mail_password' => 'MAIL_PASSWORD',
            'mail_encryption' => 'MAIL_ENCRYPTION',
            'mail_from_address' => 'MAIL_FROM_ADDRESS',
            'mail_from_name' => 'MAIL_FROM_NAME',

            // Analytics and Tracking
            'google_analytics_id' => 'GOOGLE_ANALYTICS_ID',
            'facebook_pixel_id' => 'FACEBOOK_PIXEL_ID',
        ];

        return $mappings[$key] ?? null;
    }

    /**
     * Update .env file
     */
    private function updateEnvFile($updates)
    {
        if (empty($updates)) {
            return;
        }

        $envFile = base_path('.env');
        $content = file_get_contents($envFile);

        foreach ($updates as $key => $value) {
            $key = strtoupper($key);

            // Escape special characters in the value
            $value = str_replace('"', '\"', $value);
            if (strpos($value, ' ') !== false) {
                $value = '"' . $value . '"';
            }

            // Update or add the key
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envFile, $content);
    }

    /**
     * Check if mail settings were updated
     */
    protected function mailSettingsWereUpdated($data)
    {
        $mailSettings = [
            'mail_mailer', 'mail_host', 'mail_port', 'mail_username',
            'mail_password', 'mail_encryption', 'mail_from_address', 'mail_from_name'
        ];

        foreach ($mailSettings as $setting) {
            if (array_key_exists($setting, $data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update robots.txt file
     */
    protected function updateRobotsTxt($content)
    {
        try {
            $robotsPath = public_path('robots.txt');
            file_put_contents($robotsPath, $content);
        } catch (\Exception $e) {
            Log::error('Error updating robots.txt: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test SMTP Connection
     */
    public function testSMTPConnection(SmtpTestService $smtpTestService)
    {
        try {
            $result = $smtpTestService->testConnection();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => __('SMTP connection successful'),
                    'details' => $result['details'] ?? null
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'suggestions' => $result['suggestions'] ?? [],
                    'error_code' => $result['error_code'] ?? null
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('SMTP test failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('SMTP Error: Could not connect to SMTP host.') . ' ' . $e->getMessage(),
                'suggestions' => [
                    __('Check SMTP server settings'),
                    __('Verify network connectivity'),
                    __('Check firewall settings'),
                    __('Verify SSL/TLS configuration')
                ]
            ], 500);
        }
    }

    /**
     * Send test email
     */
    public function sendTestEmail(Request $request, SmtpTestService $smtpTestService)
    {
        $request->validate([
            'test_email' => 'required|email'
        ]);

        try {
            $testEmail = $request->input('test_email');

            // First test SMTP connection
            $connectionTest = $smtpTestService->testConnection();
            if (!$connectionTest['success']) {
                return response()->json([
                    'success' => false,
                    'message' => __('SMTP connection failed before sending email: ') . $connectionTest['message'],
                    'suggestions' => $connectionTest['suggestions'] ?? []
                ], 500);
            }

            // Configure mail settings dynamically
            $this->configureMailSettings();

            // Send test email
            Mail::raw(__('This is a test email to verify SMTP settings. If you received this email, your SMTP configuration is working correctly.'), function($message) use ($testEmail) {
                $message->to($testEmail)
                        ->subject(__('SMTP Settings Test - ') . config('app.name'));
            });

            return response()->json([
                'success' => true,
                'message' => __('Test email sent successfully to ') . $testEmail
            ]);

        } catch (\Exception $e) {
            Log::error('Test email failed: ' . $e->getMessage(), [
                'email' => $testEmail ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('SMTP Error: Could not connect to SMTP host. Failed to connect to server') . ' - ' . $e->getMessage(),
                'suggestions' => [
                    __('Verify SMTP credentials'),
                    __('Check email server settings'),
                    __('Ensure SMTP server allows connections'),
                    __('Check spam/firewall settings')
                ]
            ], 500);
        }
    }

    /**
     * Configure mail settings dynamically from database
     */
    private function configureMailSettings()
    {
        $mailConfig = [
            'transport' => 'smtp',
            'host' => Setting::get('mail_host', config('mail.mailers.smtp.host')),
            'port' => Setting::get('mail_port', config('mail.mailers.smtp.port')),
            'encryption' => Setting::get('mail_encryption', config('mail.mailers.smtp.encryption')),
            'username' => Setting::get('mail_username', config('mail.mailers.smtp.username')),
            'password' => Setting::get('mail_password', config('mail.mailers.smtp.password')),
            'timeout' => 30,
            'local_domain' => env('MAIL_EHLO_DOMAIN', 'alemancenter.com'),
            'verify_peer' => false,
            'verify_peer_name' => false,
        ];

        config(['mail.mailers.smtp' => $mailConfig]);
        config(['mail.from.address' => Setting::get('mail_from_address', config('mail.from.address'))]);
        config(['mail.from.name' => Setting::get('mail_from_name', config('mail.from.name'))]);
    }

    /**
     * Get SMTP configuration suggestions
     */
    public function getSmtpSuggestions(SmtpTestService $smtpTestService)
    {
        try {
            $configurations = $smtpTestService->getCommonConfigurations();

            return response()->json([
                'success' => true,
                'configurations' => $configurations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
