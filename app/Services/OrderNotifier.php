<?php

namespace App\Services;

use App\Mail\SellerOrderMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * 주문 알림 — 결제완료 시 판매자에게 주문서 이메일 + SMS, 발송 시 구매자에게 SMS.
 * 모든 발송 실패는 삼켜서 주문/결제 흐름을 막지 않는다.
 */
class OrderNotifier
{
    public function __construct(private SmsSender $sms) {}

    /** 결제완료된 하위주문(=판매자 1) 을 해당 판매자에게 알림. */
    public function onPaid(Order $order): void
    {
        try {
            $order->loadMissing('items', 'seller', 'user');
            [$email, $phone, $name] = $this->recipient($order);

            $manageUrl = URL::temporarySignedRoute('seller.order.manage', now()->addDays(30), $order);

            // 이메일 주문서
            if ($email) {
                Mail::to($email)->send(new SellerOrderMail($order, $manageUrl, $name));
            }
            // 판매자 SMS
            if ($phone) {
                $this->sms->send($phone, $this->sellerSms($order), $name);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * 결제완료 시 관리자 앱으로 FCM 푸시 — 결제(묶음) 1건당 1회.
     * 대표(첫) 하위주문에서만 호출되며, 금액/건수는 그룹 전체 기준.
     */
    public function pushPaidToAdmins(Order $order): void
    {
        try {
            $adminIds = \App\Models\User::where('is_admin', true)->pluck('id');
            if ($adminIds->isEmpty()) {
                return;
            }

            $items  = $order->groupOrders()->with('items')->get()->flatMap->items;
            $first  = $items->first();
            $label  = ($first?->product_name ?? '상품');
            $label  = Str::limit($label, 20).($items->count() > 1 ? ' 외 '.($items->count() - 1).'건' : '');
            $amount = (int) $order->groupTotal();

            app(\App\Services\FcmService::class)->sendToUsers(
                $adminIds,
                '🛒 새 결제 완료',
                $label.' · '.number_format($amount).'원 · '.$order->order_no,
                ['type' => 'order_paid', 'order_no' => (string) $order->order_no, 'order_id' => (string) $order->id],
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** 발송(송장 등록) 시 구매자에게 SMS. */
    public function onShipped(Order $order): void
    {
        try {
            $to = $order->buyer_phone ?: $order->receiver_phone;
            $this->sms->send($to, $this->buyerShippedSms($order), $order->buyer_name ?: $order->receiver_name);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /* ===== 내부 ===== */

    /** [이메일, 전화, 표시명] — 판매자 주문이면 판매자, 본사 주문이면 사이트 관리자. */
    private function recipient(Order $order): array
    {
        if ($order->seller) {
            return [$order->seller->email, $order->seller->phone, $order->seller->name];
        }

        return [
            config('site.email') ?: config('mail.from.address'),
            config('popbill.sms.sender') ?: env('COMPANY_TEL'),
            (config('site.name') ?: '망고샵').' 본사',
        ];
    }

    private function itemLabel(Order $order): string
    {
        $first = $order->items->first();
        $name = $first?->product_name ?? '상품';
        $more = $order->items->count() > 1 ? ' 외 '.($order->items->count() - 1).'건' : '';

        return Str::limit($name, 20).$more;
    }

    private function sellerSms(Order $order): string
    {
        return sprintf('[%s] 새 주문 접수 %s / %s / %s원. 이메일의 주문확인 링크에서 배송처리 해주세요.',
            config('site.name') ?: '망고샵', $order->order_no, $this->itemLabel($order), number_format($order->total));
    }

    private function buyerShippedSms(Order $order): string
    {
        $eta = $order->desired_delivery_date ? ' 도착예정 '.$order->desired_delivery_date->format('n/j') : '';

        return sprintf('[%s] 주문 %s 상품이 발송되었습니다. %s %s%s',
            config('site.name') ?: '망고샵', $order->order_no, $order->courier, $order->tracking_no, $eta);
    }
}
