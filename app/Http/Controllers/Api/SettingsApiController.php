<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Support\AdSnippetSanitizer;
use App\Services\SmtpTestService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Resources\BaseResource;

class SettingsApiController extends Controller
{
    /**
     * Display all settings
     */
    public function getAll()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        // Ensure ad keys exist even if not in DB
        $adKeys = [
            'google_ads_desktop_download',
            'google_ads_desktop_download_2',
            'google_ads_mobile_download',
            'google_ads_mobile_download_2',
            'google_ads_desktop_home',
            'google_ads_desktop_home_2',
            'google_ads_mobile_home',
            'google_ads_mobile_home_2',
            'google_ads_desktop_classes',
            'google_ads_desktop_classes_2',
            'google_ads_desktop_subject',
            'google_ads_desktop_subject_2',
            'google_ads_desktop_article',
            'google_ads_desktop_article_2',
            'google_ads_desktop_news',
            'google_ads_desktop_news_2',
            'google_ads_mobile_classes',
            'google_ads_mobile_classes_2',
            'google_ads_mobile_subject',
            'google_ads_mobile_subject_2',
            'google_ads_mobile_article',
            'google_ads_mobile_article_2',
            'google_ads_mobile_news',
            'google_ads_mobile_news_2',
        ];

        foreach ($adKeys as $key) {
            if (!isset($settings[$key])) {
                $settings[$key] = config("settings.{$key}", '');
            }
        }

