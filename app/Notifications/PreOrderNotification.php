<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Concerns\HasMailFrom;

class PreOrderNotification extends Notification
{
    use Queueable, HasMailFrom;

    public function __construct(private array $data)
    {
    }

    public function via($notifiable): array
    {
        $channels = [];
        if ($notifiable->pre_order_notification)       $channels[] = 'database';
        if ($notifiable->pre_order_email_notification) $channels[] = 'mail';
        return $channels ?: ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return $this->applyFrom(new MailMessage)
            ->subject('Nouvelle pré-commande — ' . ($this->data['code'] ?? ''))
            ->view('emails.notification', [
                'notifiable'  => $notifiable,
                'subject'     => 'Nouvelle pré-commande',
                'icon'        => '📥',
                'accentColor' => '#8b5cf6',
                'entityLabel' => 'Pré-commande',
                'line'        => 'Une nouvelle pré-commande IA a été importée et est en attente de validation.',
                'code'        => $this->data['code'] ?? '',
                'actionUrl'   => url('/pre-orders'),
                'actionText'  => 'Voir les pré-commandes',
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'id' => $this->data['id'],
            'code' => $this->data['code'],
            'user_id' => $this->data['user_id'],
        ];
    }
}
