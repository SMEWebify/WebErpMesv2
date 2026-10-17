<?php

namespace App\Services\Planning;

use App\Enums\TaskDependencyLagUnit;
use App\Enums\TaskDependencyType;
use App\Models\Planning\Task;
use App\Models\Planning\TaskDependency;
use LogicException;

/**
 * Graphe effectif des opérations d'une gamme.
 *
 * Nœuds : les opérations, rangées par (ordre, id). Liens : ceux saisis dans
 * task_dependencies dont les deux bouts sont des nœuds, plus un fin-début
 * implicite sur l'opération précédente pour toute opération qui n'a aucun lien
 * saisi. Sans aucun lien saisi, le graphe est donc la chaîne historique.
 *
 * Le tri topologique départage les opérations prêtes en même temps par
 * (ordre, id) : sur une chaîne, l'ordre obtenu est celui de la gamme.
 */
final class DependencyGraph
{
    /** @var array<int, Task> id => opération, dans l'ordre (ordre, id) */
    private array $tasks = [];

    /** @var array<int, int> id => rang dans l'ordre de gamme */
    private array $rank = [];

    /** @var array<int, DependencyEdge[]> */
    private array $incoming = [];

    /** @var array<int, DependencyEdge[]> */
    private array $outgoing = [];

    /** @var TaskDependency[] liens saisis dont un bout n'est pas dans la gamme */
    private array $ignored = [];

    private bool $hasExplicitLinks = false;

    /** @var int[]|null */
    private ?array $order = null;

    /** @var int[] */
    private array $cycle = [];

    /**
     * @param  iterable<Task>  $tasks  opérations d'une même gamme ; la relation
     *                                  `predecessorLinks` est lue si elle est chargée,
     *                                  chargée sinon
     */
    public static function fromTasks(iterable $tasks): self
    {
        $graph = new self();
        $list = [];
        foreach ($tasks as $task) {
            $list[] = $task;
        }

        usort($list, fn (Task $a, Task $b) => [(float) $a->ordre, (int) $a->id] <=> [(float) $b->ordre, (int) $b->id]);

        foreach ($list as $rank => $task) {
            $id = (int) $task->id;
            $graph->tasks[$id] = $task;
            $graph->rank[$id] = $rank;
            $graph->incoming[$id] = [];
            $graph->outgoing[$id] = [];
        }

        $previous = null;
        foreach ($graph->tasks as $id => $task) {
            $explicit = false;

            foreach ($task->predecessorLinks ?? [] as $link) {
                $from = (int) $link->predecessor_task_id;
                if ($from === $id || ! isset($graph->tasks[$from])) {
                    $graph->ignored[] = $link;
                    continue;
                }

                $graph->addEdge(new DependencyEdge(
                    $from,
                    $id,
                    $link->type ?? TaskDependencyType::FinishToStart,
                    (float) ($link->lag_value ?? 0),
                    $link->lag_unit ?? TaskDependencyLagUnit::Hour,
                ));
                $explicit = true;
            }

            if ($explicit) {
                $graph->hasExplicitLinks = true;
            } elseif ($previous !== null) {
                $graph->addEdge(new DependencyEdge($previous, $id, implicit: true));
            }

            $previous = $id;
        }

        $graph->sort();

        return $graph;
    }

    private function addEdge(DependencyEdge $edge): void
    {
        $this->incoming[$edge->to][] = $edge;
        $this->outgoing[$edge->from][] = $edge;
    }

    /** Tri topologique de Kahn ; à égalité, la plus petite opération (ordre, id) d'abord. */
    private function sort(): void
    {
        $pending = array_map('count', $this->incoming);
        $ready = array_keys(array_filter($pending, fn (int $n) => $n === 0));
        $order = [];

        while ($ready !== []) {
            usort($ready, fn (int $a, int $b) => $this->rank[$a] <=> $this->rank[$b]);
            $id = array_shift($ready);
            $order[] = $id;

            foreach ($this->outgoing[$id] as $edge) {
                if (--$pending[$edge->to] === 0) {
                    $ready[] = $edge->to;
                }
            }
        }

        if (count($order) === count($this->tasks)) {
            $this->order = $order;

            return;
        }

        $this->cycle = $this->findCycle(array_keys(array_filter($pending, fn (int $n) => $n > 0)));
    }

