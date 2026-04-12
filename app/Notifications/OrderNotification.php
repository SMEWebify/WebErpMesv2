<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Concerns\HasMailFrom;

class OrderNotification extends Notification
{
    use Queueable, HasMailFrom;
    private $data;

    /**
     * Create a new notification instance.
     *
     * @return void
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
        if ($notifiable->orders_notification)       $channels[] = 'database';
        if ($notifiable->orders_email_notification) $channels[] = 'mail';
        return $channels ?: ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return $this->applyFrom(new MailMessage)
            ->subject('Nouvelle commande — ' . ($this->data['code'] ?? ''))
            ->view('emails.notification', [
                'notifiable'  => $notifiable,
                'subject'     => 'Nouvelle commande',
                'icon'        => '🛒',
                'accentColor' => '#10b981',
                'entityLabel' => 'Commande',
                'line'        => 'Une nouvelle commande a été créée et vous a été assignée.',
                'code'        => $this->data['code'] ?? '',
                'actionUrl'   => route('orders.show', ['id' => $this->data['id']]),
                'actionText'  => 'Voir la commande',
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'id' => $this->data['id'],
            'code' => $this->data['code'],
            'user_id' => $this->data['user_id']
        ];
    }
}
