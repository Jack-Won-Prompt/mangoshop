<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** 고객 대상 배송 시작(송장 등록) 메일. */
class OrderShippedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function build()
    {
        $site = config('site.name') ?: '망고샵';
        $track = 'https://search.naver.com/search.naver?query='.rawurlencode(($this->order->courier ?: '').' '.$this->order->tracking_no);

        return $this->subject('['.$site.'] 상품이 발송되었습니다 · '.$this->order->order_no)
            ->from(config('mail.from.address'), $site)
            ->view('emails.order-shipped', [
                'order' => $this->order,
                'items' => $this->order->items,
                'track' => $track,
            ]);
    }
}
