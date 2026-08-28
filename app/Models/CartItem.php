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

    /** 단가 — 옵션 선택 시 옵션 판매가(절대), 아니면 회원가 */
    public function unitPrice(?User $user): int
    {
        if ($this->option) {
            return max(0, (int) $this->option->extra_price);
        }

        return $this->product->priceFor($user);
    }
}
