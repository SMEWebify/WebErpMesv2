<?php

namespace App\Services\Production;

use App\Enums\TaskDependencyType;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\User;
use App\Services\Planning\BillOfMaterialsCopier;
use App\Services\Planning\DependencyGraph;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Visa de libération d'une phase (tâche) de l'OF.
 *
 * Seuls les postes de charge qui le demandent (`methods_services.requires_release`,
 * faux par défaut) sont concernés : sur ces postes, une tâche terminée n'est
 * « libérée » qu'après le visa d'un porteur de `task-release`. Aucun poste
 * coché = aucun état calculé ni affiché, le fonctionnement historique reste
 * entier.
 *
 * Le visa n'empêche jamais de démarrer la tâche suivante : celle-ci affiche un
 * avertissement tant que sa précédente n'est pas libérée, rien de plus (même
 * doctrine que le contrôle en fabrication). « Précédente » = liens fin-début du
 * graphe effectif de la gamme (DependencyGraph) : liens saisis dans
 * task_dependencies, sinon l'opération précédente dans l'ordre de gamme.
 *
 * `release()` est le seul point d'écriture, pour que la saisie à l'écran et un
 * futur flux externe (Siteflow) suivent la même règle.
 */
class TaskRelease
{
    public const PERMISSION = 'task-release';

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_SITEFLOW = 'siteflow';
    public const SOURCE_API = 'api';

    /** Poste à visa, tâche pas encore terminée. */
    public const STATE_TO_FINISH = 'to_finish';
    /** Tâche terminée, en attente du visa. */
    public const STATE_AWAITING = 'awaiting';
    public const STATE_RELEASED = 'released';

    /** Natures d'opération présentes dans une gamme (fabrication, sous-traitance), comme TaskManageApiController. */
    private const ROUTING_TYPES = [1, 7];

    /** @var array<int, true>|null */
    private ?array $requiringServiceIds = null;

    /** @var array<int, true>|null */
    private ?array $finishedStatusIds = null;

    // ------------------------------------------------------------------
    // État d'une tâche
    // ------------------------------------------------------------------

    public function requiresRelease(Task $task): bool
    {
        return $task->methods_services_id !== null
            && isset($this->requiringServiceIds()[(int) $task->methods_services_id]);
    }

    public function isFinished(Task $task): bool
    {
        return $task->status_id !== null && isset($this->finishedStatusIds()[(int) $task->status_id]);
    }

    public function isReleased(Task $task): bool
    {
        return $task->released_at !== null;
    }

    /** État du visa, null si le poste de la tâche n'en demande pas. */
    public function state(Task $task): ?string
    {
        if (! $this->requiresRelease($task)) {
            return null;
        }
        if ($this->isReleased($task)) {
            return self::STATE_RELEASED;
        }

        return $this->isFinished($task) ? self::STATE_AWAITING : self::STATE_TO_FINISH;
    }

    // ------------------------------------------------------------------
    // Visa
    // ------------------------------------------------------------------

    /**
     * Raison de refuser le visa, null s'il peut être donné. Une saisie à
     * l'écran exige un utilisateur porteur de la permission ; un flux externe
     * (Siteflow, API) est authentifié en amont et peut ne pas en fournir.
     */
    public function releaseRefusal(Task $task, ?User $user, string $source = self::SOURCE_MANUAL): ?string
    {
        if ($source === self::SOURCE_MANUAL && ($user === null || ! $user->can(self::PERMISSION))) {
            return __('task_release.forbidden');
        }
        if (! $this->requiresRelease($task)) {
            return __('task_release.not_required');
        }
        if ($this->isReleased($task)) {
            return __('task_release.already_released');
        }
        if (! $this->isFinished($task)) {
            return __('task_release.not_finished');
        }

        return null;
    }

