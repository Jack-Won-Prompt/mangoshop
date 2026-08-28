<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'seller_id', 'product_id', 'product_name', 'option_id', 'option_name', 'option_extra',
        'unit', 'price', 'quantity', 'subtotal',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** 상품명 + 옵션명 표시 */
    public function getDisplayNameAttribute(): string
    {
        return $this->product_name.($this->option_name ? ' / '.$this->option_name : '');
    }
}
