<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOption extends Model
{
    protected $fillable = ['product_id', 'group_name', 'name', 'extra_price', 'stock', 'is_active', 'sort'];

    protected $casts = [
        'extra_price' => 'integer',
        'stock'       => 'integer',
        'is_active'   => 'boolean',
        'sort'        => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** 옵션 판매가(절대값) — 이 옵션 선택 시의 판매가 */
    public function priceFor(?Product $product = null): int
    {
        return max(0, (int) $this->extra_price);
    }

    /** 표시 라벨: "8과 - 58,900원" */
    public function getLabelAttribute(): string
    {
        return $this->name.' · '.number_format(max(0, (int) $this->extra_price)).'원';
    }
}
