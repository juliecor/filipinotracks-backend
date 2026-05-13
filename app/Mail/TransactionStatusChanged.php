<?php

namespace App\Mail;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public string $oldStatus,
        public string $newStatus,
        public ?string $remarks = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Transaction {$this->transaction->transaction_code} — Status Updated",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.transaction_status',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
