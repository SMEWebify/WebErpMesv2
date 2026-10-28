<?php

namespace App\Services\Production;

use App\Enums\SalesLineType;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\SubAssembly;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use Illuminate\Support\Facades\DB;

/**
 * Bon pour fabrication (BPF) du bureau d'études sur une ligne de commande.
 *
 * Seul le démarrage d'une tâche de production (service productif) est soumis
 * au BPF : matière, fournitures, sous-traitance et achats ne le sont jamais,
 * leur approvisionnement pouvant être anticipé. Le blocage n'existe que si
 * `production.require_release` est vrai ; le BPF lui-même se donne toujours.
 *
 * Articulation avec la revue de commande : pas de BPF sur une commande dont la
 * revue est rejetée, ni tant qu'une demande de changement attend son
 * approbation. Un changement d'indice de la ligne révoque le BPF
 * (OrderLinesObserver::updating).
 */
class ProductionRelease
{
    /** Profondeur maximale remontée dans les sous-ensembles imbriqués. */
    private const MAX_SUB_ASSEMBLY_DEPTH = 10;

    public static function enabled(): bool
    {
        return (bool) config('production.require_release', false);
    }

    /**
     * Service productif : la seule nature de tâche soumise au BPF. Le type du
     * service fait foi (c'est lui que l'écran tâche lit), à défaut celui de la tâche.
     */
    public function isProductionTask(Task $task): bool
    {
        $type = $task->service?->type ?? $task->type;

        return (int) $type === MethodsServices::TYPE_PRODUCTIVE;
    }

    /** Ligne de commande de la tâche, directe ou par ses sous-ensembles. */
    public function orderLineOf(Task $task): ?OrderLines
    {
        if ($task->order_lines_id) {
            return $task->OrderLines;
        }

        $subAssemblyId = $task->sub_assembly_id;

        for ($depth = 0; $subAssemblyId && $depth < self::MAX_SUB_ASSEMBLY_DEPTH; $depth++) {
            $sub = SubAssembly::find($subAssemblyId, ['id', 'order_lines_id', 'sub_assembly_id']);

            if (! $sub) {
                return null;
            }
            if ($sub->order_lines_id) {
                return OrderLines::find($sub->order_lines_id);
            }

            $subAssemblyId = $sub->sub_assembly_id;
        }

        return null;
    }

    public function isReleased(OrderLines $line): bool
    {
        return $line->released_for_production_at !== null;
    }

    /**
     * Raison pour laquelle la tâche ne peut pas démarrer, null si elle le peut.
     */
    public function blockingReason(Task $task): ?string
    {
        if (! self::enabled() || ! $this->isProductionTask($task)) {
            return null;
        }

        $line = $this->orderLineOf($task);

        if ($line === null || $this->isReleased($line)) {
            return null;
        }

        return __('production_release.blocked_start', ['line' => $line->code ?: '#' . $line->id]);
    }

    /**
     * @throws ProductionReleaseException
     */
    public function assertCanStart(Task $task): void
    {
        $reason = $this->blockingReason($task);

        if ($reason !== null) {
            throw new ProductionReleaseException($reason);
        }
    }

    /**
     * Raison de refuser le BPF, null s'il peut être donné.
     */
    public function releaseRefusal(OrderLines $line): ?string
    {
        if ($line->line_type !== null && $line->line_type !== SalesLineType::Article->value) {
            return __('production_release.not_article');
        }
        if ($this->isReleased($line)) {
            return __('production_release.already_released');
        }

        $order = $line->order;

        if ($order?->review_decision === 'rejected') {
            return __('production_release.review_rejected');
        }
        if ($order && $order->change_requested_by && $order->change_approved_at === null) {
            return __('production_release.change_pending');
        }

        return null;
    }

    /**
     * @throws ProductionReleaseException
     */
    public function release(OrderLines $line, User $user): OrderLines
    {
        $refusal = $this->releaseRefusal($line);

        if ($refusal !== null) {
            throw new ProductionReleaseException($refusal);
        }

        $line->forceFill([
            'released_for_production_at' => now(),
            'released_for_production_by' => $user->id,
        ])->save();

        return $line;
    }

    /**
     * @throws ProductionReleaseException
     */
    public function revoke(OrderLines $line): OrderLines
    {
        if (! $this->isReleased($line)) {
            throw new ProductionReleaseException(__('production_release.not_released_yet'));
        }
        if ($this->hasStartedTask($line)) {
            throw new ProductionReleaseException(__('production_release.already_started'));
        }

        $line->forceFill([
            'released_for_production_at' => null,
            'released_for_production_by' => null,
        ])->save();

        return $line;
    }

    /**
     * Au moins une tâche de la ligne (ou de ses sous-ensembles) a été démarrée.
     */
    public function hasStartedTask(OrderLines $line): bool
    {
        $taskIds = Task::where('order_lines_id', $line->id)->pluck('id')
            ->merge(Task::whereIn('sub_assembly_id', $this->subAssemblyIds($line))->pluck('id'));

        if ($taskIds->isEmpty()) {
            return false;
        }

        return TaskActivities::whereIn('task_id', $taskIds->all())
            ->where('type', TaskActivities::TYPE_START)
            ->exists();
    }

    /**
     * Identifiants des tâches à signaler « sans BPF » parmi celles données :
     * tâches productives directement rattachées à une ligne sans BPF. Vide si
     * l'option est inactive. Une requête, quel que soit le nombre de tâches.
     *
     * @param  iterable<int>  $taskIds
     * @return array<int, int>
     */
    public function unreleasedTaskIds(iterable $taskIds): array
    {
        $ids = collect($taskIds)->filter()->unique()->values();

        if (! self::enabled() || $ids->isEmpty()) {
            return [];
        }

        return DB::table('tasks')
            ->join('order_lines', 'order_lines.id', '=', 'tasks.order_lines_id')
            ->leftJoin('methods_services', 'methods_services.id', '=', 'tasks.methods_services_id')
            ->whereIn('tasks.id', $ids->all())
            ->whereNull('order_lines.released_for_production_at')
            ->whereRaw('COALESCE(methods_services.type, tasks.type) = ?', [MethodsServices::TYPE_PRODUCTIVE])
            ->pluck('tasks.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return array<int, int> */
    private function subAssemblyIds(OrderLines $line): array
    {
        $ids = [];
        $level = SubAssembly::where('order_lines_id', $line->id)->pluck('id')->all();

        for ($depth = 0; $level !== [] && $depth < self::MAX_SUB_ASSEMBLY_DEPTH; $depth++) {
            $ids = array_merge($ids, $level);
            $level = SubAssembly::whereIn('sub_assembly_id', $level)->pluck('id')->all();
        }

        return $ids;
    }
}
