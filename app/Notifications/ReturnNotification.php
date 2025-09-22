<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReturnNotification extends Notification
{
    use Queueable;

    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function via($notifiable): array
    {
        return ['database'];
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
