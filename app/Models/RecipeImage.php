<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Model;

class RecipeImage extends Model
{
    protected $fillable = ['recipe_id', 'path', 'sort'];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function getUrlAttribute(): string
    {
        return Media::url($this->path);
    }
}
