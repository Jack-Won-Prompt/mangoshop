<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_no', 'order_group_no', 'user_id', 'seller_id', 'status', 'payment_method',
        'pay_provider', 'payment_key', 'pay_status', 'pay_method',
        'receiver_name', 'receiver_phone', 'postcode', 'address1', 'address2', 'memo',
        'desired_delivery_date', 'is_credit',
        'agent_id', 'buyer_name', 'buyer_biz_no', 'buyer_phone', 'cashback_amount',
        'subtotal', 'shipping_fee', 'discount', 'coupon_id', 'coupon_code', 'point_used', 'total',
        'bank', 'depositor', 'paid_at',
        'va_bank', 'va_account', 'va_holder', 'va_due_at',
        'courier', 'tracking_no', 'shipped_at', 'cancelled_at', 'cancel_reason',
    ];

    protected $casts = [
        'paid_at'                => 'datetime',
        'va_due_at'              => 'datetime',
        'shipped_at'             => 'datetime',
        'cancelled_at'           => 'datetime',
        'desired_delivery_date'  => 'date',
        'is_credit'              => 'boolean',
    ];

    public const STATUSES = [
        'pending'   => '입금대기',
        'paid'      => '입금확인',
        'preparing' => '상품준비중',
        'shipped'   => '배송중',
        'done'      => '배송완료',
        'cancelled' => '취소',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    /** 구매 대행자 */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function cashback()
    {
        return $this->hasOne(AgentCashback::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /** 같은 결제 묶음(여러 수입사) 형제 주문. 그룹번호가 없으면(레거시) 자기 자신만. */
    public function groupOrders()
    {
        if (! $this->order_group_no) {
            return static::whereKey($this->id);
        }

        return static::where('order_group_no', $this->order_group_no);
    }

    /** 셀러 정산건 */
    public function settlements()
    {
        return $this->hasMany(SellerSettlement::class);
    }

    /** 결제 묶음 합계(형제 주문 total 합) */
    public function groupTotal(): int
    {
        return (int) $this->groupOrders()->sum('total');
    }

    /** 결제 묶음 전체 결제완료 처리(팬아웃) */
    public function markPaidGroup(): void
    {
        $this->groupOrders()->get()->each->markPaid();
    }

    public function taxInvoices()
    {
        return $this->hasMany(TaxInvoice::class)->latest();
    }

    /** 거래명세서 발행 이력 */
    public function statements()
    {
        return $this->hasMany(OrderStatement::class)->latest();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * 주문 취소 — 재고 복구 + 적립금(사용분 반환/적립분 회수) + 토스 결제 시 환불.
     * 반환: ['ok'=>bool, 'message'=>?string]
     */
    public function cancel(string $reason = '주문취소'): array
    {
        if ($this->status === 'cancelled') {
            return ['ok' => true];
        }

        // 토스 결제완료 건이면 먼저 환불 (실패 시 중단)
        if ($this->pay_provider === 'toss' && $this->payment_key && $this->paid_at) {
            $res = app(\App\Services\TossPayments::class)->cancel($this->payment_key, $reason);
            if (! empty($res['error'])) {
                return ['ok' => false, 'message' => $res['message'] ?? '결제 취소에 실패했습니다.'];
            }
        }

        // 재고 복구
        $this->loadMissing('items');
        foreach ($this->items as $it) {
            if ($it->product_id) {
                Product::where('id', $it->product_id)->increment('stock', $it->quantity);
            }
        }

        // 사용 적립금 반환
        if ($this->point_used > 0 && $this->user) {
            $this->user->adjustPoint($this->point_used, "주문취소 적립금 반환 ({$this->order_no})", $this->id);
        }
        // 결제완료였다면 구매 적립금 회수
        if ($this->paid_at && $this->user) {
            $earned = (int) floor($this->total * config('site.point_rate', 0) / 100);
            if ($earned > 0) {
                $this->user->adjustPoint(-$earned, "주문취소 적립금 회수 ({$this->order_no})", $this->id);
            }
        }

        // 쿠폰 사용 롤백 (사용횟수 차감 + 사용기록 삭제 → 재사용 가능)
        if ($this->coupon_id) {
            Coupon::where('id', $this->coupon_id)->where('used_count', '>', 0)->decrement('used_count');
            CouponRedemption::where('order_id', $this->id)->delete();
            // 발행형 쿠폰 발행분 사용해제 → 다시 사용 가능
            UserCoupon::where('order_id', $this->id)->update(['used_at' => null, 'order_id' => null]);
        }

        $this->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancel_reason' => $reason,
        ]);

        return ['ok' => true];
    }

    /** 결제완료(입금확인) 처리 — 결제일 기록 + 구매 적립금 지급 (1회만) */
    public function markPaid(): void
    {
        if ($this->status === 'paid' || $this->paid_at) {
            return;
        }
        $this->status = 'paid';
        $this->paid_at = now();
        $this->save();

        if ($this->user) {
            $point = (int) floor($this->total * config('site.point_rate', 0) / 100);
            if ($point > 0) {
                $this->user->adjustPoint($point, "구매 적립 ({$this->order_no})", $this->id);
            }
        }

        $this->createSettlement();

        // 판매자에게 주문서 이메일 + SMS (결제완료 1회). 실패해도 결제 흐름 유지.
        app(\App\Services\OrderNotifier::class)->onPaid($this);
    }

    /** 셀러 정산건 생성 — 입점 수입사 주문에 한함, 1회만(본사 직접판매는 정산 없음) */
    protected function createSettlement(): void
    {
        if (! $this->seller_id) {
            return;
        }
        if (SellerSettlement::where('order_id', $this->id)->exists()) {
            return;
        }
        $rate = (float) ($this->seller?->commission_rate ?? 0);
        $gross = (int) $this->total;
        $commission = (int) round($gross * $rate / 100);

        SellerSettlement::create([
            'seller_id'         => $this->seller_id,
            'order_id'          => $this->id,
            'gross_amount'      => $gross,
            'commission_amount' => $commission,
            'net_amount'        => $gross - $commission,
            'status'            => 'pending',
        ]);
    }
}
