<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'country',
        'is_active',
        'icon_image',
        'parent_id',
        'icon',
        'image',
        'depth',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'depth' => 'integer',
    ];

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with(['childrenRecursive']);
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            // Calculate depth based on parent
            if ($category->parent_id) {
                $parent = Category::find($category->parent_id);
                $category->depth = $parent ? (($parent->depth ?? 0) + 1) : 0;
            } else {
                $category->depth = 0;
            }
        });
    }
}
