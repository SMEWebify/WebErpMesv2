<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Mail d'envoi d'un document (devis / commande / facture…).
 *
 * L'expéditeur, le reply-to, la pièce jointe manuelle et le PDF auto-généré
 * sont fournis par EmailController : le mailable n'est qu'un porteur, il ne
 * décide plus rien tout seul (avant, le "from" était figé sur .env, ce qui
 * empêchait de configurer un expéditeur par instance).
 */
class DocumentMail extends Mailable
{
    public function __construct(
        public $document,
        public string $subjectText,
        public string $messageContent,
        public string $fromAddress,
        public string $fromName,
        public ?string $replyToAddress = null,
        public ?string $replyToName = null,
        public ?string $manualAttachmentPath = null,
        public ?string $manualAttachmentName = null,
        public ?string $pdfBytes = null,
        public ?string $pdfFileName = null,
    ) {}

    public function envelope(): Envelope
    {
        $envelope = new Envelope(
            from:    new Address($this->fromAddress, $this->fromName),
            replyTo: $this->replyToAddress
                ? [new Address($this->replyToAddress, $this->replyToName ?: $this->replyToAddress)]
                : [],
            subject: $this->subjectText,
        );

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document',
            with: [
                'messageContent' => $this->messageContent,
                'document'       => $this->document,
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->pdfBytes !== null) {
            $attachments[] = Attachment::fromData(fn () => $this->pdfBytes, $this->pdfFileName ?? 'document.pdf')
                ->withMime('application/pdf');
        }

        if ($this->manualAttachmentPath !== null) {
            $attachments[] = Attachment::fromPath(storage_path('app/' . $this->manualAttachmentPath))
                ->as($this->manualAttachmentName ?? basename($this->manualAttachmentPath));
        }

        return $attachments;
    }
}
