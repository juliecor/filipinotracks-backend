<?php

namespace App\Mail;

use App\Models\Message;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public Message $message,
        public string $senderName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New message from {$this->senderName} — {$this->transaction->transaction_code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new_message',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
