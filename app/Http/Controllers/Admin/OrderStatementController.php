<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStatementService;
use Illuminate\Http\Request;

class OrderStatementController extends Controller
{
    public function __construct(private OrderStatementService $service) {}

    /** 새 탭 미리보기 (이력 없음) */
    public function preview(Order $order)
    {
        try {
            return $this->service->stream($order);
        } catch (\Throwable $e) {
            report($e);

            return response($this->errorHtml('거래명세서 미리보기 생성 실패', $e), 500)
                ->header('Content-Type', 'text/html; charset=utf-8');
        }
    }

    /** PDF 다운로드 (+ 이력) */
    public function download(Request $request, Order $order)
    {
        try {
            return $this->service->download($order, $request->user()?->id);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', '거래명세서 PDF 생성 실패: '.\Illuminate\Support\Str::limit($e->getMessage(), 200));
        }
    }

    /** 미리보기(새 탭)용 에러 화면 — 원인 파악을 위해 예외 메시지 노출(관리자 전용 라우트) */
    private function errorHtml(string $title, \Throwable $e): string
    {
        $msg  = e($e->getMessage());
        $at   = e($e->getFile().':'.$e->getLine());
        $hint = '폰트 캐시 쓰기 권한(storage/fonts) 또는 PHP memory_limit 을 확인하세요.';

        return "<!doctype html><meta charset=utf-8><body style=\"font-family:sans-serif;padding:32px;line-height:1.7;color:#333\">"
            ."<h2 style=color:#c0392b>⚠ {$title}</h2>"
            ."<p><b>".e(get_class($e))."</b><br>{$msg}</p>"
            ."<p style=color:#888;font-size:13px>{$at}</p>"
            ."<hr><p style=color:#666;font-size:13px>{$hint}</p></body>";
    }

    /** 이메일 발송 (+ 이력) */
    public function send(Request $request, Order $order)
    {
        $request->validate(['email' => ['nullable', 'email', 'max:150']]);

        $res = $this->service->email($order, $request->input('email'), $request->user()?->id);

        return $res['ok']
            ? back()->with('ok', $res['message'])
            : back()->with('error', $res['message']);
    }
}
