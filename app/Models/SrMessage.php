<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SrMessage extends Model
{
    protected $fillable = [
        'sr_thread_id', 'role', 'content', 'attachments', 'tokens_in', 'tokens_out',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function thread()
    {
        return $this->belongsTo(SrThread::class, 'sr_thread_id');
    }
}
