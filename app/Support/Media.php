<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * 저장된 이미지 값(절대 URL/상대경로 혼재)을 현재 배포 호스트(APP_URL) 기준으로 정규화.
 * - 로컬(서브폴더 /mangoshop)과 운영(도메인 루트) 어디서든 동일 저장값이 올바르게 렌더되도록 함.
 * - 시드 시점에 박제된 http://localhost/mangoshop/... 절대 URL도 현재 호스트로 재구성.
 * - 외부 도메인 이미지는 원본 유지.
 */
class Media
{
    public static function url(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        if (Str::startsWith($value, 'data:')) {
            return $value;
        }

        // 절대 URL이면 호스트를 제거해 경로만 추출
        $path = preg_replace('#^https?://[^/]+#i', '', $value);

        if ($path === $value) {
            // 절대 URL이 아님 → 앱 상대경로로 간주하고 현재 호스트로 구성
            return asset(ltrim($value, '/'));
        }

        // 앱 서브폴더/public 접두사 제거
        $norm = preg_replace('#^/(mangoshop|mangonara|medisell)(?=/)#', '', $path);
        $norm = preg_replace('#^/public(?=/)#', '', $norm);

        // 우리 로컬 자산 경로만 현재 호스트로 재구성, 그 외(외부 도메인)는 원본 유지
        if ($norm !== $path || preg_match('#/(images|product|products|uploads|storage)/#', $norm)) {
            return asset(ltrim($norm, '/'));
        }

        return $value;
    }
}
