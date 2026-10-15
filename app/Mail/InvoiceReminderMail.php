<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Relance d'un client : une ou plusieurs factures, chacune jointe en PDF.
 * Porteur seul, comme DocumentMail : InvoiceReminderService décide de tout.
 */
class InvoiceReminderMail extends Mailable
{
    /**
     * @param array<int, array{name:string, bytes:string}> $pdfs
     */
    public function __construct(
        public string $subjectText,
        public string $messageContent,
        public string $fromAddress,
        public string $fromName,
        public ?string $replyToAddress = null,
        public ?string $replyToName = null,
        public array $pdfs = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from:    new Address($this->fromAddress, $this->fromName),
            replyTo: $this->replyToAddress
                ? [new Address($this->replyToAddress, $this->replyToName ?: $this->replyToAddress)]
                : [],
            subject: $this->subjectText,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invoice-reminder');
    }

    public function attachments(): array
    {
        return array_map(
            fn (array $pdf) => Attachment::fromData(fn () => $pdf['bytes'], $pdf['name'])->withMime('application/pdf'),
            $this->pdfs
        );
    }
}
