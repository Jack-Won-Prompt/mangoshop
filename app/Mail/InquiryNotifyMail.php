<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 고객 문의 접수 → 관리자 알림 메일.
 */
class InquiryNotifyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Inquiry $inquiry) {}

    public function build()
    {
        $site = config('site.name') ?: '망고샵';
        $typeLabel = Inquiry::TYPES[$this->inquiry->type] ?? $this->inquiry->type;

        return $this->subject('['.$site.'] 새 고객문의('.$typeLabel.') · '.$this->inquiry->subject)
            ->from(config('mail.from.address'), $site)
            ->replyTo($this->inquiry->email ?: config('mail.from.address'), $this->inquiry->name)
            ->view('emails.inquiry-notify');
    }
}
