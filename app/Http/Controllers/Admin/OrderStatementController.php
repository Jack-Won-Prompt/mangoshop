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
        return $this->service->stream($order);
    }

    /** PDF 다운로드 (+ 이력) */
    public function download(Request $request, Order $order)
    {
        return $this->service->download($order, $request->user()?->id);
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
