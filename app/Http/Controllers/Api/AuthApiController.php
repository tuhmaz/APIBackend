<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\BaseResource;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\UserResource;

class AuthApiController extends Controller
{
    private function issueToken(User $user): string
    {
        return $user->createToken('api_token')->plainTextToken;
    }
    /**
     * ============================
     *  REGISTER
     * ============================
     */
    public function register(RegisterRequest $request)
    {
        // validation handled by RegisterRequest

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('User');
        event(new Registered($user));

        try {
            $user->notify(new CustomVerifyEmail());
        } catch (\Exception $e) {
            Log::error("Failed sending verify email: {$e->getMessage()}");
        }

        $token = $this->issueToken($user);

        return (new BaseResource([
            'status'  => true,
            'message' => 'تم إنشاء الحساب بنجاح. يرجى التحقق من بريدك الإلكتروني.',
            'token'   => $token,
            'user'    => new UserResource($user),
        ]))->response($request)->setStatusCode(201);
    }

    /**
     * ============================
     *  LOGIN
     * ============================
     */
    public function login(LoginRequest $request)
    {
        // validation handled by LoginRequest

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['بيانات الاعتماد غير صحيحة.'],
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load(['roles', 'permissions']);

        $token = $this->issueToken($user);

        return new BaseResource([
            'status'  => true,
            'message' => 'تم تسجيل الدخول بنجاح.',
            'token'   => $token,
            'user'    => new UserResource($user)
        ]);
    }

    /**
     * ============================
     *  LOGOUT
     * ============================
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return new BaseResource([
            'status'  => true,
            'message' => 'تم تسجيل الخروج بنجاح.'
        ]);
    }

    /**
     * ============================
     *  GET AUTHENTICATED USER
     * ============================
     */
    public function me(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Load roles and permissions with caching at query level
        // Using eager loading with select for better performance
        $user->load(['roles:id,name', 'permissions:id,name']);

        return new BaseResource([
            'status' => true,
            'user'   => new UserResource($user)
        ]);
    }

    /**
     * ============================
     *  UPDATE PROFILE
     * ============================
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'job_title' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female',
            'country' => 'nullable|string|max:255',
            'password' => 'nullable|confirmed|min:8',
            'profile_photo' => 'nullable|image|max:2048',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|string|max:255',
            'social_links.twitter' => 'nullable|string|max:255',
            'social_links.linkedin' => 'nullable|string|max:255',
            'social_links.instagram' => 'nullable|string|max:255',
            'social_links.github' => 'nullable|string|max:255',
        ], [
            'profile_photo.max' => 'حجم الصورة يجب أن لا يتجاوز 2 ميجابايت',
            'profile_photo.image' => 'الملف يجب أن يكون صورة',
            'password.confirmed' => 'كلمة المرور غير متطابقة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'email.email' => 'البريد الإلكتروني غير صالح',
        ]);

        // Update basic fields
        $user->fill($validated);

        // Handle password update
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            // Delete old photo
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $path = $request->file('profile_photo')->store('profiles', 'public');
            $user->profile_photo_path = $path;
        }

        // Handle social links
        if (isset($validated['social_links'])) {
            $user->social_links = $validated['social_links'];
        }

        $user->save();

        return (new UserResource($user->load('roles', 'permissions')))
            ->additional([
                'message' => 'تم تحديث الملف الشخصي بنجاح',
            ]);
    }

    /**
     * ============================
     *  FORGOT PASSWORD
     * ============================
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        // validation handled by ForgotPasswordRequest

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return new BaseResource([
                'status'  => true,
                'message' => __($status)
            ]);
        }

        return (new BaseResource(['message' => __($status)]))
            ->response($request)
            ->setStatusCode(400);
    }

    /**
     * ============================
     *  RESET PASSWORD
     * ============================
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        // validation handled by ResetPasswordRequest
    
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password)
                ])->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return new BaseResource([
                'status'  => true,
                'message' => __($status)
            ]);
        }

        return (new BaseResource(['message' => __($status)]))
            ->response($request)
            ->setStatusCode(400);
    }

    /**
     * ============================
     *  EMAIL VERIFICATION
     * ============================
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals($hash, sha1($user->email))) {
            throw new AuthorizationException();
        }

        if ($user->hasVerifiedEmail()) {
            return new BaseResource([
                'status'  => true,
                'message' => 'البريد الإلكتروني مؤكد بالفعل.'
            ]);
        }

        $user->markEmailAsVerified();

        return new BaseResource([
            'status'  => true,
            'message' => 'تم تأكيد البريد الإلكتروني بنجاح.'
        ]);
    }

    /**
     * ============================
     *  RESEND VERIFICATION EMAIL
     * ============================
     */
    public function resendVerifyEmail(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return (new BaseResource(['message' => 'البريد الإلكتروني مؤكد بالفعل.']))
                ->response($request)
                ->setStatusCode(400);
        }