    /**
     * @throws TaskReleaseException
     */
    public function release(Task $task, ?User $user, string $source = self::SOURCE_MANUAL, ?string $comment = null): Task
    {
        $refusal = $this->releaseRefusal($task, $user, $source);

        if ($refusal !== null) {
            throw new TaskReleaseException($refusal);
        }

        $comment = $comment !== null && trim($comment) !== '' ? trim($comment) : null;

        $task->forceFill([
            'released_at'     => now(),
            'released_by'     => $user?->id,
            'release_comment' => $comment,
        ])->save();

        activity()
            ->performedOn($task)
            ->causedBy($user)
            ->withProperties(['source' => $source, 'comment' => $comment])
            ->log('task_released');

        return $task;
    }

    /**
     * @throws TaskReleaseException
     */
    public function revoke(Task $task, ?User $user, string $source = self::SOURCE_MANUAL): Task
    {
        if ($source === self::SOURCE_MANUAL && ($user === null || ! $user->can(self::PERMISSION))) {
            throw new TaskReleaseException(__('task_release.forbidden'));
        }
        if (! $this->isReleased($task)) {
            throw new TaskReleaseException(__('task_release.not_released_yet'));
        }

        $task->forceFill([
            'released_at'     => null,
            'released_by'     => null,
            'release_comment' => null,
        ])->save();

        activity()
            ->performedOn($task)
            ->causedBy($user)
            ->withProperties(['source' => $source])
            ->log('task_release_revoked');

        return $task;
    }

    // ------------------------------------------------------------------
    // Lecture en lot (écran tâche, Kanban)
    // ------------------------------------------------------------------

    /**
     * État du visa et prédécesseurs non libérés, par tâche. Seules les tâches
     * concernées figurent dans le résultat ; vide (une seule requête) quand
     * aucun poste ne demande de visa. Nombre de requêtes constant, quel que
     * soit le nombre de tâches.
     *
     * @param  iterable<Task>  $tasks
     * @return array<int, array{state: ?string, pending: array<int, array{id:int, ordre:mixed, label:?string, finished:bool}>}>
     */
    public function states(iterable $tasks): array
    {
        $tasks = collect($tasks)->filter(fn ($task) => $task instanceof Task)->keyBy('id');

        if ($tasks->isEmpty() || $this->requiringServiceIds() === []) {
            return [];
        }

        $pending = $this->pendingPredecessors($tasks);
        $result  = [];

        foreach ($tasks as $id => $task) {
            $state = $this->state($task);
            $waits = $pending[(int) $id] ?? [];

            if ($state !== null || $waits !== []) {
                $result[(int) $id] = ['state' => $state, 'pending' => $waits];
            }
        }

        return $result;
    }

    /**
     * Charge utile de l'écran tâche, null si rien n'est à afficher (poste sans
     * visa et aucune précédente en attente) : l'écran reste alors inchangé.
     */
    public function payload(Task $task, ?User $viewer): ?array
    {
        $info = $this->states([$task])[(int) $task->id] ?? null;

        if ($info === null) {
            return null;
        }

        $releasedBy = $task->released_by ? $task->releasedBy : null;

        return [
            'state'        => $info['state'],
            'released_at'  => $task->released_at?->format('d/m/Y H:i'),
            'released_by'  => $releasedBy?->name,
            'comment'      => $task->release_comment,
            'pending'      => $info['pending'],
            'warning'      => $this->warningText($info['pending']),
            'can_release'  => (bool) $viewer?->can(self::PERMISSION),
            'labels'       => __('task_release'),
        ];
    }

    /**
     * Texte de l'avertissement d'une tâche dont une précédente attend son visa,
     * null s'il n'y en a pas.
     *
     * @param  array<int, array{ordre:mixed, label:?string}>  $pending
     */
    public function warningText(array $pending): ?string
    {
        if ($pending === []) {
            return null;
        }

        $names = array_map(
            fn (array $p) => trim(($p['ordre'] !== null ? $p['ordre'] . ' ' : '') . ($p['label'] ?? '')),
            $pending
        );

        return trans_choice('task_release.predecessor_warning', count($pending), ['tasks' => implode(', ', $names)]);
    }