        return new BaseResource(['data' => $settings]);
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        try {
            $data = $request->except(['_token', '_method']);
            $envUpdates = [];
            $pendingEnvUpdates = [];
            $shouldClearConfig = false;

            // 1. Handle File Uploads
            foreach ($data as $key => $value) {
                if ($request->hasFile($key)) {
                    $file = $request->file($key);

                    // Validate file
                    if (!in_array($file->getClientMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'])) {
                        return (new BaseResource(['message' => 'نوع الملف غير مسموح به. يجب أن يكون الملف صورة']))
                            ->response($request)
                            ->setStatusCode(422);
                    }

                    if ($file->getSize() > 2048 * 1024) { // 2MB
                        return (new BaseResource(['message' => 'حجم الملف كبير جداً. يجب أن لا يتجاوز 2 ميجابايت']))
                            ->response($request)
                            ->setStatusCode(422);
                    }

                    // Delete old file if exists
                    $oldValue = Setting::get($key);
                    if ($oldValue && Storage::disk('public')->exists($oldValue)) {
                        Storage::disk('public')->delete($oldValue);
                    }

                    // Store new file
                    $path = $file->store('settings', 'public');
                    $data[$key] = $path;
                }
            }

            // 2. Handle reCAPTCHA settings (Environment Variables)
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

            if (!empty($envUpdates)) {
                $pendingEnvUpdates = array_merge($pendingEnvUpdates, $envUpdates);
                $shouldClearConfig = true;
            }
            $envUpdates = [];

            // 3. Handle AdSense Base64 Decoding
            foreach ($data as $key => $value) {
                if (is_string($key) && str_starts_with($key, 'google_ads_') && is_string($value)) {
                    $trimmed = trim($value);
                    if (str_starts_with($trimmed, '__B64__')) {
                        $encoded = substr($trimmed, 7);
                        $decoded = base64_decode($encoded, true);
                        if ($decoded !== false) {
                            $data[$key] = $decoded;
                        }
                    }
                }
            }

            $adsenseClient = $data['adsense_client'] ?? Setting::get('adsense_client');

            // 4. Update Settings Loop
            foreach ($data as $key => $value) {
                // AdSense Sanitization
                if (is_string($key) && str_starts_with($key, 'google_ads_')) {
                    if (!empty(trim((string) $value))) {
                        try {
                            $value = AdSnippetSanitizer::sanitize($value, $adsenseClient, $key);
                            $data[$key] = $value;
                        } catch (ValidationException $e) {
                            Log::warning('Ad snippet validation failed', ['key' => $key]);
                            throw $e;
                        }
                    } else {
                        $data[$key] = ''; // Allow clearing
                    }
                }

                // Update DB
                if ($value !== null) {
                    Setting::updateOrCreate(
                        ['key' => $key],
                        ['value' => $value]
                    );
                }

                // Language Switch
                if ($key === 'site_language' && in_array($value, ['en', 'ar'])) {
                    app()->setLocale($value);
                    session(['locale' => $value]);
                }

                // Check for ENV updates mapping
                $envKey = $this->getEnvKey($key);
                if ($envKey) {
                    $envUpdates[$envKey] = $value;
                }
            }

            if (!empty($envUpdates)) {
                $pendingEnvUpdates = array_merge($pendingEnvUpdates, $envUpdates);
                $shouldClearConfig = true;
            }

            // 5. Handle robots.txt
            if (isset($data['robots_txt'])) {
                $this->updateRobotsTxt($data['robots_txt']);
            }

            // 6. Check Mail Updates
            if ($this->mailSettingsWereUpdated($data)) {
                $shouldClearConfig = true;
            }

            // 7. Apply Deferred ENV Updates and Config Clear
            if ($shouldClearConfig || !empty($pendingEnvUpdates)) {
                $updates = $pendingEnvUpdates;
                dispatch(function () use ($updates) {
                    if (!empty($updates)) {
                        $this->updateEnvFile($updates);
                    }
                    Artisan::call('config:clear');
                })->afterResponse();
            }

            // 8. Clear frontend settings cache
            Cache::forget('front_settings');
            Cache::forget('front_public_settings');

            return new BaseResource([
                'message' => 'تم تحديث الإعدادات بنجاح',
                'data' => $data
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'فشل في التحقق من صحة البيانات',
                'errors' => $e->errors(),
                'success' => false
            ], 422);

        } catch (\Exception $e) {
            Log::error('Settings API Update Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ في تحديث الإعدادات',
                'error' => $e->getMessage(), // Dev only, maybe hide in prod
                'success' => false
            ], 500);
        }
    }

    /**
     * Test SMTP Connection
     */
    public function testSmtp(Request $request, SmtpTestService $smtp)
    {
        try {
            // If request has SMTP config, use it
            $config = null;
            if ($request->has('host')) {
                $config = [
                    'host' => $request->input('host'),
                    'port' => $request->input('port'),
                    'username' => $request->input('username'),
                    'password' => $request->input('password'),
                    'encryption' => $request->input('encryption') ?: null,
                    'timeout' => 30
                ];
            }

            $result = $smtp->testConnection($config);

            return (new BaseResource(['result' => $result]))
                ->response($request)
                ->setStatusCode($result['success'] ? 200 : 500);
        } catch (\Exception $e) {
             return (new BaseResource([
                'message' => 'SMTP Test Failed',
                'error' => $e->getMessage()
            ]))->response($request)->setStatusCode(500);
        }
    }

    /**
     * Send Test Email
     */
    public function sendTestEmail(Request $request, SmtpTestService $smtp)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            // If request has SMTP config, use it to configure mailer dynamically
            if ($request->has('host')) {
                $mailConfig = [
                    'transport' => 'smtp',
                    'host' => $request->input('host'),
                    'port' => $request->input('port'),
                    'encryption' => $request->input('encryption') ?: null,
                    'username' => $request->input('username'),
                    'password' => $request->input('password'),
                    'timeout' => 30,
                    'local_domain' => env('MAIL_EHLO_DOMAIN', 'alemancenter.com'),
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ];

                config(['mail.mailers.smtp' => $mailConfig]);
                
                if ($request->has('from_address')) {
                    config(['mail.from.address' => $request->input('from_address')]);
                }
                if ($request->has('from_name')) {
                    config(['mail.from.name' => $request->input('from_name')]);
                }
            } else {
                // Otherwise use saved settings from DB
                $this->configureMailSettings();
            }

            // Also test connection first with these settings
            $connConfig = null;
            if ($request->has('host')) {
                $connConfig = [
                    'host' => $request->input('host'),
                    'port' => $request->input('port'),
                    'username' => $request->input('username'),
                    'password' => $request->input('password'),
                    'encryption' => $request->input('encryption') ?: null,
                    'timeout' => 30
                ];
            }
            
            $conn = $smtp->testConnection($connConfig);
            if (!$conn['success']) {
                return (new BaseResource(['result' => $conn]))
                    ->response($request)
                    ->setStatusCode(500);
            }

            Mail::raw('اختبار إعدادات SMTP - ' . config('app.name'), function ($msg) use ($request) {
                $msg->to($request->email)
                    ->subject('SMTP Test Email');
            });

            return new BaseResource(['message' => 'تم إرسال البريد بنجاح']);

        } catch (\Exception $e) {
            Log::error('SMTP Test Email Error: ' . $e->getMessage());
            return (new BaseResource([
                'message' => 'فشل إرسال البريد',
                'error' => $e->getMessage()
            ]))->response($request)->setStatusCode(500);
        }
    }

    /**
     * Update robots.txt (Standalone endpoint if needed)
     */
    public function updateRobots(Request $request)
    {
        $request->validate(['content' => 'required|string']);
        $this->updateRobotsTxt($request->content);
        return new BaseResource(['message' => 'تم تحديث Robots.txt']);
    }

    // ================= HELPER METHODS =================

    protected function getEnvKey($key)
    {
        $mappings = [
            'site_name' => 'APP_NAME',
            'site_url' => 'APP_URL',
            'site_email' => 'ADMIN_EMAIL',
            'mail_mailer' => 'MAIL_MAILER',
            'mail_host' => 'MAIL_HOST',
            'mail_port' => 'MAIL_PORT',
            'mail_username' => 'MAIL_USERNAME',
            'mail_password' => 'MAIL_PASSWORD',
            'mail_encryption' => 'MAIL_ENCRYPTION',
            'mail_from_address' => 'MAIL_FROM_ADDRESS',
            'mail_from_name' => 'MAIL_FROM_NAME',
            'google_analytics_id' => 'GOOGLE_ANALYTICS_ID',
            'facebook_pixel_id' => 'FACEBOOK_PIXEL_ID',
        ];
        return $mappings[$key] ?? null;
    }

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

    protected function updateRobotsTxt($content)
    {
        file_put_contents(public_path('robots.txt'), $content);
    }

    private function updateEnvFile($updates)
    {
        if (empty($updates)) return;

        $envFile = base_path('.env');
        $content = file_exists($envFile) ? file_get_contents($envFile) : '';

        foreach ($updates as $key => $value) {
            $key = strtoupper($key);
            $value = str_replace('"', '\"', (string) $value);
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
}
