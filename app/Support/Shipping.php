<?php

namespace App\Support;

/**
 * 배송비 계산 — 기본 3,000원(제주 5,000원), 3박스 단위마다 +2,000원.
 *  예) 1~3박스 3,000 / 4~6박스 5,000 / 7~9박스 7,000 ...
 */
class Shipping
{
    public static function fee(int $boxes, ?string $postcode = null, ?string $address1 = null): int
    {
        $base  = self::isJeju($postcode, $address1)
            ? (int) config('site.shipping_fee_jeju', 5000)
            : (int) config('site.shipping_fee', 3000);
        $unit  = (int) config('site.shipping_box_unit', 3);   // 3박스 단위
        $extra = (int) config('site.shipping_box_extra', 2000);

        $boxes = max(1, $boxes);
        $units = (int) ceil($boxes / max(1, $unit));          // 3박스당 1구간

        return $base + ($units - 1) * $extra;
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
