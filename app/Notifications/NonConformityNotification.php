<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Concerns\HasMailFrom;

class NonConformityNotification extends Notification
{
    use Queueable, HasMailFrom;
    private $data;

    /**
     * Create a new notification instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        $channels = [];
        if ($notifiable->non_conformity_notification)       $channels[] = 'database';
        if ($notifiable->non_conformity_email_notification) $channels[] = 'mail';
        return $channels ?: ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->applyFrom(new MailMessage)
            ->subject('Nouvelle non-conformité — ' . ($this->data['code'] ?? ''))
            ->view('emails.notification', [
                'notifiable'  => $notifiable,
                'subject'     => 'Nouvelle non-conformité',
                'icon'        => '⚠️',
                'accentColor' => '#ef4444',
                'entityLabel' => 'Non-conformité',
                'line'        => 'Une nouvelle non-conformité a été enregistrée et requiert votre attention.',
                'code'        => $this->data['code'] ?? '',
                'actionUrl'   => route('quality.nonConformitie'),
                'actionText'  => 'Voir les non-conformités',
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'id' => $this->data['id'],
            'code' => $this->data['code'],
            'user_id' => $this->data['user_id']
        ];
    }
}