        $key = 'verify-email-' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return (new BaseResource(['message' => "الرجاء الانتظار " . RateLimiter::availableIn($key) . " ثانية."]))
                ->response($request)
                ->setStatusCode(429);
        }

        RateLimiter::hit($key, 60);

        $user->notify(new CustomVerifyEmail());

        return new BaseResource([
            'status'  => true,
            'message' => 'تم إرسال رابط التحقق.'
        ]);
    }

    /**
     * ============================
     *  GOOGLE LOGIN
     * ============================
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

    private function appendQuery(string $url, array $query): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . http_build_query($query);
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

    /**
     * Redirect to Google OAuth
     * For SPA: Returns the redirect URL instead of redirecting
     */
    public function googleRedirect(Request $request)
    {
        $mobileRequested = $request->boolean('mobile');
        $mobileRedirectUri = $request->query('mobile_redirect_uri');
        $mobileEnabled = $mobileRequested && $this->isValidMobileRedirectUri($mobileRedirectUri);

        if ($mobileRequested && !$mobileEnabled) {
            return (new BaseResource([
                'message' => 'رابط إعادة التوجيه للتطبيق غير صالح.',
            ]))->response($request)->setStatusCode(422);
        }

        /** @var AbstractProvider $driver */
        $driver = Socialite::driver('google');
        if ($mobileEnabled) {
            $driver = $driver->with([
                'state' => $this->encodeMobileState((string) $mobileRedirectUri),
            ]);
        }

        $redirectUrl = $driver
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        if ($request->wantsJson() || $request->has('spa') || $mobileEnabled) {
            return new BaseResource([
                'status' => true,
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect()->away($redirectUrl);
    }

    /**
     * Handle Google OAuth callback
     * Supports both code exchange and direct callback
     */
    public function googleCallback(Request $request)
    {
        $mobileRedirectUriFromState = $this->decodeMobileState($request->query('state'));
        $mobileRequested = $request->boolean('mobile');
        $mobileRedirectUriFromQuery = $mobileRequested ? $request->query('mobile_redirect_uri') : null;
        $mobileRedirectUri = $mobileRedirectUriFromState ?: $mobileRedirectUriFromQuery;
        $mobileEnabled = $this->isValidMobileRedirectUri($mobileRedirectUri);

        try {
            // Get user from Google using the authorization code
            /** @var AbstractProvider $driver */
            $driver = Socialite::driver('google');
            $googleUser = $driver->stateless()->user();

            // Find or create user
            $user = User::where('email', $googleUser->email)->first();

            if (!$user) {
                $user = User::create([
                    'name'              => $googleUser->name,
                    'email'             => $googleUser->email,
                    'password'          => Hash::make(Str::random(24)),
                    'google_id'         => $googleUser->id,
                    'email_verified_at' => now(),
                    'profile_photo_path' => $googleUser->avatar,
                ]);

                $user->assignRole('User');
            } else {
                // Update google_id if not set
                if (!$user->google_id) {
                    $user->google_id = $googleUser->id;
                    $user->save();
                }
            }

            // Load roles and permissions
            $user->load(['roles:id,name', 'permissions:id,name']);

            $token = $this->issueToken($user);

            if ($mobileEnabled) {
                $target = $this->appendQuery((string) $mobileRedirectUri, ['token' => $token]);
                return redirect()->away($target);
            }

            if ($request->wantsJson()) {
                return new BaseResource([
                    'status' => true,
                    'token'  => $token,
                    'user'   => new UserResource($user)
                ]);
            }

            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));

            // Redirect to frontend with token
            return redirect()->to("{$frontendUrl}/auth/google/callback?token={$token}");

        } catch (\Exception $e) {
            Log::error("Google Login Failed: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString()
            ]);

            if ($mobileEnabled) {
                $target = $this->appendQuery((string) $mobileRedirectUri, ['error' => 'google_auth_failed']);
                return redirect()->away($target);
            }

            // Redirect to frontend with error
            if (!request()->wantsJson()) {
                $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
                return redirect()->to("{$frontendUrl}/login?error=google_auth_failed");
            }

            return (new BaseResource(['message' => 'فشل تسجيل الدخول باستخدام Google.']))
                ->response($request)
                ->setStatusCode(500);
        }
    }
}
