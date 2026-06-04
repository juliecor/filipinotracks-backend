<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Inquiry $inquiry,
        public string $replyMessage,
        public string $staffName,
    ) {}

    public function envelope(): Envelope
    {
        $code = $this->inquiry->transaction?->transaction_code;
        return new Envelope(
            subject: 'Re: Your property inquiry with FilipinoTracks' . ($code ? " ({$code})" : ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inquiry_reply',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
