<?php

namespace App\Mail;

use App\Models\SellerInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SellerInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SellerInvite $invite) {}

    public function envelope(): Envelope
    {
        $siteName = config('app.name', '망고샵');

        return new Envelope(
            from: new Address(config('mail.from.address'), $siteName),
            subject: '['.$siteName.'] 수입사 입점 초대 — 함께하실 파트너를 찾습니다',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.seller-invite', with: [
            'invite'    => $this->invite,
            'acceptUrl' => $this->invite->url(),
            'siteName'  => config('app.name', '망고샵'),
            'site'      => config('site'),
        ]);
    }
}
