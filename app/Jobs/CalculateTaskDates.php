<?php

namespace App\Jobs;

use App\Models\Workflow\OrderLines;
use App\Services\Planning\FiniteCapacityScheduler;
use App\Services\Planning\InterOperationDelayResolver;
use App\Services\TaskDateCalculator;
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
    private string $direction;
    private string $capacityMode;

    public function __construct(
        ?int $orderId = null,
        string $direction = TaskDateCalculator::DIRECTION_ALAP,
        string $capacityMode = FiniteCapacityScheduler::MODE_INFINITE,
    ) {
        $this->orderId = $orderId;
        $this->cacheKey = self::cacheKeyForOrder($orderId);
        $this->direction = in_array($direction, [
            TaskDateCalculator::DIRECTION_ALAP,
            TaskDateCalculator::DIRECTION_ASAP,
        ], true) ? $direction : TaskDateCalculator::DIRECTION_ALAP;
        $this->capacityMode = in_array($capacityMode, [
            FiniteCapacityScheduler::MODE_INFINITE,
            FiniteCapacityScheduler::MODE_FINITE,
        ], true) ? $capacityMode : FiniteCapacityScheduler::MODE_INFINITE;
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

        // La capacité finie contrôle la charge par ressource — on eager-load
        // le pivot pour éviter un N+1 pendant le placement.
        $taskWith = $this->capacityMode === FiniteCapacityScheduler::MODE_FINITE
            ? ['resources']
            : [];

        $orderLines = OrderLines::with(['order', 'Task' => function ($query) use ($taskWith) {
                                    $query->where('not_recalculate', 0)
                                            ->where(function (Builder $query) {
                                                return $query->where('tasks.type', 1)
                                                            ->orWhere('tasks.type', 7);
                                            })
                                    ->orderBy('ordre');
                                    if ($taskWith) {
                                        $query->with($taskWith);
                                    }
                                    }])
                                    ->join('orders', 'order_lines.orders_id', '=', 'orders.id')
                                    ->where('order_lines.tasks_status', '!=', 4)
                                    ->select('order_lines.*');

        // ALAP : on traite d'abord les commandes les plus urgentes (ancrage
        // au plus tard). ASAP : les plus anciennes en premier — c'est l'ordre
        // dans lequel elles se placeraient devant les postes.
        $orderLines = $this->direction === TaskDateCalculator::DIRECTION_ASAP
            ? $orderLines->orderBy('order_lines.start_date')
            : $orderLines->orderBy('order_lines.internal_delay');

        if ($this->orderId !== null) {
            $orderLines->where('order_lines.orders_id', $this->orderId);
        }

        $countLines = (clone $orderLines)->count();

        if ($countLines === 0) {
            $this->markFinished();
            return;
        }

        // Nouvelle instance par run : les surcharges de paires et les défauts
        // config peuvent avoir été modifiés depuis le dernier calcul.
        $delayResolver = app(InterOperationDelayResolver::class);
        $delayCallback = fn (?int $from, ?int $to) => $delayResolver->hoursBetween($from, $to);

        // Scheduler unique par run : il porte la charge cumulée par ressource,
        // c'est ce qui permet aux OF suivants de constater qu'une machine est
        // déjà pleine (capacité finie) et de se décaler.
        $scheduler = app(FiniteCapacityScheduler::class);
        $scheduler->reset();

        $processed = 0;
        $messages = [];

        $direction    = $this->direction;
        $capacityMode = $this->capacityMode;
        $suffix       = strtoupper($direction) . ($capacityMode === FiniteCapacityScheduler::MODE_FINITE ? ' · Capacité finie' : '');

        $orderLines->lazy()->each(function ($line) use ($scheduler, $delayCallback, $direction, $capacityMode, $suffix, $countLines, &$processed, &$messages) {
            $chained = $scheduler->chainOrderLine($line, $direction, $capacityMode, $delayCallback);

            foreach ($chained as $entry) {
                $entry['task']->end_date   = $entry['end'];
                $entry['task']->start_date = $entry['start'];
                $entry['task']->save();
            }

            $processed++;
            $messages[] = 'OF #' . ($line->orders_id ?? '?') . ' — ' . count($chained) . ' tâche(s) planifiée(s) (' . $suffix . ')';

            // Batch cache write every 10 lines
            if ($processed % 10 === 0 || $processed === $countLines) {
                $this->updateProgress($processed, $countLines, array_slice($messages, -20));
                $messages = [];
            }
        });

        // Ajoute les débordements en queue de log — utile pour prévenir
        // l'utilisateur que certaines tâches n'ont pas trouvé de créneau.
        $unfitted = $scheduler->unfittedMessages();
        if ($unfitted !== []) {
            $tail = array_slice($unfitted, -20);
            $this->updateProgress($processed, $countLines, $tail);
        }

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
