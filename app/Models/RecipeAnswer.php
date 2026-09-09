<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeAnswer extends Model
{
    protected $fillable = ['recipe_question_id', 'user_id', 'body', 'is_admin', 'status'];

    protected $casts = ['is_admin' => 'boolean'];

    public function question()
    {
        return $this->belongsTo(RecipeQuestion::class, 'recipe_question_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
