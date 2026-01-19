<?php

namespace App\Models;

use App\Notifications\CustomVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    protected $connection = 'jo';
    protected $guard_name = 'sanctum';

    /**
     * التحقق مما إذا كان المستخدم مدير
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasRole('Admin');
    }

    /**
     * إرسال إشعار تأكيد البريد الإلكتروني.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        Log::info('Sending email verification notification', [
            'user_id' => $this->id,
            'email' => $this->email
        ]);

        $this->notify(new CustomVerifyEmail);

        Log::info('Email verification notification sent successfully');
    }

    /**
     * الحصول على رابط الصورة الشخصية للمستخدم
     *
     * @return string
     */
    public function getProfilePhotoUrlAttribute()
    {
        // التحقق من وجود حقل profile_photo_path أولاً
        if ($this->profile_photo_path) {
            // التحقق مما إذا كان URL كامل (مثل Google OAuth)
            if (filter_var($this->profile_photo_path, FILTER_VALIDATE_URL)) {
                return $this->profile_photo_path;
            }
            // إذا كان مسار محلي
            return asset('storage/' . $this->profile_photo_path);
        }

        // التحقق من وجود حقل google_id كبديل (للتوافق مع Google OAuth)
        if ($this->google_id && !empty($this->profile_photo_path)) {
            return $this->profile_photo_path;
        }

        // اختيار صورة افتراضية ثابتة حسب الدور/الجنس إذا لم يتم العثور على صورة
        // أولوية: الدور > الجنس > افتراضي عام
        try {
            if (method_exists($this, 'hasRole')) {
                if ($this->hasRole('Admin')) {
                    return asset('assets/img/avatars/1.png'); // مدير
                }
            }
        } catch (\Throwable $e) {
            // تجاهل أي خطأ في فحص الدور لتجنب كسر الواجهة
        }

        if (!empty($this->gender)) {
            $gender = strtolower($this->gender);
            if (in_array($gender, ['female', 'f', 'انثى', 'أنثى'])) {
                return asset('assets/img/avatars/8.png');
            }
            if (in_array($gender, ['male', 'm', 'ذكر'])) {
                return asset('assets/img/avatars/7.png');
            }
        }

        // افتراضي عام
        return asset('assets/img/avatars/default.png');
    }

    /**
     * تحقق مما إذا كان المستخدم متصل حالياً
     *
     * @return bool
     */
    public function isOnline()
    {
        return $this->last_activity && $this->last_activity->gt(now()->subMinutes(5));
    }

    /**
     * تحديث آخر نشاط للمستخدم
     *
     * @return void
     */
    public function updateLastActivity()
    {
        $this->last_activity = now();
        $this->save();
    }

    /**
     * الحصول على رابط الصورة الشخصية للمستخدم (للتوافق مع الواجهات القديمة)
     *
     * @return string
     */
    public function getAvatarUrl()
    {
        return $this->profile_photo_url;
    }

    /**
     * الحقول التي يمكن تعبئتها جماعياً
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'api_token',
        'phone',
        'job_title',
        'gender',
        'country',
        'bio',
        'social_links',
        'profile_photo_path',
        'avatar', // للتوافق مع الإصدارات السابقة
        'google_id',
        'status',
        'last_activity',
        'current_team_id',
        'last_seen'
    ];

    /**
     * الحقول المخفية عند التحويل إلى مصفوفة أو JSON
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * الحقول التي يجب تحويلها إلى أنواع بيانات محددة
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'social_links' => 'array',
        'last_activity' => 'datetime',
        'last_seen' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
  ];

    /**
     * الخصائص المضافة إلى نموذج المستخدم
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];
}