<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $manageUrl,
        public ?string $sellerName = null,
    ) {}

    public function build()
    {
        $site = config('site.name') ?: '망고샵';
        $this->order->loadMissing('items');

        return $this->subject('['.$site.'] 새 주문 접수 · '.$this->order->order_no)
            ->from(config('mail.from.address'), $site)
            ->view('emails.seller-order');
    }
}
