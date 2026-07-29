<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SellerInvite extends Model
{
    protected $fillable = [
        'email', 'company_name', 'origin_focus', 'token', 'status',
        'invited_by', 'expires_at', 'accepted_at', 'accepted_user_id',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'accepted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SellerInvite $i) {
            $i->token = $i->token ?: Str::random(48);
            $i->expires_at = $i->expires_at ?: now()->addDays(14);
        });
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /** 아직 수락 가능한 유효 초대인지 */
    public function isUsable(): bool
    {
        return $this->status === 'pending' && ! $this->isExpired();
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->status === 'accepted' => '수락완료',
            $this->status === 'revoked'  => '취소됨',
            $this->isExpired()           => '만료됨',
            default                      => '대기중',
        };
    }

    public function url(): string
    {
        return route('seller.invite.show', $this->token);
    }
}
