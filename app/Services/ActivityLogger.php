<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * 방문이력(활동로그) 기록기.
 *  - 웹/앱 방문(페이지 접속), 상품검색, 상품조회, 로그인 이력을 activity_logs 에 남긴다.
 *  - 비로그인(게스트)은 session_id 로 추적한다.
 */
class ActivityLogger
{
    /** 페이지 방문 기록 (미들웨어에서 호출) */
    public static function visit(Request $request): void
    {
        static::write($request, 'visit', [
            'path' => static::path($request),
        ]);
    }

    /** 상품 검색 기록 */
    public static function search(Request $request, string $keyword, ?int $resultCount = null): void
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return; // 빈 검색은 기록하지 않음
        }
        static::write($request, 'search', [
            'keyword'      => mb_substr($keyword, 0, 191),
            'result_count' => $resultCount,
            'path'         => static::path($request),
        ]);
    }

    /** 상품 조회(선택) 기록 */
    public static function productView(Request $request, Product $product): void
    {
        static::write($request, 'product_view', [
            'product_id'   => $product->id,
            'product_name' => mb_substr((string) $product->name, 0, 191),
            'path'         => static::path($request),
        ]);
    }

    /** 로그인 시도 기록 (success | fail) */
    public static function login(Request $request, ?User $user, string $email, string $status): void
    {
        static::write($request, 'login', [
            'email'  => mb_substr($email, 0, 191),
            'status' => $status,
        ], $user);
    }

    /* ===== 내부 ===== */

    private static function write(Request $request, string $type, array $extra, ?User $user = null): void
    {
        try {
            $user = $user ?: $request->user();
            ActivityLog::create(array_merge([
                'type'       => $type,
                'user_id'    => $user?->id,
                'session_id' => static::sessionId($request),
                'platform'   => static::platform($request),
                'ip'         => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
                'referer'    => mb_substr((string) $request->headers->get('referer'), 0, 512) ?: null,
            ], $extra));
        } catch (\Throwable $e) {
            // 로깅 실패가 요청을 막지 않도록 무시
            report($e);
        }
    }

    private static function platform(Request $request): string
    {
        // 명시적 헤더 우선 → API 경로면 앱 → 그 외 웹
        $hdr = strtolower((string) $request->headers->get('X-Client-Platform'));
        if (in_array($hdr, ['app', 'web'], true)) {
            return $hdr;
        }

        return $request->is('api/*') ? 'app' : 'web';
    }

    private static function sessionId(Request $request): ?string
    {
        if ($request->hasSession()) {
            return $request->session()->getId();
        }

        return null;
    }

    private static function path(Request $request): string
    {
        $path = '/'.ltrim($request->path(), '/');
        $qs = $request->getQueryString();

        return mb_substr($qs ? $path.'?'.$qs : $path, 0, 512);
    }
}
