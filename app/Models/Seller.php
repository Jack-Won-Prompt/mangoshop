<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Seller extends Model
{
    protected $fillable = [
        'user_id', 'name', 'slug', 'biz_no', 'ceo_name', 'phone', 'email',
        'postcode', 'address1', 'address2', 'logo', 'banner', 'intro', 'origin_focus',
        'status', 'commission_rate', 'shipping_fee', 'free_shipping_threshold',
        'coldchain', 'shipping_notice', 'rating_sum', 'rating_count',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'coldchain'       => 'boolean',
        'is_active'       => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Seller $seller) {
            if (empty($seller->slug)) {
                $seller->slug = static::uniqueSlug($seller->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'seller';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function settlements()
    {
        return $this->hasMany(SellerSettlement::class);
    }

    public function scopeApproved($q)
    {
        return $q->where('status', 'approved')->where('is_active', true);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved' && $this->is_active;
    }

    /** 별점 평균 */
    public function avgRating(): float
    {
        return $this->rating_count > 0
            ? round($this->rating_sum / $this->rating_count, 1)
            : 0.0;
    }

    /** 주문금액 기준 배송비(무료배송 기준 반영) */
    public function shippingFeeFor(int $subtotal): int
    {
        if ($this->free_shipping_threshold && $subtotal >= $this->free_shipping_threshold) {
            return 0;
        }

        return (int) $this->shipping_fee;
    }
}
