<?php

namespace App\Services;

use App\Models\AgentBuyer;
use App\Models\AgentCashback;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 체크아웃 → 셀러별 하위주문 분할 생성(①: 결제묶음 + 셀러별 하위주문).
 *  - 같은 결제 1건을 order_group_no 로 묶고, 판매자별 Order 를 생성.
 *  - 배송비는 0(주문 후 별도 정산)이라 금액 배분은 상품합계 비례로 단순.
 *  - 쿠폰할인/적립금 사용은 그룹 단위 → 하위주문에 비례 배분(잔여는 대표주문).
 *  - 대표(첫) 주문을 반환 → 결제/완료 라우트의 앵커로 사용.
 */
class OrderPlacement
{
    public function place(User $user, Collection $items, array $data, bool $isPg, ?Coupon $coupon, int $couponDiscount, int $pointUsed): Order
    {
        $isAgent = $user->isAgent();

        // 셀러별 그룹핑 (product.seller_id, null → 0=본사)
        $groups = $items->groupBy(fn ($i) => $i->product->seller_id ?? 0);
        $subtotals = $groups->map(fn ($g) => (int) $g->sum(fn ($i) => $i->product->priceFor($user) * $i->quantity));
        $grand = (int) $subtotals->sum();
        $keys = $subtotals->keys()->values();

        $discAlloc = $this->allocate($couponDiscount, $subtotals, $grand, $keys);
        $pointAlloc = $this->allocate($pointUsed, $subtotals, $grand, $keys);

        return DB::transaction(function () use ($user, $groups, $data, $isPg, $coupon, $couponDiscount, $pointUsed, $isAgent, $subtotals, $discAlloc, $pointAlloc, $keys) {
            $groupNo = 'MSG'.now()->format('ymd').strtoupper(substr(uniqid(), -6));
            $primary = null;
            $idx = 0;

            foreach ($keys as $key) {
                $idx++;
                $sellerId = ((int) $key) === 0 ? null : (int) $key;
                $sub = (int) $subtotals[$key];
                $disc = (int) $discAlloc[$key];
                $point = (int) $pointAlloc[$key];
                $total = max(0, $sub - $disc - $point);
                $cashback = $isAgent ? (int) round($total * ((float) $user->cashback_rate) / 100) : 0;

                $order = Order::create([
                    'order_no'        => 'MS'.now()->format('ymd').strtoupper(substr(uniqid(), -5)).($idx > 1 ? '-'.$idx : ''),
                    'order_group_no'  => $groupNo,
                    'seller_id'       => $sellerId,
                    'user_id'         => $user->id,
                    'agent_id'        => $isAgent ? $user->id : null,
                    'buyer_name'      => $isAgent ? ($data['buyer_name'] ?? null) : null,
                    'buyer_biz_no'    => $isAgent ? ($data['buyer_biz_no'] ?? null) : null,
                    'buyer_phone'     => $isAgent ? ($data['buyer_phone'] ?? null) : null,
                    'cashback_amount' => $cashback,
                    'status'          => 'pending',
                    'payment_method'  => $data['payment_method'],
                    'pay_provider'    => $isPg ? $data['payment_method'] : null,
                    'receiver_name'   => $data['receiver_name'],
                    'receiver_phone'  => $data['receiver_phone'],
                    'postcode'        => $data['postcode'] ?? null,
                    'address1'        => $data['address1'],
                    'address2'        => $data['address2'] ?? null,
                    'memo'            => $data['memo'] ?? null,
                    'subtotal'        => $sub,
                    'shipping_fee'    => 0,
                    'discount'        => $disc,
                    'coupon_id'       => $coupon?->id,
                    'coupon_code'     => $coupon?->code,
                    'point_used'      => $point,
                    'total'           => $total,
                    'bank'            => $isPg ? null : ($data['bank'] ?? null),
                    'depositor'       => $isPg ? null : ($data['depositor'] ?? null),
                ]);
                $primary ??= $order;

                foreach ($groups[$key] as $i) {
                    $price = $i->product->priceFor($user);
                    $order->items()->create([
                        'seller_id'    => $sellerId,
                        'product_id'   => $i->product_id,
                        'product_name' => $i->product->name,
                        'unit'         => $i->product->unit,
                        'price'        => $price,
                        'quantity'     => $i->quantity,
                        'subtotal'     => $price * $i->quantity,
                    ]);
                    $i->product->decrement('stock', min($i->quantity, $i->product->stock));
                }

                // 구매 대행자 캐시백: 하위주문별 즉시 적립
                if ($isAgent && $cashback > 0) {
                    AgentCashback::create([
                        'agent_id'     => $user->id,
                        'order_id'     => $order->id,
                        'buyer_name'   => $data['buyer_name'] ?? null,
                        'order_amount' => $total,
                        'rate'         => $user->cashback_rate,
                        'amount'       => $cashback,
                        'status'       => 'paid',
                    ]);
                    $user->adjustPoint($cashback, "구매대행 캐시백 ({$order->order_no})", $order->id);
                }
            }

            // 적립금 사용 차감(그룹 1회, 대표주문 참조)
            if ($pointUsed > 0) {
                $user->adjustPoint(-$pointUsed, "주문 사용 ({$primary->order_no})", $primary->id);
            }

            // 쿠폰 사용 확정(그룹 1회, 대표주문 참조)
            if ($coupon) {
                $coupon->increment('used_count');
                \App\Models\CouponRedemption::create([
                    'coupon_id' => $coupon->id, 'user_id' => $user->id,
                    'order_id' => $primary->id, 'discount' => $couponDiscount,
                ]);
                if (! $coupon->is_public) {
                    $coupon->userCoupons()->where('user_id', $user->id)->whereNull('used_at')
                        ->limit(1)->update(['used_at' => now(), 'order_id' => $primary->id]);
                }
            }

            // 신규 구매자(소매처) 명부 저장
            if ($isAgent && ! empty($data['save_buyer']) && ! empty($data['buyer_name'])) {
                AgentBuyer::firstOrCreate(
                    ['agent_id' => $user->id, 'name' => $data['buyer_name'], 'biz_no' => $data['buyer_biz_no'] ?? null],
                    ['phone' => $data['buyer_phone'] ?? null],
                );
            }

            $user->cartItems()->delete();

            return $primary;
        });
    }

    /** 정수 금액을 그룹 상품합계 비례로 배분(잔여는 첫 키에 보정). */
    private function allocate(int $amount, Collection $subtotals, int $grand, Collection $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = 0;
        }
        if ($amount <= 0 || $grand <= 0) {
            return $out;
        }
        $acc = 0;
        foreach ($keys as $k) {
            $a = (int) floor($amount * ((int) $subtotals[$k]) / $grand);
            $out[$k] = $a;
            $acc += $a;
        }
        $out[$keys->first()] += $amount - $acc;

        return $out;
    }
}
