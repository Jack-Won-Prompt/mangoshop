<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);
        // 모바일 API — 공개 라우트에서도 Bearer 토큰을 해석해 회원가(도매가)를 반영
        $middleware->api(prepend: [
            \App\Http\Middleware\ResolveSanctumUser::class,
        ]);
        // 토스 웹훅은 외부 서버가 호출 → CSRF 제외
        $middleware->validateCsrfTokens(except: [
            'payment/toss/webhook',
            'payment/portone/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
