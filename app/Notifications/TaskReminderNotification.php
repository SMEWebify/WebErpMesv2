<?php

namespace App\Notifications;

use App\Models\Planning\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Task $task;

    protected string $alertType;

    public function __construct(Task $task, string $alertType)
    {
        $this->task = $task;
        $this->alertType = $alertType;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(__('Task reminder'))
            ->line(__('Task ":label" requires your attention.', ['label' => $this->task->label]))
            ->line(__('Due date: :date', ['date' => optional($this->task->due_date)->format('d/m/Y') ?? __('Not defined')]))
            ->action(__('View task'), route('production.task.gtd'));
    }

    public function toArray($notifiable)
    {
        return [
            'task_id' => $this->task->id,
            'label' => $this->task->label,
            'alert_type' => $this->alertType,
            'due_date' => optional($this->task->due_date)->toDateString(),
            'priority' => $this->task->priority,
            'primary_user_id' => $this->task->user_id,
            'secondary_user_id' => $this->task->secondary_user_id,
        ];
    }
}