    /**
     * Remonte les prédécesseurs non triés jusqu'à repasser par un nœud déjà vu :
     * tout nœud resté en attente a au moins un prédécesseur lui aussi en attente.
     *
     * @param  int[]  $stuck
     * @return int[]
     */
    private function findCycle(array $stuck): array
    {
        $stuckSet = array_flip($stuck);
        $path = [];
        $seen = [];
        $id = $stuck[0];

        while (! isset($seen[$id])) {
            $seen[$id] = count($path);
            $path[] = $id;

            foreach ($this->incoming[$id] as $edge) {
                if (isset($stuckSet[$edge->from])) {
                    $id = $edge->from;
                    break;
                }
            }
        }

        // Le chemin a été parcouru à rebours (vers les prédécesseurs) : on le
        // remet dans le sens des liens et on referme la boucle.
        $loop = array_reverse(array_slice($path, $seen[$id]));
        $loop[] = $loop[0];

        return $loop;
    }

    public function hasExplicitLinks(): bool
    {
        return $this->hasExplicitLinks;
    }

    public function hasCycle(): bool
    {
        return $this->order === null;
    }

    /** @return int[] ids de la boucle, le premier répété à la fin */
    public function cycle(): array
    {
        return $this->cycle;
    }

    /** « 20 → 30 → 20 », avec les numéros d'opération. */
    public function describeCycle(): string
    {
        return implode(' → ', array_map(fn (int $id) => $this->label($id), $this->cycle));
    }

    /** @return int[] */
    public function topologicalOrder(): array
    {
        if ($this->order === null) {
            throw new LogicException('Liens circulaires : ' . $this->describeCycle());
        }

        return $this->order;
    }

    /**
     * Ordre inverse pour la passe au plus tard : à égalité, la plus grande
     * opération (ordre, id) d'abord, comme le chaînage historique.
     *
     * @return int[]
     */
    public function reverseTopologicalOrder(): array
    {
        if ($this->order === null) {
            throw new LogicException('Liens circulaires : ' . $this->describeCycle());
        }

        $pending = array_map('count', $this->outgoing);
        $ready = array_keys(array_filter($pending, fn (int $n) => $n === 0));
        $order = [];

        while ($ready !== []) {
            usort($ready, fn (int $a, int $b) => $this->rank[$b] <=> $this->rank[$a]);
            $id = array_shift($ready);
            $order[] = $id;

            foreach ($this->incoming[$id] as $edge) {
                if (--$pending[$edge->from] === 0) {
                    $ready[] = $edge->from;
                }
            }
        }

        return $order;
    }

    /** @return array<int, Task> */
    public function tasks(): array
    {
        return $this->tasks;
    }

    public function task(int $id): Task
    {
        return $this->tasks[$id];
    }

    /** @return DependencyEdge[] liens qui arrivent sur l'opération */
    public function incoming(int $id): array
    {
        return $this->incoming[$id] ?? [];
    }

    /** @return DependencyEdge[] liens qui partent de l'opération */
    public function outgoing(int $id): array
    {
        return $this->outgoing[$id] ?? [];
    }

    /** @return DependencyEdge[] */
    public function edges(): array
    {
        return array_merge(...array_values($this->incoming)) ?: [];
    }

    /** @return TaskDependency[] */
    public function ignoredLinks(): array
    {
        return $this->ignored;
    }

    /** Numéro d'opération (ordre) affiché dans les messages. */
    public function label(int $id): string
    {
        $ordre = $this->tasks[$id]->ordre ?? null;

        return $ordre !== null ? (string) $ordre : '#' . $id;
    }
}
