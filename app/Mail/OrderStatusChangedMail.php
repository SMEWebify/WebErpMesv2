<?php

namespace App\Mail;

use App\Models\Admin\Factory;
use App\Models\Workflow\Orders;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Mail envoyé au contact client quand sa commande change d'étape.
 *
 * Déclenché par SendOrderStatusEmail, uniquement pour les sociétés qui ont
 * activé `companies.order_status_email`.
 */
class OrderStatusChangedMail extends Mailable
{
    public function __construct(
        public Orders $order,
        public int $status,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: $this->subjectText(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-status',
            with: [
                'order'       => $this->order,
                'statusLabel' => self::statusLabel($this->status),
                'intro'       => __('general_content.order_status_email_intro_' . $this->status . '_trans_key', [
                    'order' => $this->order->code,
                ]),
                'factory'     => Factory::first(),
            ],
        );
    }

    public function subjectText(): string
    {
        return __('general_content.order_status_email_subject_trans_key', [
            'order'  => $this->order->code,
            'status' => self::statusLabel($this->status),
        ]);
    }

    /**
     * Libellés alignés sur les étapes affichées dans orders-show.blade.php.
     */
    public static function statusLabel(int $status): string
    {
        return match ($status) {
            1       => __('general_content.open_trans_key'),
            2       => __('general_content.in_progress_trans_key'),
            3       => __('general_content.delivered_trans_key'),
            4       => __('general_content.partly_delivered_trans_key'),
            5       => __('general_content.stopped_trans_key'),
            6       => __('general_content.canceled_trans_key'),
            default => (string) $status,
        };
    }
}
