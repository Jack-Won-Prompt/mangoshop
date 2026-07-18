<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    protected $fillable = [
        'quote_no', 'user_id', 'seller_id', 'status', 'title', 'memo',
        'seller_memo', 'desired_date', 'estimate_total', 'valid_until', 'quoted_at',
    ];

    protected $casts = [
        'desired_date' => 'date',
        'valid_until'  => 'date',
        'quoted_at'    => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'requested' => '견적요청',
            'quoted'    => '견적회신',
            'accepted'  => '수락',
            'rejected'  => '거절',
            'expired'   => '만료',
            'ordered'   => '주문완료',
            default     => $this->status,
        };
    }
}
