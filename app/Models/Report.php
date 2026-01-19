<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User; // added this line to import the User model

class Report extends Model
{
  protected $fillable = [
    'reporter_id', 'reported_user_id', 'reason', 'status'
  ];

  // العلاقات لعرض الأسماء بسهولة
  public function reporter()
  {
    return $this->belongsTo(User::class, 'reporter_id');
  }

  public function reported()
  {
    return $this->belongsTo(User::class, 'reported_user_id');
  }
}
