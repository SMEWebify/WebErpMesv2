<?php

namespace App\Jobs;

use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Notifications\TaskReminderNotification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchGtdTaskReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationService $notificationService): void
    {
        $today = Carbon::today();
        $soonThreshold = $today->copy()->addDays(2);

        $doneStatusIds = Status::query()
            ->whereIn('title', ['Finished', 'Supplied', 'Done'])
            ->pluck('id')
            ->all();

        $tasks = Task::query()
            ->whereNull('quote_lines_id')
            ->whereNull('order_lines_id')
            ->whereNull('products_id')
            ->whereNull('sub_assembly_id')
            ->whereNotNull('due_date')
            ->when(!empty($doneStatusIds), fn ($query) => $query->whereNotIn('status_id', $doneStatusIds))
            ->with(['user', 'secondaryAssignee'])
            ->get();

        foreach ($tasks as $task) {
            $alertType = null;

            if ($task->due_date->lt($today)) {
                $alertType = 'overdue';
            } elseif ($task->due_date->between($today, $soonThreshold, true)) {
                $alertType = 'due_soon';
            }

            if (! $alertType) {
                continue;
            }

            $recipients = collect([$task->user, $task->secondaryAssignee])->filter();

            if ($recipients->isEmpty()) {
                continue;
            }

            $notificationService->sendTaskAlert(TaskReminderNotification::class, $task, $recipients, $alertType);
        }
    }
}
