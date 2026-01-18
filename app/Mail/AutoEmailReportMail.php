<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;

class AutoEmailReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $reportTitle;
    public array $reportData;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $reportTitle, array $reportData)
    {
        $this->user = $user;
        $this->reportTitle = $reportTitle;
        $this->reportData = $reportData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: __('general_content.automatic_email_report_subject_trans_key', ['report' => $this->reportTitle]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auto-report',
            with: [
                'user' => $this->user,
                'reportTitle' => $this->reportTitle,
                'reportData' => $this->reportData,
            ],
        );
    }
}
