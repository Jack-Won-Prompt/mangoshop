<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = ['user_id', 'product_id', 'option_id', 'quantity'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function option()
    {
        return $this->belongsTo(ProductOption::class, 'option_id');
    }

    /** 옵션 추가금액 포함 단가(회원가 반영) */
    public function unitPrice(?User $user): int
    {
        return max(0, $this->product->priceFor($user) + (int) ($this->option?->extra_price ?? 0));
    }
}
