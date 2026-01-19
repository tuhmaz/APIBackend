<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SecurityLog extends Model
{
    protected $fillable = [
        'ip_address',
        'event_type',
        'description',
        'user_agent',
        'route',
        'method',
        'request_data',
        'risk_score',
        'country_code',
        'city',
        'attack_type',
        'is_blocked',
        'is_trusted',
        'user_id',
        'is_resolved',
        'resolved_at',
        'resolution_notes',
        'severity',
        'occurrence_count',
    ];

    protected $casts = [
        'request_data' => 'array',
        'is_blocked' => 'boolean',
        'is_trusted' => 'boolean',
        'is_resolved' => 'boolean',
        'risk_score' => 'integer',
        'occurrence_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Backed-by-schema severity levels
    public const SEVERITY_LEVELS = [
        'INFO' => 'info',
        'WARNING' => 'warning',
        'DANGER' => 'danger',
        'CRITICAL' => 'critical',
    ];

    // Common event types used across the app/services
    public const EVENT_TYPES = [
        'LOGIN_FAILED' => 'login_failed',
        'SUSPICIOUS_ACTIVITY' => 'suspicious_activity',
        'BLOCKED_ACCESS' => 'blocked_access',
    ];

    public static function log($eventType, $severity = self::SEVERITY_LEVELS['INFO'], $description = null, $request = null, $user = null)
    {
        if ($request === null) {
            $request = request();
        }

        if ($user === null && Auth::check()) {
            $user = Auth::user();
        }

        $data = [
            'ip_address' => $request->ip(),
            'event_type' => $eventType,
            'description' => is_string($description) ? $description : json_encode($description, JSON_PRETTY_PRINT),
            'user_agent' => $request->userAgent(),
            'route' => $request->path(),
            'method' => $request->method(),
            'request_data' => $request->except(['_token', 'password', 'password_confirmation', 'current_password']),
            'user_id' => $user ? $user->id : null,
            'severity' => $severity,
        ];

        // Log the security event
        $log = static::create($data);

        // If this is a critical event, notify admins (placeholder)
        if ($severity === self::SEVERITY_LEVELS['CRITICAL']) {
            // Here you can add notification logic (email, slack, etc.)
            // Example: dispatch(new NotifyAdminsAboutSecurityEvent($log));
        }

        return $log;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedByUser()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Backwards-compatible: keep method name but use severity column
    public function scopeOfLevel($query, $level)
    {
        return $query->where('severity', $level);
    }

    public function scopeCritical($query)
    {
        return $this->ofLevel(self::SEVERITY_LEVELS['CRITICAL']);
    }

    public function scopeDanger($query)
    {
        return $this->ofLevel(self::SEVERITY_LEVELS['DANGER']);
    }

    public function scopeWarning($query)
    {
        return $this->ofLevel(self::SEVERITY_LEVELS['WARNING']);
    }

    public function scopeInfo($query)
    {
        return $this->ofLevel(self::SEVERITY_LEVELS['INFO']);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Accessor: returns a color keyword for UI badges based on severity/event type.
     * Compatible with existing controller usage calling the method directly.
     */
    public function getEventTypeColorAttribute()
    {
        // Prefer severity mapping when available
        $severity = strtolower((string) $this->severity);
        switch ($severity) {
            case self::SEVERITY_LEVELS['CRITICAL']:
                return 'danger';
            case self::SEVERITY_LEVELS['DANGER']:
                return 'danger';
            case self::SEVERITY_LEVELS['WARNING']:
                return 'warning';
            case self::SEVERITY_LEVELS['INFO']:
                return 'info';
        }

        // Fallback by event type if severity is missing/unknown
        $type = strtolower((string) $this->event_type);
        return match ($type) {
            'sql_injection_attempt', 'xss_attempt', 'blocked_access', 'brute_force_attempt' => 'danger',
            'suspicious_activity', 'file_upload_violation' => 'warning',
            'login_failed' => 'info',
            default => 'secondary',
        };
    }
}
