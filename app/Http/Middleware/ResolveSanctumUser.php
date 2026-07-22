<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API 요청의 기본 가드를 sanctum 으로 전환한다.
 *
 * 공개 API(/home, /products, /product/{slug} …)는 비로그인도 허용하지만
 * "로그인 시 회원가(도매가) 반영"이 요구사항이다. 기본 가드가 web(세션)이면
 * Bearer 토큰이 있어도 $request->user() 가 null 이 되어 승인 도매회원에게
 * 소매 정가가 내려간다. 이 미들웨어로 토큰이 해석되도록 한다.
 *
 * 토큰이 없으면 게스트로 그대로 통과하므로 공개 라우트 동작에는 영향이 없다.
 */
class ResolveSanctumUser
{
    public function handle(Request $request, Closure $next)
    {
        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
