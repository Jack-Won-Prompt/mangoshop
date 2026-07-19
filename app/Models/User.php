<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name', 'email', 'password',
        'member_type', 'phone', 'postcode', 'address1', 'address2',
        'company_name', 'biz_no', 'biz_type', 'biz_status', 'grade',
        'point', 'is_admin', 'is_agent', 'cashback_rate',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
            'is_agent'          => 'boolean',
            'cashback_rate'     => 'decimal:2',
        ];
    }

    /** 구매 대행자 여부 */
    public function isAgent(): bool
    {
        return (bool) $this->is_agent;
    }

    /** 대행자가 관리하는 구매자(소매처) 명부 */
    public function buyers()
    {
        return $this->hasMany(AgentBuyer::class, 'agent_id')->latest();
    }

    /** 대행자 캐시백 원장 */
    public function cashbacks()
    {
        return $this->hasMany(AgentCashback::class, 'agent_id')->latest();
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function pointLogs()
    {
        return $this->hasMany(PointLog::class)->latest();
    }

    public function contractPrices()
    {
        return $this->hasMany(ContractPrice::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class)->orderByDesc('is_default')->orderByDesc('id');
    }

    public function defaultAddress(): ?UserAddress
    {
        return $this->addresses()->where('is_default', true)->first()
            ?? $this->addresses()->first();
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class)->latest();
    }

    public function creditAccount()
    {
        return $this->hasOne(CreditAccount::class);
    }

    public function restockAlerts()
    {
        return $this->hasMany(RestockAlert::class);
    }

    /** 입점 수입사(판매자 콘솔용) — 이 회원이 운영하는 수입사 */
    public function seller()
    {
        return $this->hasOne(Seller::class);
    }

    public function userCoupons()
    {
        return $this->hasMany(UserCoupon::class);
    }

    /** 발행받은 미사용 쿠폰(유효한 것) 목록 */
    public function availableCoupons()
    {
        return UserCoupon::with('coupon')
            ->where('user_id', $this->id)
            ->whereNull('used_at')
            ->get()
            ->filter(fn ($uc) => $uc->coupon && $uc->coupon->is_active
                && (! $uc->coupon->ends_at || $uc->coupon->ends_at->isFuture())
                && (! $uc->coupon->starts_at || $uc->coupon->starts_at->isPast()))
            ->values();
    }

    /** 도매(사업자) 회원 구분 — 'wholesale'(신규) 또는 'business'(레거시) */
    public function isWholesaleType(): bool
    {
        return in_array($this->member_type, ['wholesale', 'business'], true);
    }

    /** 승인된 도매 회원 여부 — 도매가/여신/전용가 적용 대상 */
    public function isWholesale(): bool
    {
        return $this->isWholesaleType() && $this->biz_status === 'approved';
    }

    /** 도매 회원인데 아직 승인 대기/반려 상태 */
    public function isPendingWholesale(): bool
    {
        return $this->isWholesaleType() && $this->biz_status !== 'approved';
    }

    public function isRetail(): bool
    {
        return ! $this->isWholesaleType();
    }

    /** 승인된 사업자(도매) 회원 여부 — 전용가 적용 대상(레거시 명칭 유지) */
    public function isApprovedBusiness(): bool
    {
        return $this->isWholesale();
    }

    /** 가독성 별칭 */
    public function isHospital(): bool
    {
        return $this->isApprovedBusiness();
    }

    public function memberTypeLabel(): string
    {
        return $this->isWholesaleType() ? '도매회원' : '소매회원';
    }

    /** 도매 전용 단가 맵 [product_id => price] (요청당 1회 조회) */
    protected ?array $priceMapCache = null;

    public function priceMap(): array
    {
        if ($this->priceMapCache === null) {
            $this->priceMapCache = $this->isApprovedBusiness()
                ? $this->contractPrices()->pluck('price', 'product_id')->all()
                : [];
        }

        return $this->priceMapCache;
    }

    /** 적립금 변동 + 로그 기록 */
    public function adjustPoint(int $amount, string $reason, ?int $orderId = null): void
    {
        $this->point = max(0, $this->point + $amount);
        $this->save();

        $this->pointLogs()->create([
            'amount'   => $amount,
            'balance'  => $this->point,
            'reason'   => $reason,
            'order_id' => $orderId,
        ]);
    }
}
