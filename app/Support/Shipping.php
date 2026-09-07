<?php

namespace App\Support;

/**
 * 배송비 계산 — 건당(배송 1건) 고정 3,000원(제주 5,000원).
 *  수량/박스 수와 무관하게 배송 건당 동일 요금.
 */
class Shipping
{
    public static function fee(int $boxes = 1, ?string $postcode = null, ?string $address1 = null): int
    {
        return self::isJeju($postcode, $address1)
            ? (int) config('site.shipping_fee_jeju', 5000)
            : (int) config('site.shipping_fee', 3000);
    }

    /** 제주(우편번호 63xxx) 또는 주소에 '제주' 포함 */
    public static function isJeju(?string $postcode, ?string $address1): bool
    {
        $pc = preg_replace('/\D/', '', (string) $postcode);
        if (strlen($pc) >= 2 && str_starts_with($pc, '63')) {
            return true;
        }

        return str_contains((string) $address1, '제주');
    }
}
