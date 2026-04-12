<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\Concerns\HasMailFrom;

class CompanieNotification extends Notification
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
        if ($notifiable->companies_notification)       $channels[] = 'database';
        if ($notifiable->companies_email_notification) $channels[] = 'mail';
        return $channels ?: ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return $this->applyFrom(new MailMessage)
            ->subject('Nouvelle entreprise — ' . ($this->data['label'] ?? ''))
            ->view('emails.notification', [
                'notifiable'  => $notifiable,
                'subject'     => 'Nouvelle entreprise',
                'icon'        => '🏢',
                'accentColor' => '#6366f1',
                'entityLabel' => 'Entreprise',
                'line'        => 'Une nouvelle entreprise a été enregistrée dans le CRM.',
                'code'        => $this->data['label'] ?? '',
                'actionUrl'   => route('companies.show', ['id' => $this->data['id']]),
                'actionText'  => "Voir l'entreprise",
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
            'code' => $this->data['label'],
            'user_id' => $this->data['user_id']
        ];
    }
}
