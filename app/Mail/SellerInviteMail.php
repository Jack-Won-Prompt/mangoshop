<?php

namespace App\Mail;

use App\Models\SellerInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SellerInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SellerInvite $invite) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[망고샵] 수입사 입점 초대 안내');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.seller-invite', with: [
            'invite'  => $this->invite,
            'acceptUrl' => $this->invite->url(),
            'siteName'  => config('app.name', '망고샵'),
        ]);
    }
}
