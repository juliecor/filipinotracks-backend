<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewPropertyInquiry extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Inquiry $inquiry,
    ) {}

    public function envelope(): Envelope
    {
        $code = $this->inquiry->transaction?->transaction_code ?? 'a property';
        return new Envelope(
            subject: "New property inquiry from {$this->inquiry->name} — {$code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new_inquiry',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
