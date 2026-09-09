<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeQuestion extends Model
{
    protected $fillable = [
        'user_id', 'recipe_category_id', 'title', 'body', 'status', 'view_count', 'answer_count',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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
