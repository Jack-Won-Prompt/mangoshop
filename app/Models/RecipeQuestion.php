<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeQuestion extends Model
{
    protected $fillable = [
        'user_id', 'recipe_category_id', 'title', 'body', 'video_url', 'status', 'view_count', 'answer_count',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(RecipeQuestionImage::class)->orderBy('sort')->orderBy('id');
    }

    /** 유튜브 영상 ID(embed용) */
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

    public function category()
    {
        return $this->belongsTo(RecipeCategory::class, 'recipe_category_id');
    }

    public function answers()
    {
        return $this->hasMany(RecipeAnswer::class)->where('status', 'published')
            ->orderByDesc('is_admin')->oldest();
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }
}
