<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Concerns\HasMailFrom;

class ReturnNotification extends Notification
{
    use Queueable, HasMailFrom;

    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function via($notifiable): array
    {
        $channels = [];
        if ($notifiable->return_notification)       $channels[] = 'database';
        if ($notifiable->return_email_notification) $channels[] = 'mail';
        return $channels ?: ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return $this->applyFrom(new MailMessage)
            ->subject('Nouveau retour — ' . ($this->data['code'] ?? ''))
            ->view('emails.notification', [
                'notifiable'  => $notifiable,
                'subject'     => 'Nouveau retour',
                'icon'        => '↩️',
                'accentColor' => '#f59e0b',
                'entityLabel' => 'Retour',
                'line'        => 'Un nouveau retour client a été enregistré.',
                'code'        => $this->data['code'] ?? '',
                'actionUrl'   => route('returns.show', ['id' => $this->data['id']]),
                'actionText'  => 'Voir le retour',
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'id' => $this->data['id'],
            'code' => $this->data['code'],
            'user_id' => $this->data['user_id'],
            'statu' => $this->data['statu'] ?? null,
        ];
    }
}
