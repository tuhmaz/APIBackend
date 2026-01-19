<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\ContactFormMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class FrontController extends Controller
{
    public function home()
    {
        return view('content.front.home');
    }

    public function aboutUs()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('content.front.about-us', compact('settings'));
    }

    public function contactUs()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('content.front.contact-us', compact('settings'));
    }

    public function submitContact(Request $request)
    {
        // Honeypot: reject if hidden field filled
        if ($request->filled('hp_token')) {
            Log::warning('Contact form honeypot triggered', [
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
            ]);
            return back()->withInput()->with('error', __('الرجاء المحاولة لاحقاً.'));
        }

        // Time check: require at least 3 seconds between render and submit
        $start = $request->input('form_start');
        if ($start) {
            try {
                $elapsed = Carbon::now()->diffInSeconds(Carbon::parse($start), false);
                // diffInSeconds with false returns signed; if negative, user clock skew. Normalize by abs.
                $elapsed = abs($elapsed);
                if ($elapsed < 3) {
                    Log::warning('Contact form submitted too fast', [
                        'elapsed_sec' => $elapsed,
                        'ip' => $request->ip(),
                    ]);
                    return back()->withInput()->with('error', __('الرجاء الانتظار قليلاً قبل الإرسال وإعادة المحاولة.'));
                }
            } catch (\Exception $e) {
                // If parsing fails, continue without blocking
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'hp_token' => 'nullable|string|size:0',
            'form_start' => 'nullable|string',
            'g-recaptcha-response' => 'required|captcha',
        ]);

        try {
            $settings = Setting::pluck('value', 'key')->toArray();

            // Get email from settings or use default
            $contactEmail = $settings['contact_email'] ?? 'info@alemancenter.com';

            // Send email
            Mail::to($contactEmail)->send(new ContactFormMail($validated));


            // Log success
            Log::info('Contact form submitted successfully', [
                'from' => $validated['email'],
                'to' => $contactEmail,
                'subject' => $validated['subject']
            ]);

            return back()->with('success', __('تم إرسال رسالتك بنجاح! سنقوم بالرد عليك في أقرب وقت ممكن.'));
        } catch (\Exception $e) {
            // Log error
            Log::error('Error sending contact form email', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return back()
                ->withInput()
                ->with('error', __('عذراً، حدث خطأ أثناء إرسال رسالتك. الرجاء المحاولة مرة أخرى لاحقاً.'));
        }
    }

    /**
     * عرض قائمة الأعضاء والمدراء والمشرفين
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function members(Request $request)
    {
        $query = User::with('roles')
            ->whereHas('roles', function($q) {
                $q->whereIn('name', ['Admin', 'Manager', 'Supervisor', 'Member']);
            });

        // فلترة حسب الدور
        if ($request->has('role') && in_array($request->role, ['Admin', 'Manager', 'Supervisor', 'Member'])) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // بحث بالاسم أو البريد الإلكتروني
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(12);
        $roles = Role::whereIn('name', ['Admin', 'Manager', 'Supervisor', 'Member'])->get();

        return view('content.front.members.index', compact('users', 'roles'));
    }

    /**
     * عرض صفحة العضو
     *
     * @param  string  $id
     * @return \Illuminate\View\View
     */
    public function showMember($id)
    {
        $user = User::with('roles')->findOrFail($id);
        return view('content.front.members.show', compact('user'));
    }

    /**
     * Handle contact form submission for members
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function contactMember(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'g-recaptcha-response' => 'required|captcha'
        ]);

        try {
            $user = User::findOrFail($id);
            
            // Here you can implement the logic to send the message to the user
            // For example, send an email or store the message in the database
            
            // Log the contact attempt
            Log::info('Member contact form submitted', [
                'from' => $validated['email'],
                'to' => $user->email,
                'subject' => $validated['subject']
            ]);

            // Redirect back with success message
            return redirect()->back()->with('success', __('تم إرسال رسالتك بنجاح، سيتم الرد عليك في أقرب وقت ممكن'))
                ->with('user_name', $user->name);
            
        } catch (\Exception $e) {
            Log::error('Error in member contact form', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال الرسالة. الرجاء المحاولة مرة أخرى.'
            ], 500);
        }
    }
}
