<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SrThread extends Model
{
    protected $fillable = ['user_id', 'title', 'model', 'last_message_at'];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(SrMessage::class)->orderBy('id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
