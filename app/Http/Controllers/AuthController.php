<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Access\AuthorizationException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('content.authentications.auth-login-cover');
    }

    public function showRegistrationForm()
    {
        return view('content.authentications.auth-register-cover');
    }

    public function register(Request $request)
{
    try {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            // Explicitly validate confirmation for clearer error attachment
            'password_confirmation' => 'required_with:password|same:password',
            'g-recaptcha-response' => 'required|captcha',
        ], [
            'g-recaptcha-response.required' => 'يرجى التأكد من أنك لست روبوت.',
            'g-recaptcha-response.captcha' => 'فشل التحقق من reCAPTCHA!',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('User');

        Log::info('User created successfully', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => 'User'
        ]);

        try {
            $user->notify(new CustomVerifyEmail);
            Log::info('Verification email sent', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send verification email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('verification.notice')
            ->with('success', __('تم إنشاء حسابك بنجاح. يرجى التحقق من بريدك الإلكتروني لتفعيل حسابك.'));

    } catch (ValidationException $ve) {
        // Return precise validation errors back to the form (do not mask them)
        Log::warning('Registration validation failed', [
            'errors' => $ve->errors(),
        ]);

        return back()
            ->withInput()
            ->withErrors($ve->errors());
    } catch (\Exception $e) {
        Log::error('Registration failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return back()
            ->withInput()
            ->withErrors(['error' => __('حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى.')]);
    }
}

    

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            $credentials = $request->only('email', 'password');
            $remember = (bool) $request->boolean('remember');

            // Log authentication attempt
            Log::info('Login attempt', [
                'email' => $request->email,
                'guard' => config('auth.defaults.guard')
            ]);

            if (Auth::attempt($credentials, $remember)) {
                $request->session()->regenerate();
                
                $user = Auth::user();
                Log::info('Login successful', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'guard' => Auth::getDefaultDriver()
                ]);

                if ($request->wantsJson()) {
                    return response()->json([
                        'status' => true,
                        'message' => 'تم تسجيل الدخول بنجاح',
                        'user' => $user
                    ]);
                }

                return redirect()->intended('/');
            }

            Log::warning('Login failed', [
                'email' => $request->email,
                'reason' => 'Invalid credentials'
            ]);

            return back()->withErrors([
                'email' => 'بيانات الاعتماد المقدمة غير صحيحة.'
            ])->withInput($request->only('email', 'remember'));
        } catch (ValidationException $ve) {
            // Return validation errors without logging a generic error
            Log::warning('Login validation failed', [
                'errors' => $ve->errors(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $ve->errors(),
                ], 422);
            }

            return back()->withErrors($ve->errors())
                ->withInput($request->only('email', 'remember'));
        } catch (\Exception $e) {
            Log::error('Login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'حدث خطأ أثناء تسجيل الدخول. يرجى المحاولة مرة أخرى.'
                ], 500);
            }

            return back()->withErrors([
                'error' => 'حدث خطأ أثناء تسجيل الدخول. يرجى المحاولة مرة أخرى.'
            ]);
        }
    }

    public function logout(Request $request)
    {
        try {
            // Get user before logging out
            $user = Auth::user();

            if ($user) {
                // Ensure IDE understands the concrete model type
                /** @var \App\Models\User $user */
                if ($user instanceof User) {
                    // Build updates only for existing columns to avoid SQL errors
                    $updates = [
                        'is_online' => false,
                        'last_activity' => now(),
                        'last_seen' => now(),
                    ];
                    if (Schema::hasColumn('users', 'status')) {
                        $updates['status'] = 'offline';
                    }
                    $user->update($updates);
                }

                // Log the logout action
                Log::info('User logged out', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);

                // Clear user-specific cache using a simple key
                cache()->forget('user.' . $user->id . '.permissions');
                cache()->forget('user.' . $user->id . '.settings');
                cache()->forget('user.' . $user->id . '.preferences');
            }

            // Debug: log state before logout
            Log::debug('Logout debug: before', [
                'auth_check' => Auth::check(),
                'session_id' => $request->session()->getId(),
            ]);

            // Clear authentication (explicit web guard)
            /** @var \Illuminate\Auth\SessionGuard $guard */
            $guard = Auth::guard('web');
            $guard->logout();

            // Explicitly forget remember-me cookie for web guard (defensive)
            try {
                $recaller = $guard->getRecallerName();
                if ($recaller) {
                    Cookie::queue(Cookie::forget($recaller));
                }
            } catch (\Throwable $t) {
                Log::debug('Logout debug: could not forget recaller cookie', ['error' => $t->getMessage()]);
            }

            // Clear and invalidate session
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            // Explicitly forget session cookie (defensive)
            try {
                $sessionCookie = config('session.cookie');
                if ($sessionCookie) {
                    Cookie::queue(Cookie::forget($sessionCookie));
                }
            } catch (\Throwable $t) {
                Log::debug('Logout debug: could not forget session cookie', ['error' => $t->getMessage()]);
            }

            // Debug: log state after invalidation
            Log::debug('Logout debug: after invalidate', [
                'auth_check' => Auth::check(),
                'session_id' => $request->session()->getId(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => 'تم تسجيل الخروج بنجاح'
                ]);
            }

            return redirect('/');
        } catch (\Exception $e) {
            Log::error('Logout error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'حدث خطأ أثناء تسجيل الخروج'
                ], 500);
            }

            return redirect('/')->withErrors([
                'error' => 'حدث خطأ أثناء تسجيل الخروج'
            ]);
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($request->wantsJson()) {
            return $status === Password::RESET_LINK_SENT
                ? response()->json(['status' => true, 'message' => __($status)])
                : response()->json(['status' => false, 'message' => __($status)], 400);
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    public function showForgotPasswordForm()
    {
        return view('content.authentications.auth-forgot-password-cover');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($request->wantsJson()) {
            return $status === Password::PASSWORD_RESET
                ? response()->json(['status' => true, 'message' => __($status)])
                : response()->json(['status' => false, 'message' => __($status)], 400);
        }

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    public function showResetPasswordForm($token)
    {
        return view('content.authentications.auth-reset-password-cover', ['token' => $token]);
    }

    public function verify(Request $request)
    {
        try {
            $user = User::find($request->route('id'));

            if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
                throw new AuthorizationException;
            }

            if ($user->hasVerifiedEmail()) {
                return redirect()->intended(route('dashboard.index').'?verified=1');
            }

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            return redirect()->intended(route('dashboard.index').'?verified=1');

        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'user_id' => $request->route('id'),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('verification.notice')
                ->with('error', __('فشل التحقق من البريد الإلكتروني. يرجى المحاولة مرة أخرى.'));
        }
    }

    public function verificationNotice()
    {
        return view('content.authentications.auth-verify-email-cover');
    }

    public function verificationResend(Request $request)
    {
        try {
            if ($request->user()->hasVerifiedEmail()) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'status' => false,
                        'message' => __('البريد الإلكتروني مؤكد بالفعل.')
                    ], 400);
                }
                return back()->with('error', __('البريد الإلكتروني مؤكد بالفعل.'));
            }

            // التحقق من معدل الإرسال
            $key = 'verify-email-' . $request->user()->id;
            $maxAttempts = 3;
            $decayMinutes = 1;

            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $seconds = RateLimiter::availableIn($key);
                if ($request->wantsJson()) {
                    return response()->json([
                        'status' => false,
                        'message' => __("الرجاء الانتظار {$seconds} ثانية قبل إعادة المحاولة.")
                    ], 429);
                }
                return back()->with('error', __("الرجاء الانتظار {$seconds} ثانية قبل إعادة المحاولة."));
            }

            RateLimiter::hit($key, $decayMinutes * 60);

            $request->user()->notify(new CustomVerifyEmail);

            Log::info('Verification email resent', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => true,
                    'message' => __('تم إرسال رابط التحقق بنجاح.')
                ]);
            }

            return back()->with('success', __('تم إرسال رابط التحقق بنجاح.'));
        } catch (\Exception $e) {
            Log::error('Failed to resend verification email', [
                'user_id' => $request->user()->id ?? 'unknown',
                'email' => $request->user()->email ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => __('حدث خطأ أثناء إرسال رابط التحقق.')
                ], 500);
            }

            return back()->with('error', __('حدث خطأ أثناء إرسال رابط التحقق.'));
        }
    }

    public function showVerificationNotice()
    {
        return view('content.authentications.auth-verify-email-cover');
    }

    public function resendVerificationEmail(Request $request)
    {
        try {
            if ($request->user()->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => __('البريد الإلكتروني مؤكد بالفعل.')
                ], 400);
            }

            // التحقق من معدل الإرسال
            $key = 'verify-email-' . $request->user()->id;
            $maxAttempts = 3;
            $decayMinutes = 1;

            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $seconds = RateLimiter::availableIn($key);
                return response()->json([
                    'status' => false,
                    'message' => __("الرجاء الانتظار {$seconds} ثانية قبل إعادة المحاولة.")
                ], 429);
            }

            RateLimiter::hit($key, $decayMinutes * 60);

            $request->user()->notify(new CustomVerifyEmail);

            Log::info('Verification email resent', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email
            ]);

            return response()->json([
                'status' => true,
                'message' => __('تم إرسال رابط التحقق بنجاح.')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to resend verification email', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'message' => __('حدث خطأ أثناء إرسال رابط التحقق.')
            ], 500);
        }
    }

    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    private function mobileSchemes(): array
    {
        $raw = (string) env('MOBILE_AUTH_SCHEMES', 'alemancenter');
        return collect(explode(',', $raw))
            ->map(fn ($value) => strtolower(trim($value)))
            ->filter()
            ->values()
            ->all();
    }

    private function isValidMobileRedirectUri(?string $redirectUri): bool
    {
        if (!$redirectUri) {
            return false;
        }

        $parts = parse_url($redirectUri);
        if (!is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if ($scheme === '') {
            return false;
        }

        return in_array($scheme, $this->mobileSchemes(), true);
    }

    private function encodeMobileState(string $mobileRedirectUri): string
    {
        $payload = [
            'mobile' => 1,
            'redirect' => $mobileRedirectUri,
            'nonce' => Str::random(12),
        ];
        $encoded = base64_encode(json_encode($payload));
        return rtrim(strtr($encoded, '+/', '-_'), '=');
    }

    private function decodeMobileState(?string $state): ?string
    {
        if (!$state) {
            return null;
        }

        $normalized = strtr($state, '-_', '+/');
        $padding = strlen($normalized) % 4;
        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalized, true);
        if ($decoded === false) {
            return null;
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload)) {
            return null;
        }

        $redirectUri = isset($payload['redirect']) && is_string($payload['redirect'])
            ? $payload['redirect']
            : null;

        return $this->isValidMobileRedirectUri($redirectUri) ? $redirectUri : null;
    }

    private function appendQuery(string $url, array $query): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . http_build_query($query);
    }

    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function googleRedirect(Request $request)
    {
        $mobileRequested = $request->boolean('mobile');
        $mobileRedirectUri = $request->query('mobile_redirect_uri');
        $mobileEnabled = $mobileRequested && $this->isValidMobileRedirectUri($mobileRedirectUri);

        $driver = Socialite::driver('google')->stateless();
        if ($mobileEnabled) {
            $driver = $driver->with([
                'state' => $this->encodeMobileState((string) $mobileRedirectUri),
            ]);
        }

        return $driver->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\Response
     */
    public function googleCallback(Request $request)
    {
        $mobileRedirectUri = $this->decodeMobileState($request->query('state'));
        $isMobileFlow = $this->isValidMobileRedirectUri($mobileRedirectUri);

        try {
            Log::info('Google callback initiated', ['request_data' => $request->all()]);

            if ($request->has('error')) {
                Log::warning('Google OAuth returned error', [
                    'error' => $request->query('error'),
                    'error_description' => $request->query('error_description'),
                    'state' => $request->query('state')
                ]);

                if ($isMobileFlow) {
                    return redirect()->away($this->appendQuery((string) $mobileRedirectUri, [
                        'error' => 'google_auth_cancelled',
                    ]));
                }

                return redirect()->route('login')->with('error', __('Google login was cancelled.'));
            }

            if (!$request->has('code')) {
                Log::warning('Google OAuth callback missing code parameter', [
                    'query' => $request->query(),
                ]);

                if ($isMobileFlow) {
                    return redirect()->away($this->appendQuery((string) $mobileRedirectUri, [
                        'error' => 'google_auth_failed',
                    ]));
                }

                return redirect()->route('login')->with('error', __('Google login failed.'));
            }

            $provider = Socialite::driver('google');
            /** @var \Laravel\Socialite\Two\AbstractProvider $provider */
            $googleUser = $provider->stateless()->user();

            Log::info('Google user data retrieved', [
                'google_id' => $googleUser->id,
                'email' => $googleUser->email,
                'name' => $googleUser->name
            ]);

            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
                $userData = [
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'password' => Hash::make(Str::random(24)),
                    'email_verified_at' => now(),
                    'google_id' => $googleUser->id,
                ];

                if (!empty($googleUser->avatar)) {
                    $userData['profile_photo_path'] = $googleUser->avatar;
                }

                $user = User::create($userData);

                try {
                    $user->assignRole('User');
                } catch (\Exception $roleException) {
                    Log::warning('Could not assign role to user', [
                        'user_id' => $user->id,
                        'error' => $roleException->getMessage()
                    ]);
                }
            } else {
                $updateData = ['google_id' => $googleUser->id];
                if (!empty($googleUser->avatar)) {
                    $updateData['profile_photo_path'] = $googleUser->avatar;
                }
                $user->update($updateData);
            }

            if (!$user->hasVerifiedEmail()) {
                $user->forceFill([
                    'email_verified_at' => now()
                ])->save();
            }

            Auth::login($user);
            $request->session()->regenerate();

            if (Auth::check()) {
                $token = $user->createToken('google-login')->plainTextToken;

                if ($isMobileFlow) {
                    return redirect()->away($this->appendQuery((string) $mobileRedirectUri, [
                        'token' => $token,
                    ]));
                }

                $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
                return redirect()->to("{$frontendUrl}/auth/google/callback?token={$token}");
            }

            if ($isMobileFlow) {
                return redirect()->away($this->appendQuery((string) $mobileRedirectUri, [
                    'error' => 'google_auth_failed',
                ]));
            }

            return redirect()->route('login')->with('error', __('Google login failed.'));
        } catch (\Exception $e) {
            Log::error('Google login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            if ($isMobileFlow) {
                return redirect()->away($this->appendQuery((string) $mobileRedirectUri, [
                    'error' => 'google_auth_failed',
                ]));
            }

            return redirect()->route('login')->with('error', __('Google login failed.'));
        }
    }
}
