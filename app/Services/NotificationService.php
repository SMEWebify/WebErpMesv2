<?php

namespace App\Services;

use App\Models\Planning\Task;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    private const EMAIL_COLUMN_MAP = [
        'companies_notification'      => 'companies_email_notification',
        'users_notification'          => 'users_email_notification',
        'quotes_notification'         => 'quotes_email_notification',
        'orders_notification'         => 'orders_email_notification',
        'non_conformity_notification' => 'non_conformity_email_notification',
        'return_notification'         => 'return_email_notification',
        'pre_order_notification'      => 'pre_order_email_notification',
    ];

    /**
     * Send Notification to all users who have app or email channel enabled
     *
     * @param string $notificationType
     * @param object $entityCreated
     * @param string $notificationColumn
     */
    public function sendNotification($notificationType, $entityCreated, $notificationColumn)
    {
        $emailColumn = self::EMAIL_COLUMN_MAP[$notificationColumn] ?? null;

        $users = User::where(function ($q) use ($notificationColumn, $emailColumn) {
            $q->where($notificationColumn, 1);
            if ($emailColumn) {
                $q->orWhere($emailColumn, 1);
            }
        })->get();

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
