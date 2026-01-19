<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conversation extends Model
{
    use HasFactory;

    // Allow mass assignment for public/private conversations and legacy user columns
    protected $fillable = ['title', 'type', 'user1_id', 'user2_id'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function user1()
    {
        return $this->belongsTo(User::class, 'user1_id');
    }

    public function user2()
    {
        return $this->belongsTo(User::class, 'user2_id');
    }
    // التحقق من أن المحادثة تحتوي على مستخدم معين
    public function hasUser($userId)
    {
        return $this->user1_id == $userId || $this->user2_id == $userId;
    }
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withTimestamps();
    }


}
