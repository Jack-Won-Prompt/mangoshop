<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * 판매자 주문 확인·배송처리 — 결제완료 알림 이메일의 서명 링크로 접근(로그인 불필요).
 * 배송예정일 + 송장 입력 시 구매자에게 SMS 발송.
 */
class SellerOrderController extends Controller
{
    /** 주문 확인 화면(서명 URL) */
    public function manage(Request $request, Order $order)
    {
        $order->load('items', 'seller');
        $shipUrl = URL::temporarySignedRoute('seller.order.ship', now()->addDays(30), $order);

        return view('seller-order.manage', compact('order', 'shipUrl'));
    }

    /** 배송예정일 확정 + 송장 등록 → 배송중 처리 + 구매자 SMS */
    public function ship(Request $request, Order $order)
    {
        $data = $request->validate([
            'courier'       => ['required', 'string', 'max:50'],
            'tracking_no'   => ['required', 'string', 'max:60'],
            'delivery_date' => ['nullable', 'date'],
        ]);

        $order->update([
            'courier'               => $data['courier'],
            'tracking_no'           => $data['tracking_no'],
            'desired_delivery_date' => $data['delivery_date'] ?? null,
            'shipped_at'            => now(),
            'status'                => in_array($order->status, ['cancelled', 'done']) ? $order->status : 'shipped',
        ]);

        // 구매자에게 발송 SMS
        app(OrderNotifier::class)->onShipped($order->fresh());

        return redirect()->to(URL::temporarySignedRoute('seller.order.manage', now()->addDays(30), $order))
            ->with('ok', '배송 정보가 저장되고 구매자에게 발송 안내 문자가 전송되었습니다.');
    }
}
