<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorEvent extends Model
{
    protected $fillable = [
        'source', 'fingerprint', 'exception', 'message', 'file', 'line',
        'url', 'method', 'user_id', 'user_agent', 'trace', 'count',
        'sr_thread_id', 'notified_at', 'resolved_at', 'last_seen_at',
    ];

    protected $casts = [
        'notified_at'  => 'datetime',
        'resolved_at'  => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function thread()
    {
        return $this->belongsTo(SrThread::class, 'sr_thread_id');
    }
}
