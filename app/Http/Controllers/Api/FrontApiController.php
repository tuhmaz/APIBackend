<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;
use App\Models\User;
use App\Mail\ContactFormMail;
use Carbon\Carbon;
use App\Http\Resources\BaseResource;
use App\Http\Resources\Api\UserResource;
use Spatie\Permission\Models\Role;

class FrontApiController extends Controller
{
    /**
     * القائمة البيضاء للإعدادات المسموح بها للعرض العام
     * فقط هذه الإعدادات سيتم إرجاعها للفرونت إند
     */
    protected array $publicSettings = [
        // معلومات الموقع الأساسية
        'site_name',
        'siteName',
        'site_description',
        'site_logo',
        'site_favicon',
        'site_keywords',

        // معلومات التواصل العامة
        'contact_email',
        'contact_phone',
        'contact_address',
        'contact_working_hours',

        // روابط السوشيال ميديا
        'social_facebook',
        'social_twitter',
        'social_instagram',
        'social_youtube',
        'social_linkedin',
        'social_tiktok',
        'social_whatsapp',

        // إعدادات العرض
        'primary_color',
        'secondary_color',
        'footer_text',
        'copyright_text',

        // إعدادات الإعلانات
        'google_ads_desktop_home',
        'google_ads_desktop_home_2',
        'google_ads_mobile_home',
        'google_ads_mobile_home_2',

        'google_ads_desktop_classes',
        'google_ads_desktop_classes_2',
        'google_ads_mobile_classes',
        'google_ads_mobile_classes_2',

        'google_ads_desktop_subject',
        'google_ads_desktop_subject_2',
        'google_ads_mobile_subject',
        'google_ads_mobile_subject_2',

        'google_ads_desktop_article',
        'google_ads_desktop_article_2',
        'google_ads_mobile_article',
        'google_ads_mobile_article_2',

        'google_ads_desktop_news',
        'google_ads_desktop_news_2',
        'google_ads_mobile_news',
        'google_ads_mobile_news_2',

        'google_ads_desktop_download',
        'google_ads_desktop_download_2',
        'google_ads_mobile_download',
        'google_ads_mobile_download_2',

        // إعدادات SEO العامة
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'google_analytics_id',

        // إعدادات عامة أخرى
        'maintenance_mode',
        'maintenance_message',
        'allow_registration',
        'recaptcha_site_key',
    ];

    /**
     * GET /api/front/settings
     * إرجاع الإعدادات العامة فقط - بدون أي معلومات حساسة
     */
    public function settings()
    {
        // Use cache for public settings only
        $settings = Cache::remember('front_public_settings', 600, function () {
            $allSettings = Setting::pluck('value', 'key')->toArray();

            // فلترة الإعدادات - إرجاع فقط المسموح بها
            $filteredSettings = [];
            foreach ($this->publicSettings as $key) {
                if (isset($allSettings[$key])) {
                    $filteredSettings[$key] = $allSettings[$key];
                }
            }

            return $filteredSettings;
        });

        return new BaseResource([
            'settings' => $settings
        ]);
    }

    /**
     * POST /api/front/contact
     * إرسال رسالة تواصل من الزائر
     */
    public function submitContact(Request $request)
    {
        // Honeypot
        if ($request->filled('hp_token')) {
            Log::warning('API Contact honeypot triggered', [
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
            ]);

            return (new BaseResource(['message' => __('الرجاء المحاولة لاحقاً.')]))
                ->response($request)
                ->setStatusCode(400);
        }

        // Time-check
        if ($request->form_start) {
            try {
                $elapsed = abs(
                    Carbon::now()->diffInSeconds(Carbon::parse($request->form_start), false)
                );

                if ($elapsed < 3) {
                    return (new BaseResource(['message' => __('الرجاء الانتظار قليلاً قبل الإرسال.')]))
                        ->response($request)
                        ->setStatusCode(400);
                }
            } catch (\Exception $e) {}
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'phone'    => 'nullable|string|max:20',
            'subject'  => 'required|string|max:255',
            'message'  => 'required|string',
            'g-recaptcha-response' => 'required|captcha',
        ]);

        try {
            // Use cache for settings
            $settings = Cache::remember('front_settings', 600, function () {
                return Setting::pluck('value', 'key')->toArray();
            });
            $contactEmail = $settings['contact_email'] ?? 'info@alemancenter.com';

            Mail::to($contactEmail)->send(new ContactFormMail($validated));

            Log::info('API contact form submitted successfully', [
                'from' => $validated['email'],
                'to' => $contactEmail
            ]);

            return new BaseResource([
                'message' => __('تم إرسال رسالتك بنجاح.')
            ]);

        } catch (\Exception $e) {
            Log::error('API Contact form error', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return (new BaseResource(['message' => __('حدث خطأ أثناء إرسال الرسالة.')]))
                ->response($request)
                ->setStatusCode(500);
        }
    }

    /**
     * GET /api/front/members
     */
    public function members(Request $request)
    {
        $query = User::with('roles')
            ->whereHas('roles', fn($q) =>
                $q->whereIn('name', ['Admin', 'Manager', 'Supervisor', 'Member'])
            );

        if ($request->role && in_array($request->role, ['Admin', 'Manager', 'Supervisor', 'Member'])) {
            $query->whereHas('roles', fn($q) =>
                $q->where('name', $request->role)
            );
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(fn($q) =>
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
            );
        }

        $users = $query->paginate(12);

        $roles = Role::whereIn('name', ['Admin', 'Manager', 'Supervisor', 'Member'])
            ->get();

        return UserResource::collection($users)
            ->additional([
                'success' => true,
                'roles' => $roles,
            ]);
    }

    /**
     * GET /api/front/members/{id}
     */
    public function showMember($id)
    {
        $user = User::with('roles')->find($id);

        if (!$user) {
            return (new BaseResource(['message' => __('User not found.')]))
                ->response(request())
                ->setStatusCode(404);
        }

        return new UserResource($user);
    }

    /**
     * POST /api/front/members/{id}/contact
     */
    public function contactMember(Request $request, $id)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255',
            'subject'  => 'required|string|max:255',
            'message'  => 'required|string',
            'g-recaptcha-response' => 'required|captcha'
        ]);

        try {
            $user = User::findOrFail($id);

            Log::info('API contact-member submitted', [
                'from' => $validated['email'],
                'to' => $user->email,
                'subject' => $validated['subject']
            ]);

            return new BaseResource(['message' => __('تم إرسال الرسالة بنجاح.')]);

        } catch (\Exception $e) {

            Log::error('API contact-member error', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return (new BaseResource(['message' => __('فشل إرسال الرسالة.')]))
                ->response($request)
                ->setStatusCode(500);
        }
    }
}
