<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;
use App\Mail\ContactFormMail;
use Carbon\Carbon;
use App\Http\Resources\BaseResource;

class FrontApiController extends Controller
{
    /**
     * GET /api/front/settings
     */
    public function settings()
    {
        // Use cache for settings
        $settings = Cache::remember('front_settings', 600, function () {
            return Setting::pluck('value', 'key')->toArray();
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
