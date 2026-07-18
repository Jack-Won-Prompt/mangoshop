<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'seller_id', 'category_id', 'brand_id', 'name', 'slug', 'code', 'unit', 'maker',
        'summary', 'description', 'spec', 'price', 'cost', 'member_price', 'wholesale_price', 'wholesale_only',
        'tax_type', 'stock', 'thumbnail', 'images', 'is_active', 'is_featured', 'is_best', 'is_new',
        'badge', 'view_count', 'sort_order',
        // 신선식품/멀티벤더 속성
        'origin', 'variety', 'grade', 'box_spec', 'weight_kg',
        'inbound_date', 'expiry_date', 'storage_method', 'lot_no',
        'moq', 'price_tiers', 'sale_status', 'expected_inbound_date', 'sales_count',
    ];

    protected $casts = [
        'images'      => 'array',
        'price_tiers' => 'array',
        'is_active'   => 'boolean',
        'is_featured' => 'boolean',
        'is_best'     => 'boolean',
        'is_new'      => 'boolean',
        'inbound_date'          => 'date',
        'expiry_date'           => 'date',
        'expected_inbound_date' => 'date',
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function restockAlerts()
    {
        return $this->hasMany(RestockAlert::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function contractPrices()
    {
        return $this->hasMany(ContractPrice::class);
    }

    /**
     * 회원 등급에 따른 기본 판매가(수량 1 기준).
     * 우선순위: 판매자 전용 계약가(레거시) → 도매 승인회원=도매가 → 소매/정가
     */
    public function priceFor(?User $user): int
    {
        if ($user && $user->isApprovedBusiness()) {
            // 판매자-회원 개별 계약가(회원별 개별 계약가(contract_prices))
            $map = $user->priceMap();
            if (isset($map[$this->id])) {
                return (int) $map[$this->id];
            }
            // 도매 승인회원 → 도매가
            if ($user->isWholesale() && $this->wholesale_price) {
                return (int) $this->wholesale_price;
            }
            if ($this->member_price) {
                return (int) $this->member_price;
            }
        }

        return (int) $this->price;
    }

    /**
     * 수량구간 할인 반영 단가.
     * price_tiers = [{min_qty, price}] 중 조건을 만족하는 가장 저렴한 단가 적용.
     */
    public function unitPriceFor(?User $user, int $qty = 1): int
    {
        $best = $this->priceFor($user);
        foreach ((array) $this->price_tiers as $tier) {
            $min = (int) ($tier['min_qty'] ?? 0);
            $tp  = (int) ($tier['price'] ?? 0);
            if ($min > 0 && $qty >= $min && $tp > 0 && $tp < $best) {
                $best = $tp;
            }
        }

        return $best;
    }

    /**
     * 가격 노출 가능 여부.
     * 소매가(정가)는 누구에게나 공개(exfresh 방식). 도매가는 priceFor()에서 승인 도매회원에게만 적용.
     * wholesale_only=true(도매 전용 상품)인 경우에만 비로그인/미승인에게 숨김.
     */
    public function priceVisibleFor(?User $user): bool
    {
        if (! $this->wholesale_only) {
            return true;
        }

        return $user && $user->isWholesale();
    }

    /** 판매 가능 상태(재고+판매상태) */
    public function isPurchasable(): bool
    {
        return $this->is_active
            && $this->sale_status === 'on_sale'
            && $this->stock > 0;
    }

    public function saleStatusLabel(): string
    {
        return match ($this->sale_status) {
            'soldout' => '품절',
            'closed'  => '판매마감',
            'inbound' => '입고예정',
            default   => $this->stock > 0 ? '판매중' : '품절',
        };
    }

    /** 해당 회원에게 도매 전용가(정가보다 낮은 가격)가 적용되는지 */
    public function hasSpecialPriceFor(?User $user): bool
    {
        return $user && $user->isApprovedBusiness() && $this->priceFor($user) < $this->price;
    }

    /** 정가 대비 할인율(%) — 주어진 판매가 기준 */
    public function discountRateFor(int $sell): int
    {
        if ($this->price > 0 && $sell < $this->price) {
            return (int) round(($this->price - $sell) / $this->price * 100);
        }

        return 0;
    }

    /** 기본 도매가 기준 할인율(%) — 비로그인/일반 표시용 */
    public function discountRate(): int
    {
        if ($this->member_price && $this->price > 0 && $this->member_price < $this->price) {
            return (int) round(($this->price - $this->member_price) / $this->price * 100);
        }

        return 0;
    }
}
