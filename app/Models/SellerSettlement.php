<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerSettlement extends Model
{
    protected $fillable = [
        'seller_id', 'order_id', 'gross_amount', 'commission_amount',
        'net_amount', 'status', 'settled_at',
    ];

    protected $casts = ['settled_at' => 'datetime'];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
