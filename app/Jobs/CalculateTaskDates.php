<?php

namespace App\Jobs;

use App\Models\Workflow\OrderLines;
use App\Support\WorkingTime;
use App\Services\TaskDateCalculator;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class CalculateTaskDates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CACHE_KEY = 'task_calculation_dates_progress';
    public const ORDER_CACHE_KEY_PREFIX = 'task_calculation_dates_progress_order_';

    private ?int $orderId;
    private string $cacheKey;

    public function __construct(?int $orderId = null)
    {
        $this->orderId = $orderId;
        $this->cacheKey = self::cacheKeyForOrder($orderId);
    }

    public static function cacheKeyForOrder(?int $orderId = null): string
    {
        if ($orderId === null) {
            return self::CACHE_KEY;
        }

        return self::ORDER_CACHE_KEY_PREFIX . $orderId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->initializeProgress();

        $orderLines = OrderLines::with(['order', 'Task' => function ($query) {
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
                                    ->select('order_lines.*');

        if ($this->orderId !== null) {
            $orderLines->where('order_lines.orders_id', $this->orderId);
        }

        $countLines = (clone $orderLines)->count();

        if ($countLines === 0) {
            $this->markFinished();
            return;
        }

        $taskDateCalculator = app(TaskDateCalculator::class);
        $processed = 0;

        $orderLines->lazy()->each(function ($line) use ($taskDateCalculator, $countLines, &$processed) {
            $taskEndDate = Carbon::parse($line->internal_delay);
            $taskEndDate = $taskDateCalculator->adjustForWeekendsAndHolidays($taskEndDate);

            $elapsedTimeInSeconds = 0;
            $tasks = $line->Task->sortByDesc('ordre');

            foreach ($tasks as $task) {
                $endDate = $taskDateCalculator->adjustForWorkingHours(clone $taskEndDate, $elapsedTimeInSeconds);
                $task->end_date = $endDate;

                $totalTaskHours = $task->TotalTime();
                $secondsToSubtract = $this->calculateWorkingHours($taskDateCalculator, $endDate, $totalTaskHours);

                $elapsedTimeInSeconds += $secondsToSubtract;
                $startDate = $taskDateCalculator->adjustForWorkingHours(clone $taskEndDate, $elapsedTimeInSeconds);
                $task->start_date = $startDate;
                $task->save();

                $taskEndDate = $startDate;
            }

            $processed++;
            $this->updateProgress($processed, $countLines);
        });

        $this->markFinished();
    }

    private function calculateWorkingHours(TaskDateCalculator $taskDateCalculator, Carbon $fromDate, int $totalTaskHours): int
    {
        $startDate = WorkingTime::subtractWorkingHours($fromDate, $totalTaskHours);

        return $fromDate->diffInSeconds($startDate);
    }

    private function initializeProgress(): void
    {
        Cache::put($this->cacheKey, [
            'status' => 'running',
            'progress' => 0,
            'count' => 0,
        ], now()->addHour());
    }

    private function updateProgress(int $processed, int $total): void
    {
        Cache::put($this->cacheKey, [
            'status' => 'running',
            'progress' => round(($processed / $total) * 100, 2),
            'count' => $processed,
        ], now()->addHour());
    }

    private function markFinished(): void
    {
        $state = Cache::get($this->cacheKey, []);

        Cache::put($this->cacheKey, [
            'status' => 'finished',
            'progress' => $state['progress'] ?? 100,
            'count' => $state['count'] ?? 0,
        ], now()->addHour());
    }
}
