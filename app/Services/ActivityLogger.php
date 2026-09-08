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
        if ($keyword === '' || static::looksLikeInjection($keyword)) {
            return; // 빈 검색·인젝션 스캐너 키워드는 기록하지 않음
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

    /** 알려진 스캐너/크롤러 UA는 방문이력에서 제외(고객 통계 정확도) */
    private static function isBot(Request $request): bool
    {
        $ua = (string) $request->userAgent();
        if (trim($ua) === '') {
            return true; // UA 없는 요청은 대부분 봇/스크립트
        }

        return (bool) preg_match(
            '/(bot|crawl|spider|slurp|curl|wget|python-requests|python-urllib|scrapy|nikto|sqlmap|nmap|masscan|zgrab|semrush|ahrefs|mj12|dotbot|petalbot|bytespider|headlesschrome|phantomjs|go-http-client|libwww|httrack|censys|zmeu)/i',
            $ua
        );
    }

    /** SQL/스크립트 인젝션 스캐너가 검색창으로 넣는 패턴 */
    private static function looksLikeInjection(string $s): bool
    {
        return (bool) preg_match(
            '/(\bunion\b\s+select|select\s*\(|sleep\s*\(|benchmark\s*\(|waitfor\s+delay|\bxor\b|information_schema|concat\s*\(|0x[0-9a-f]{4}|--\s|\/\*|<script|onerror\s*=|\bor\b\s+\d+\s*=\s*\d+)/i',
            $s
        );
    }

    private static function write(Request $request, string $type, array $extra, ?User $user = null): void
    {
        // 봇/스캐너 트래픽은 기록하지 않음 (단, 로그인 실패 등은 이메일 기준으로 별도 판단)
        if (static::isBot($request)) {
            return;
        }
        // 스캐너 기본 이메일(로그인 등)은 제외
        if (! empty($extra['email']) && str_starts_with(strtolower($extra['email']), 'testing@example.com')) {
            return;
        }

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
