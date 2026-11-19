<?php

namespace App\Services\Production;

use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use Illuminate\Support\Facades\Cache;

/**
 * Écran de scan atelier (docs/pointage-scan.md, §3) : un scan d'opération ouvre,
 * démarre, reprend ou termine la tâche selon le mode de l'instance
 * (factory.scan_mode). Toutes les écritures passent par TaskExecution, donc par
 * les mêmes activités que l'écran tâche : le temps reste calculé par le serveur.
 */
class ScanStation
{
    public const MODE_OPEN = 'open';
    public const MODE_TOGGLE = 'toggle';
    public const MODE_TOGGLE_QTY = 'toggle_qty';
    public const MODES = [self::MODE_OPEN, self::MODE_TOGGLE, self::MODE_TOGGLE_QTY];

    /** Même opération, même opérateur à moins de 5 s : scan en double, ignoré. */
    public const DEBOUNCE_SECONDS = 5;

    public const OPENED = 'opened';
    public const STARTED = 'started';
    public const RESUMED = 'resumed';
    public const FINISHED = 'finished';
    public const NEEDS_QTY = 'needs_qty';
    public const ALREADY_FINISHED = 'already_finished';
    public const REFUSED = 'refused';
    public const NOT_FOUND = 'not_found';
    public const IGNORED = 'ignored';
    public const BADGE = 'badge';
    public const BADGE_UNKNOWN = 'badge_unknown';
    public const BADGE_REQUIRED = 'badge_required';

    /** Permission du compte de poste partagé : écran de scan seul, badge exigé. */
    public const KIOSK_PERMISSION = 'workshop-kiosk';

    /** Un badge reste l'opérateur du poste 15 min sans scan (contrat ERP / MES). */
    public const BADGE_IDLE_MINUTES = 15;

    public static function isKiosk(?\App\Models\User $user): bool
    {
        return $user !== null && $user->checkPermissionTo(self::KIOSK_PERMISSION);
    }

    public function __construct(
        private TaskExecution $execution,
        private ProductionRelease $productionRelease,
    ) {
    }

    public static function mode(): string
    {
        $mode = (string) optional(app('Factory'))->scan_mode;

        return in_array($mode, self::MODES, true) ? $mode : self::MODE_OPEN;
    }

    /**
     * @param  array{good: int, bad: int}|null  $quantities  saisies de la fenêtre de fin (toggle_qty)
     * @return array{result: string, task: ?Task, reason?: string}
     */
    public function scan(string $code, ?int $operatorId, ?array $quantities = null): array
    {
        $task = ScanCode::resolveTask($code);
        if (! $task) {
            return ['result' => self::NOT_FOUND, 'task' => null];
        }

        $task->loadMissing('status', 'service', 'OrderLines.order');
        $mode = self::mode();

        if ($mode === self::MODE_OPEN) {
            return ['result' => self::OPENED, 'task' => $task];
        }

        if ($task->status?->title === 'Finished') {
            return ['result' => self::ALREADY_FINISHED, 'task' => $task];
        }

        $last = TaskActivities::query()
            ->where('task_id', $task->getKey())
            ->whereIn('type', [TaskActivities::TYPE_START, TaskActivities::TYPE_END, TaskActivities::TYPE_FINISH])
            ->orderByDesc('timestamp')
            ->orderByDesc('id')
            ->value('type');
        $running = (int) $last === TaskActivities::TYPE_START;

        // La fin avec quantités se fait en deux temps (scan, puis fenêtre) : le
        // second appel n'est pas un scan en double.
        if ($quantities === null && ! Cache::add($this->debounceKey($operatorId, $task), 1, self::DEBOUNCE_SECONDS)) {
            return ['result' => self::IGNORED, 'task' => $task];
        }

        if (! $running) {
            $reason = $this->productionRelease->blockingReason($task);
            if ($reason !== null) {
                return ['result' => self::REFUSED, 'task' => $task, 'reason' => $reason];
            }

            $this->execution->start($task, $operatorId);

            return ['result' => (int) $last === TaskActivities::TYPE_END ? self::RESUMED : self::STARTED, 'task' => $task->fresh(['status', 'service', 'OrderLines.order'])];
        }

        if ($mode === self::MODE_TOGGLE_QTY && $quantities === null) {
            // Le scan de fin a été pris en compte : le libérer pour que la
            // validation de la fenêtre n'attende pas 5 s.
            Cache::forget($this->debounceKey($operatorId, $task));

            return ['result' => self::NEEDS_QTY, 'task' => $task];
        }

        if ($quantities !== null) {
            if ($quantities['good'] > 0) {
                $this->execution->declareGood($task, $quantities['good'], $operatorId);
            }
            if ($quantities['bad'] > 0) {
                $this->execution->declareBad($task, $quantities['bad'], $operatorId);
            }
        }

        $this->execution->finish($task, $operatorId);

        return ['result' => self::FINISHED, 'task' => $task->fresh(['status', 'service', 'OrderLines.order'])];
    }

    /**
     * Résumé affiché par l'écran de scan.
     *
     * @return array<string, mixed>
     */
    public static function summary(Task $task): array
    {
        $orderQty = (float) $task->GetOrderQtyLine();
        $good = (int) $task->getTotalLogGoodQt();

        return [
            'id' => $task->getKey(),
            'label' => $task->label,
            'operation' => $task->service?->code ?: $task->code,
            'ordre' => $task->ordre,
            'order_code' => $task->OrderLines?->order?->code,
            'line_label' => $task->OrderLines?->label,
            'status' => $task->status?->title,
            'worked_seconds' => $task->getTotalLogSeconds(),
            'good_qty' => $good,
            'bad_qty' => (int) $task->getTotalLogBadQt(),
            'order_qty' => $orderQty,
            'remaining_qty' => (int) max(0, $orderQty - $good),
        ];
    }

    private function debounceKey(?int $operatorId, Task $task): string
    {
        return 'scan-station:' . ($operatorId ?? 'anon') . ':' . $task->getKey();
    }
}
