<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = [
        'recipe_category_id', 'user_id', 'title', 'slug', 'summary', 'body',
        'cover_image', 'video_url', 'video_path', 'is_official', 'is_pinned', 'status', 'view_count', 'like_count', 'comment_count',
        'cook_time', 'ingredients', 'tags', 'meta_title', 'meta_description', 'published_at',
    ];

    protected $casts = [
        'is_official'  => 'boolean',
        'is_pinned'    => 'boolean',
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(RecipeCategory::class, 'recipe_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(RecipeImage::class)->orderBy('sort')->orderBy('id');
    }

    public function comments()
    {
        return $this->hasMany(RecipeComment::class)->where('status', 'published')->oldest();
    }

    public function likers()
    {
        return $this->belongsToMany(User::class, 'recipe_likes')->withTimestamps();
    }

    public function isLikedBy(?User $user): bool
    {
        return $user ? $this->likers()->where('user_id', $user->id)->exists() : false;
    }

    /* ===== 스코프 ===== */
    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    /** 인기 점수순(조회 + 좋아요*3 + 댓글*2) */
    public function scopePopular($q)
    {
        return $q->orderByRaw('(view_count + like_count * 3 + comment_count * 2) DESC');
    }

    /** 태그 배열 */
    public function getTagArrayAttribute(): array
    {
        return collect(explode(',', (string) $this->tags))->map(fn ($t) => trim($t))->filter()->unique()->values()->all();
    }

    /** 태그 문자열 정규화(공백 제거·중복 제거 → "a,b,c") */
    public static function normalizeTags(?string $raw): ?string
    {
        $tags = collect(explode(',', (string) $raw))->map(fn ($t) => trim($t))->filter()->unique()->take(10);

        return $tags->isEmpty() ? null : $tags->implode(',');
    }

    /* ===== 접근자 ===== */
    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_image ? Media::url($this->cover_image) : null;
    }

    /** 업로드 영상 파일 URL */
    public function getVideoFileUrlAttribute(): ?string
    {
        return $this->video_path ? Media::url($this->video_path) : null;
    }

    /** 유튜브 영상 ID 추출(embed용). 유튜브가 아니면 null */
    public function getYoutubeIdAttribute(): ?string
    {
        if (! $this->video_url) {
            return null;
        }
        if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $this->video_url, $m)) {
            return $m[1];
        }

        return null;
    }

    /** 카테고리 내 유니크 슬러그(한글 유지) */
    public static function uniqueSlug(string $title, int $categoryId, ?int $ignoreId = null): string
    {
        $base = RecipeCategory::slugify($title) ?: 'recipe';
        $slug = $base;
        $i = 2;
        while (static::where('recipe_category_id', $categoryId)->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /** 공개 상세 URL */
    public function getUrlAttribute(): string
    {
        return route('community.recipe.show', [
            'category' => $this->category->slug ?? 'recipe',
            'recipe'   => $this->slug,
        ]);
    }
}
