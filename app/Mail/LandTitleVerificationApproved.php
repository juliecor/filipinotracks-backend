<?php

namespace App\Mail;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when an admin marks a Land / Title Verification transaction as
 * "approved". The email embeds a Google Static Maps image showing the
 * verified property's location (satellite view + drawn polygon).
 */
class LandTitleVerificationApproved extends Mailable
{
    use Queueable, SerializesModels;

    public Transaction $transaction;
    public ?string $staticMapUrl;
    public ?string $verifyUrl;

    public function __construct(Transaction $transaction)
    {
        $this->transaction  = $transaction->load('user', 'propertyMap', 'assignedStaff');
        $this->staticMapUrl = $this->transaction->propertyMap?->staticMapUrl();
        $this->verifyUrl    = $this->buildVerifyUrl();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✓ Your property verification is approved — ' . $this->transaction->transaction_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.land_title_verification_approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function buildVerifyUrl(): ?string
    {
        $base = rtrim(env('FRONTEND_URL', config('app.url')), '/');
        return $base . '/portal/transactions/' . $this->transaction->id;
    }
}
