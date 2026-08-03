<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 방문(페이지 접속) 이력 기록 — 웹/앱 공용.
 *  - GET 페이지 요청만, 정상(2xx) 응답에 한해 기록.
 *  - 관리자/셀러 콘솔, 정적파일, AJAX 부분요청, 상품검색·상품조회(별도 타입) 및
 *    앱 유틸리티(설정·푸시·장바구니 등 폴링성) 엔드포인트는 제외.
 */
class TrackVisit
{
    /** 방문으로 기록하지 않을 경로 접두사(관리/스태프 영역) */
    private array $skipPrefixes = ['admin', 'seller', 'up', 'payment', 'storage', 'vendor', 'css', 'js', 'images', 'build'];

    /** 별도 타입으로 이미 기록되는 라우트명(중복 방지) */
    private array $skipRouteNames = ['catalog.search', 'catalog.show'];

    /** 앱(API) 유틸리티/폴링성 경로(방문 제외) */
    private array $skipApiSegments = ['settings', 'push', 'cart', 'wishlist', 'checkout', 'orders', 'auth', 'products/search'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if ($this->shouldLog($request, $response)) {
                ActivityLogger::visit($request);
            }
        } catch (\Throwable $e) {
            // 기록 실패는 무시
        }

        return $response;
    }

    private function shouldLog(Request $request, Response $response): bool
    {
        if ($request->method() !== 'GET') {
            return false;
        }
        // 정상 응답만 (리다이렉트·에러 제외)
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return false;
        }

        $path = ltrim($request->path(), '/');
        $isApi = $request->is('api/*');

        // 스태프/정적 영역 제외
        $firstSeg = explode('/', $path)[0] ?? '';
        if (in_array($firstSeg, $this->skipPrefixes, true)) {
            return false;
        }
        // 확장자(정적파일) 제외
        if (preg_match('/\.\w{2,5}$/', $path)) {
            return false;
        }
        // 별도 타입으로 기록되는 라우트 제외
        if (in_array($request->route()?->getName(), $this->skipRouteNames, true)) {
            return false;
        }

        if ($isApi) {
            // api/ 접두 제거 후 버전(v1) 제거
            $rel = preg_replace('#^api/(v\d+/)?#', '', $path);
            foreach ($this->skipApiSegments as $seg) {
                if ($rel === $seg || str_starts_with($rel, $seg.'/') || str_starts_with($rel, 'product/')) {
                    return false;
                }
            }
            // JSON 응답만 앱 방문으로 인정
            return true;
        }

        // 웹: AJAX 부분요청 제외, HTML 응답만
        if ($request->ajax() || $request->wantsJson()) {
            return false;
        }
        $ctype = (string) $response->headers->get('Content-Type');

        return $ctype === '' || str_contains($ctype, 'text/html');
    }
}