    /**
     * Prédécesseurs fin-début non libérés, sur un poste à visa, de chaque tâche.
     *
     * @param  Collection<int, Task>  $tasks
     * @return array<int, array<int, array{id:int, ordre:mixed, label:?string, finished:bool}>>
     */
    private function pendingPredecessors(Collection $tasks): array
    {
        // Tâches demandées, rangées par gamme.
        $byKey = [];
        foreach ($tasks as $id => $task) {
            $key = $this->routingKey($task);
            if ($key !== null) {
                $byKey[$key][(int) $id] = $task;
            }
        }

        if ($byKey === []) {
            return [];
        }

        // Opérations de ces gammes : un whereIn par porteur (plus les sous-ensembles
        // sans porteur), le tri fin par gamme se fait ensuite sur la clé.
        $values = [];
        foreach ($byKey as $group) {
            $task    = reset($group);
            $carrier = $this->carrierOf($task);
            $carrier !== null
                ? $values[$carrier][] = $task->{$carrier}
                : $values['sub_assembly_id'][] = $task->sub_assembly_id;
        }

        $routing = Task::query()
            ->with('predecessorLinks')
            ->whereIn('type', self::ROUTING_TYPES)
            ->where(function (Builder $q) use ($values) {
                foreach ($values as $column => $ids) {
                    $q->orWhereIn($column, array_values(array_unique($ids)));
                }
            })
            ->get(['id', 'ordre', 'label', 'type', 'status_id', 'methods_services_id', 'released_at',
                'quote_lines_id', 'order_lines_id', 'products_id', 'sub_assembly_id']);

        // Liens désactivés : chaîne historique par `ordre`, comme le calcul des dates.
        if (! config('planning.dependencies_enabled', true)) {
            $routing->each(fn (Task $task) => $task->setRelation('predecessorLinks', collect()));
        }

        $result = [];

        foreach ($routing->groupBy(fn (Task $task) => (string) $this->routingKey($task)) as $key => $group) {
            if (! isset($byKey[$key])) {
                continue;
            }

            $graph = DependencyGraph::fromTasks($group);

            foreach (array_keys($byKey[$key]) as $id) {
                if (! isset($graph->tasks()[$id])) {
                    continue;
                }

                foreach ($graph->incoming($id) as $edge) {
                    if ($edge->type !== TaskDependencyType::FinishToStart) {
                        continue;
                    }

                    $predecessor = $graph->task($edge->from);
                    if (! $this->requiresRelease($predecessor) || $this->isReleased($predecessor)) {
                        continue;
                    }

                    $result[$id][] = [
                        'id'       => (int) $predecessor->id,
                        'ordre'    => $predecessor->ordre,
                        'label'    => $predecessor->label,
                        'finished' => $this->isFinished($predecessor),
                    ];
                }
            }
        }

        return $result;
    }

    /** Porteur de la gamme (ligne de devis, ligne de commande ou article), comme TaskManageApiController::routingTasks(). */
    private function carrierOf(Task $task): ?string
    {
        foreach (BillOfMaterialsCopier::KEYS as $key) {
            if (! empty($task->{$key})) {
                return $key;
            }
        }

        return null;
    }

    /** Clé de la gamme d'une opération, null si elle n'en a pas (opération isolée ou hors gamme). */
    private function routingKey(Task $task): ?string
    {
        if (! in_array((int) $task->type, self::ROUTING_TYPES, true)) {
            return null;
        }

        $carrier = $this->carrierOf($task);

        if ($carrier === null && empty($task->sub_assembly_id)) {
            return null;
        }

        return ($carrier ? $carrier . ':' . $task->{$carrier} : '-') . '|' . ($task->sub_assembly_id ?: '-');
    }

    /** @return array<int, true> */
    private function requiringServiceIds(): array
    {
        return $this->requiringServiceIds ??= MethodsServices::query()
            ->where('requires_release', true)
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }

    /** @return array<int, true> */
    private function finishedStatusIds(): array
    {
        return $this->finishedStatusIds ??= Status::query()
            ->where('title', 'Finished')
            ->pluck('id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }
}
