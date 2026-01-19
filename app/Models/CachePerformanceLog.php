<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CachePerformanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'cache_key',
        'operation',
        'response_time_ms',
        'memory_usage_kb',
        'ttl',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    public function scopeByOperation($query, $operation)
    {
        return $query->where('operation', $operation);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('cache_key', $key);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
