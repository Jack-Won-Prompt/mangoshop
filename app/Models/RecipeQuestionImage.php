<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Model;

class RecipeQuestionImage extends Model
{
    protected $fillable = ['recipe_question_id', 'path', 'sort'];

    public function getUrlAttribute(): string
    {
        return Media::url($this->path);
    }
}
