<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Mail de test envoyé depuis l'écran d'admin — content minimal, on cherche
 * juste à valider que le SMTP configuré accepte l'envoi.
 */
class TestConnectionMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail_settings.test_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>' . __('mail_settings.test_body') . '</p>',
        );
    }
}
