<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    use HasFactory;


    protected $fillable = ['keyword'];

    public function articles()
{
    return $this->belongsToMany(Article::class, 'article_keyword', 'keyword_id', 'article_id');
}

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_keyword', 'keyword_id', 'post_id');
    }

}
