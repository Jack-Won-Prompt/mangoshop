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

    /** 옵션 적용가 = max(0, 상품 판매가 + 추가금액) */
    public function priceFor(Product $product): int
    {
        return max(0, (int) $product->price + (int) $this->extra_price);
    }

    /** 표시 라벨: "5kg 박스 (+3,000원)" */
    public function getLabelAttribute(): string
    {
        $label = $this->name;
        if ($this->extra_price > 0) {
            $label .= ' (+'.number_format($this->extra_price).'원)';
        } elseif ($this->extra_price < 0) {
            $label .= ' ('.number_format($this->extra_price).'원)';
        }

        return $label;
    }
}
