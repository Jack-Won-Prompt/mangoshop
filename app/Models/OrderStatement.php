<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatement extends Model
{
    protected $fillable = [
        'order_id', 'seq', 'file_name', 'statement_date', 'total_amount',
        'action', 'sent_to', 'issued_by', 'error_message',
    ];

    protected $casts = [
        'statement_date' => 'date',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function actionLabel(): string
    {
        return $this->action === 'email' ? '이메일 발송' : 'PDF 다운로드';
    }
}
