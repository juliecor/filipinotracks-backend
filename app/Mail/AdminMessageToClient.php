<?php

namespace App\Mail;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * On-demand email an admin/staff member sends to the client tied to a
 * specific transaction. The dashboard "Email Client" button triggers this.
 */
class AdminMessageToClient extends Mailable
{
    use Queueable, SerializesModels;

    public Transaction $transaction;
    public User $sender;
    public string $messageSubject;
    public string $messageBody;
    public ?string $verifyUrl;
    public ?string $staticMapUrl;
    /** @var array<array{data:string,name:string,mime:string}> */
    public array $attachedFiles;

    public function __construct(
        Transaction $transaction,
        User $sender,
        string $subject,
        string $body,
        array $attachedFiles = [],
    ) {
        $this->transaction    = $transaction->load('user', 'propertyMap');
        $this->sender         = $sender;
        $this->messageSubject = $subject;
        $this->messageBody    = $body;
        $this->attachedFiles  = $attachedFiles;
        $this->verifyUrl      = $this->buildVerifyUrl();
        $this->staticMapUrl   = $this->transaction->propertyMap?->staticMapUrl();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject:  $this->messageSubject . ' — ' . $this->transaction->transaction_code,
            replyTo: [$this->sender->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin_message_to_client',
        );
    }

    public function attachments(): array
    {
        return array_map(
            fn(array $file) => Attachment::fromData(fn() => $file['data'], $file['name'])
                ->withMime($file['mime']),
            $this->attachedFiles,
        );
    }

    private function buildVerifyUrl(): ?string
    {
        $base = rtrim(env('FRONTEND_URL', config('app.url')), '/');
        return $base . '/portal/transactions/' . $this->transaction->id;
    }
}
