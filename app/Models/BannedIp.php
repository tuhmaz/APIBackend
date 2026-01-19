<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BannedIp extends Model
{
    protected $table = 'banned_ips';

    protected $fillable = [
        'ip',
        'reason',
        'banned_by',      // أصبح الآن user_id
        'banned_until'
    ];

    protected $casts = [
        'banned_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * علاقة المستخدم (الأدمن الذي حظر)
     * تستخدم Eager Loading لتجنب N+1 Query Problem
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function admin()
    {
        return $this->belongsTo(\App\Models\User::class, 'banned_by');
    }

    /**
     * Alias للتوافق مع BlockedIp القديم
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function blockedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'banned_by');
    }

    // التحقق إذا الآيبي محظور
    public static function isBanned($ip)
    {
        return static::where('ip', $ip)
            ->where(function($query) {
                $query->whereNull('banned_until')
                      ->orWhere('banned_until', '>', now());
            })
            ->exists();
    }

    // حظر آيبي
    public static function ban($ip, $reason = null, $days = 30, $adminId = null)
    {
        // تحديد من قام بالحظر (Admin ID أو System = null)
        // null يمثل الحظر التلقائي من النظام (Auto-ban)
        // هذا يضمن وجود Audit Trail دائماً ويتوافق مع foreign key constraint
        $bannedBy = $adminId ?: (Auth::check() ? Auth::id() : null);

        return static::create([
            'ip' => $ip,
            'reason' => $reason,
            'banned_by' => $bannedBy,
            'banned_until' => $days ? now()->addDays($days) : null
        ]);
    }

    // Scope: المحظورين النشطين
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('banned_until')
              ->orWhere('banned_until', '>', now());
        });
    }

    // Scope: المحظورين المنتهين
    public function scopeExpired($query)
    {
        return $query->whereNotNull('banned_until')
                     ->where('banned_until', '<=', now());
    }

    public function isActive()
    {
        return is_null($this->banned_until) || $this->banned_until->isFuture();
    }

    /**
     * تحقق إذا كان الحظر تلقائي من النظام
     *
     * @return bool
     */
    public function isSystemBan()
    {
        return $this->banned_by === null;
    }

    /**
     * الحصول على اسم من قام بالحظر
     * يستخدم Eager Loading عند توفره لتجنب N+1 Query
     *
     * @return string
     */
    public function getBannedByNameAttribute()
    {
        if ($this->banned_by === null) {
            return 'النظام (Auto-ban)';
        }

        // استخدام relationLoaded للتحقق من وجود eager loading
        if ($this->relationLoaded('admin') && $this->admin) {
            return $this->admin->name;
        }

        // fallback: تحميل العلاقة فقط عند الحاجة
        return $this->admin?->name ?? 'غير معروف';
    }

    // Accessor للحصول على ip_address (للتوافق مع BlockedIp)
    public function getIpAddressAttribute()
    {
        return $this->ip;
    }

    // Accessor للحصول على blocked_at (للتوافق مع BlockedIp)
    public function getBlockedAtAttribute()
    {
        return $this->created_at;
    }

    // Mutator لتعيين ip_address (للتوافق مع BlockedIp)
    public function setIpAddressAttribute($value)
    {
        $this->attributes['ip'] = $value;
    }

}
