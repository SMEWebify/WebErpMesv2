<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Concerns\HasMailFrom;

class QuoteNotification extends Notification
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
        if ($notifiable->quotes_notification)       $channels[] = 'database';
        if ($notifiable->quotes_email_notification) $channels[] = 'mail';
        return $channels ?: ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return $this->applyFrom(new MailMessage)
            ->subject('Nouveau devis — ' . ($this->data['code'] ?? ''))
            ->view('emails.notification', [
                'notifiable'  => $notifiable,
                'subject'     => 'Nouveau devis',
                'icon'        => '📊',
                'accentColor' => '#3b82f6',
                'entityLabel' => 'Devis',
                'line'        => 'Un nouveau devis a été créé et vous a été assigné.',
                'code'        => $this->data['code'] ?? '',
                'actionUrl'   => route('quotes.show', ['id' => $this->data['id']]),
                'actionText'  => 'Voir le devis',
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
