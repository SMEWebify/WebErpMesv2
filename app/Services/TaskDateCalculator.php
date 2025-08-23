<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Workflow\OrderLines;
use App\Models\Times\TimesBanckHoliday;
use Illuminate\Database\Eloquent\Builder;
use Exception;

class TaskDateCalculator
{
    public function calculateDate(): array
    {
        $progressDateLog = '';
        $progressDate = 0;
        $countTaskCalculateDate = 0;

        $OrderLines = OrderLines::with(['order', 'Task' => function ($query) {
            $query->where('not_recalculate', 0)
                ->where(function (Builder $query) {
                    return $query->where('tasks.type', 1)
                                 ->orWhere('tasks.type', 7);
                })
                ->orderBy('ordre');
        }])
        ->join('orders', 'order_lines.orders_id', '=', 'orders.id')
        ->where('order_lines.tasks_status', '!=', 4)
        ->orderBy('order_lines.internal_delay')
        ->select('order_lines.*')
        ->get();

        $countLines = $OrderLines->count();

        if ($countLines === 0) {
            return [
                'progressDate' => $progressDate,
                'progressDateLog' => $progressDateLog,
                'countTaskCalculateDate' => $countTaskCalculateDate,
                'toBeCalculateDate' => false,
            ];
        }

        foreach ($OrderLines as $line) {
            $taskEndDate = Carbon::parse($line->internal_delay);
            $taskEndDate = $this->adjustForWeekends($taskEndDate);

            $elapsedTimeInSeconds = 0;

            $tasks = $line->Task->sortByDesc('ordre');

            foreach ($tasks as $task) {
                $endDate = $this->adjustForWorkingHours(clone $taskEndDate, $elapsedTimeInSeconds);
                $task->end_date = $endDate;

                $progressDateLog .= '<li>End date : '. $endDate .' updated for task #'. $task->id .' ordre '. $task->ordre .'</li>';

                $totalTaskHours = $task->TotalTime();
                $adjustedTaskHours = $this->calculateWorkingHours($totalTaskHours);

                $elapsedTimeInSeconds += ($adjustedTaskHours * 3600);
                $startDate = $this->adjustForWorkingHours(clone $taskEndDate, $elapsedTimeInSeconds);
                $task->start_date = $startDate;
                $task->save();

                $taskEndDate = $startDate;
                $countTaskCalculateDate += 1;
            }

            $progressDate += (1 / $countLines) * 100;
        }

        return [
            'progressDate' => $progressDate,
            'progressDateLog' => $progressDateLog,
            'countTaskCalculateDate' => $countTaskCalculateDate,
            'toBeCalculateDate' => false,
        ];
    }

    private function adjustForWeekends(Carbon $date): Carbon
    {
        if ($date->isSunday()) {
            return $date->subDay();
        } elseif ($date->isMonday()) {
            return $date->subDays(2);
        }
        return $date;
    }

    private function adjustForWorkingHours(Carbon $date, int $subtractSeconds): Carbon
    {
        $date->subSeconds($subtractSeconds);

        $maxIterations = 100;
        $iterations = 0;

        while ($iterations < $maxIterations) {
            if ($date->isSaturday()) {
                $date->subDay()->hour(18)->minute(0);
            } elseif ($date->isSunday()) {
                $date->subDays(2)->hour(18)->minute(0);
            } elseif (TimesBanckHoliday::isBankHoliday($date)) {
                $date->subDay()->hour(18)->minute(0);
            }

            if ($date->hour < 8) {
                $date->subHours($date->hour + 10);
            } elseif ($date->hour >= 18) {
                $date->hour(18)->minute(0)->subSecond();
            } else {
                break;
            }

            $iterations++;
        }

        if ($iterations >= $maxIterations) {
            throw new Exception("Loop limit exceeded in adjustForWorkingHours()! Date: " . $date->toDateTimeString());
        }

        return $date;
    }

    private function calculateWorkingHours(int $totalTaskHours): int
    {
        $workingDays = floor($totalTaskHours / 8);
        $weekends = floor($workingDays / 5);
        return $totalTaskHours + ($workingDays * 16) + ($weekends * 48);
    }
}
