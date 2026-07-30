<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $pdfData,
        public string $fileName,
    ) {}

    public function build()
    {
        $site = config('site.name') ?: '망고샵';
        $this->order->loadMissing('items', 'user');

        return $this->subject('['.$site.'] 거래명세서 · '.$this->order->order_no)
            ->from(config('mail.from.address'), $site)
            ->view('emails.order-statement')
            ->attachData($this->pdfData, $this->fileName, ['mime' => 'application/pdf']);
    }
}
