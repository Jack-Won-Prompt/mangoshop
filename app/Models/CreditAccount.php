<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditAccount extends Model
{
    protected $fillable = [
        'user_id', 'limit_amount', 'used_amount', 'terms', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(CreditTransaction::class)->latest();
    }

    /** 남은 여신 가능액 */
    public function available(): int
    {
        return max(0, (int) $this->limit_amount - (int) $this->used_amount);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** 여신 사용(주문) — 한도 초과 시 false */
    public function charge(int $amount, ?int $orderId = null, string $memo = ''): bool
    {
        if (! $this->isActive() || $amount > $this->available()) {
            return false;
        }
        $this->used_amount += $amount;
        $this->save();
        $this->transactions()->create([
            'order_id' => $orderId,
            'type'     => 'charge',
            'amount'   => $amount,
            'balance'  => $this->used_amount,
            'memo'     => $memo ?: '여신 주문',
        ]);

        return true;
    }

    /** 상환 */
    public function repay(int $amount, string $memo = ''): void
    {
        $this->used_amount = max(0, (int) $this->used_amount - $amount);
        $this->save();
        $this->transactions()->create([
            'type'    => 'repay',
            'amount'  => -$amount,
            'balance' => $this->used_amount,
            'memo'    => $memo ?: '여신 상환',
        ]);
    }
}
