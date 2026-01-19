<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes; // Import SoftDeletes
use Spatie\Permission\Traits\HasRoles;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['grade_level', 'subject_name'];



    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'grade_level', 'grade_level');
    }


    public function semesters()
    {
        return $this->hasMany(Semester::class, 'grade_level', 'semester_name');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'subject_id');
    }

    public function files()
    {
        return $this->hasManyThrough(File::class, Article::class, 'subject_id', 'article_id');
    }
}
