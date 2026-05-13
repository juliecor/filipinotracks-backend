<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpLogin extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $name,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your FilipinoTracks Login Code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp_login');
    }

    public function attachments(): array
    {
        return [];
    }
}
