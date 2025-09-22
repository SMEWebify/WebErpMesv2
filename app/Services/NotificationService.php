<?php

namespace App\Services;

use App\Models\Planning\Task;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Send Notification to all users
     *
     * @param string $notificationType
     * @param object $entityCreated
     * @param string $notificationColumn
     */
    public function sendNotification($notificationType, $entityCreated, $notificationColumn)
    {
        // Récupérer tous les utilisateurs ayant activé les notifications pour ce type
        $users = User::where($notificationColumn, 1)->get();

        // Envoyer la notification correspondante
        Notification::send($users, new $notificationType($entityCreated));
    }

    public function sendTaskAlert(string $notificationType, Task $task, iterable $users, string $alertType): void
    {
        $recipients = collect($users)
            ->filter()
            ->unique('id');

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new $notificationType($task, $alertType));
    }
}
