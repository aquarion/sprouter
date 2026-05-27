<?php

namespace App\Mail;

use App\Models\Passkey;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasskeyInvalidated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Passkey $passkey,
        public readonly bool $automatic,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->automatic
                ? 'Security alert: a passkey was disabled on your account'
                : 'A passkey was removed from your account',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.passkey-invalidated',
        );
    }
}
