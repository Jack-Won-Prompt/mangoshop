<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 고객 대상 주문 메일.
 *  - mode 'paid' : 결제완료 주문서 + 영수증
 *  - mode 'bank' : 무통장 주문접수 + 입금 안내
 */
class OrderCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $mode = 'paid') {}

    public function build()
    {
        $site = config('site.name') ?: '망고샵';
        $subject = $this->mode === 'bank'
            ? '['.$site.'] 주문이 접수되었습니다 (입금 안내) · '.$this->order->order_no
            : '['.$site.'] 결제가 완료되었습니다 · '.$this->order->order_no;

        $items = $this->order->groupOrders()->with('items')->get()->flatMap->items;

        return $this->subject($subject)
            ->from(config('mail.from.address'), $site)
            ->view('emails.order-customer', [
                'order'  => $this->order,
                'mode'   => $this->mode,
                'items'  => $items,
                'amount' => $this->order->groupTotal(),
            ]);
    }
}
