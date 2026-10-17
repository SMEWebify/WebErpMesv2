<?php

namespace App\Jobs;

use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\Planning\TaskDependency;
use App\Models\Workflow\OrderLines;
use App\Services\Planning\FiniteCapacityScheduler;
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
                                    ->where('order_lines.line_type', 'article')
                                    ->select('order_lines.*');

        // ALAP : on traite d'abord les commandes les plus urgentes (ancrage
        // au plus tard). ASAP : les plus anciennes en premier — c'est l'ordre
        // dans lequel elles se placeraient devant les postes. La ligne de
        // commande n'a pas de date de début : c'est la première tâche qui la
        // porte, et c'est justement ce que ce calcul écrit.
        $orderLines = $this->direction === TaskDateCalculator::DIRECTION_ASAP
            ? $orderLines->orderBy('orders.created_at')->orderBy('order_lines.id')
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

        // Lignes dont une opération porte un lien saisi : une seule requête. Les
        // autres gardent exactement le chaînage historique par `ordre`.
        $linkedLines = config('planning.dependencies_enabled', true)
            ? TaskDependency::linkedOrderLineIds()
            : [];

        $processed = 0;
        $messages = [];

        $direction    = $this->direction;
        $capacityMode = $this->capacityMode;
        $suffix       = strtoupper($direction) . ($capacityMode === FiniteCapacityScheduler::MODE_FINITE ? ' · Capacité finie' : '');
        // Un seul « maintenant » pour tout le run : toutes les lignes ASAP
        // partent du même instant, quelle que soit la durée du calcul.
        $asapFrom     = Carbon::now();

        $orderLines->lazy()->each(function ($line) use ($scheduler, $delayCallback, $direction, $capacityMode, $suffix, $asapFrom, $countLines, $linkedLines, $taskWith, &$processed, &$messages) {
            $chained = isset($linkedLines[$line->id])
                ? $scheduler->chainOrderLineWithDependencies(
                    $line,
                    $this->dependencyTasks($line, $taskWith),
                    $direction,
                    $capacityMode,
                    $delayCallback,
                    $asapFrom,
                )
                : $scheduler->chainOrderLine($line, $direction, $capacityMode, $delayCallback, $asapFrom);

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

        // Ajoute les débordements et les liens non tenus en queue de log — utile
        // pour prévenir l'utilisateur que certaines tâches n'ont pas trouvé de
        // créneau ou qu'un lien n'a pas pu être respecté.
        $tail = array_merge($scheduler->unfittedMessages(), $scheduler->conflictMessages());
        if ($tail !== []) {
            $this->updateProgress($processed, $countLines, array_slice($tail, -20));
        }

        $this->markFinished();
    }

    /**
     * Opérations d'une ligne à liens : les mêmes que le chaînage historique,
     * plus les tâches figées (« Ne pas recalculer », démarrées ou terminées),
     * qui servent de points fixes à leurs voisines.
     */
    private function dependencyTasks(OrderLines $line, array $taskWith)
    {
        return Task::with(array_merge(['predecessorLinks'], $taskWith))
            ->withExists(['taskActivities as has_started' => function (Builder $query) {
                return $query->whereIn('type', [TaskActivities::TYPE_START, TaskActivities::TYPE_FINISH]);
            }])
            ->where('order_lines_id', $line->id)
            ->where(function (Builder $query) {
                return $query->where('tasks.type', 1)
                            ->orWhere('tasks.type', 7);
            })
            ->orderBy('ordre')
            ->orderBy('id')
            ->get();
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
