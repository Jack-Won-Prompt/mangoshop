<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RecipeCategory extends Model
{
    protected $fillable = [
        'name', 'slug', 'tagline', 'cover', 'description',
        'meta_title', 'meta_description', 'sort_order', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (self $c) {
            if (blank($c->slug)) {
                $c->slug = static::uniqueSlug($c->name, $c->id);
            }
        });
    }

    /** 한글 유지 슬러그(공백·특수문자 → '-') */
    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = static::slugify($name) ?: 'recipe';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public static function slugify(string $s): string
    {
        $s = trim($s);
        $s = preg_replace('/[\s\/\\\\]+/u', '-', $s);           // 공백·슬래시 → -
        $s = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $s);        // 문자·숫자·하이픈만
        $s = preg_replace('/-+/', '-', trim($s, '-'));
        return Str::lower($s);
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover ? Media::url($this->cover) : null;
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
