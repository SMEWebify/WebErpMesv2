<?php

namespace App\Jobs;

use App\Models\Workflow\OrderLines;
use App\Services\Planning\InterOperationDelayResolver;
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
        // Nouvelle instance par run : les surcharges de paires et les défauts
        // config peuvent avoir été modifiés depuis le dernier calcul.
        $delayResolver = app(InterOperationDelayResolver::class);
        $delayCallback = fn (?int $from, ?int $to) => $delayResolver->hoursBetween($from, $to);

        $processed = 0;
        $messages = [];

        $orderLines->lazy()->each(function ($line) use ($taskDateCalculator, $delayCallback, $countLines, &$processed, &$messages) {
            $anchor = Carbon::parse($line->internal_delay);
            $tasks  = $line->Task->sortByDesc('ordre');

            $chained = $taskDateCalculator->chainTasks($tasks, $anchor, $delayCallback);

            foreach ($chained as $entry) {
                $entry['task']->end_date   = $entry['end'];
                $entry['task']->start_date = $entry['start'];
                $entry['task']->save();
            }

            $processed++;
            $messages[] = 'OF #' . ($line->orders_id ?? '?') . ' — ' . $tasks->count() . ' tâche(s) planifiée(s)';

            // Batch cache write every 10 lines
            if ($processed % 10 === 0 || $processed === $countLines) {
                $this->updateProgress($processed, $countLines, array_slice($messages, -20));
                $messages = [];
            }
        });

        $this->markFinished();
    }

    private function initializeProgress(): void
    {
        Cache::put($this->cacheKey, [
            'status'   => 'running',
            'progress' => 0,
            'count'    => 0,
            'messages' => [],
        ], now()->addHour());
    }

    private function updateProgress(int $processed, int $total, array $messages): void
    {
        Cache::put($this->cacheKey, [
            'status'   => 'running',
            'progress' => round(($processed / $total) * 100, 2),
            'count'    => $processed,
            'messages' => $messages,
        ], now()->addHour());
    }

    private function markFinished(): void
    {
        $state = Cache::get($this->cacheKey, []);

        Cache::put($this->cacheKey, [
            'status'   => 'finished',
            'progress' => $state['progress'] ?? 100,
            'count'    => $state['count'] ?? 0,
            'messages' => $state['messages'] ?? [],
        ], now()->addHour());
    }
}
