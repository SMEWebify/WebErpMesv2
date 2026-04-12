<?php

namespace App\Notifications;

use App\Models\Inspection\InspectionDocument;
use App\Notifications\Concerns\HasMailFrom;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InspectionDocumentApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable, HasMailFrom;

    public function __construct(private InspectionDocument $document, private int $submittedById) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->applyFrom(new MailMessage)
            ->subject('Approbation requise — Document ' . ($this->document->file_name ?? ''))
            ->view('emails.notification', [
                'notifiable'  => $notifiable,
                'subject'     => 'Approbation de document requise',
                'icon'        => '📄',
                'accentColor' => '#f59e0b',
                'entityLabel' => 'Document d\'inspection',
                'line'        => 'Un document a été soumis pour approbation et requiert votre validation.',
                'code'        => $this->document->file_name ?? '',
                'actionUrl'   => route('quality.inspection.projects'),
                'actionText'  => 'Voir les projets d\'inspection',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'id'      => $this->document->id,
            'code'    => $this->document->file_name ?? '',
            'user_id' => $this->submittedById,
        ];
    }
}
