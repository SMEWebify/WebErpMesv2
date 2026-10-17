<?php

namespace Tests\Unit\Planning;

use App\Enums\TaskDependencyLagUnit;
use App\Enums\TaskDependencyType;
use App\Models\Planning\TaskDependency;
use App\Services\Planning\DependencyGraph;
use App\Support\Planning\DependencyNotation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Graphe effectif d'une gamme : liens saisis + fin-début implicite sur
 * l'opération précédente pour toute opération sans lien saisi.
 */
class DependencyGraphTest extends TestCase
{
    private function tasks(array $ordres): array
    {
        $tasks = [];
        foreach ($ordres as $id => $ordre) {
            $tasks[$id] = PlanningScenarios::task($id, $ordre, 1, 0, 1, null, []);
        }

        return $tasks;
    }

    private function link(array $tasks, int $to, int $from, string $type = 'FS', float $lag = 0, string $unit = 'hour'): void
    {
        $links = $tasks[$to]->getRelation('predecessorLinks');
        $links->push(new TaskDependency([
            'task_id'             => $to,
            'predecessor_task_id' => $from,
            'type'                => $type,
            'lag_value'           => $lag,
            'lag_unit'            => $unit,
        ]));
    }

    public function test_without_links_the_graph_is_the_routing_chain(): void
    {
        $graph = DependencyGraph::fromTasks($this->tasks([3 => 30, 1 => 10, 2 => 20]));

        $this->assertFalse($graph->hasExplicitLinks());
        $this->assertSame([1, 2, 3], $graph->topologicalOrder());
        $this->assertSame([3, 2, 1], $graph->reverseTopologicalOrder());
        $this->assertSame([], $graph->incoming(1));

        $edge = $graph->incoming(3)[0];
        $this->assertSame(2, $edge->from);
        $this->assertTrue($edge->implicit);
        $this->assertSame(TaskDependencyType::FinishToStart, $edge->type);
    }

    public function test_the_table_of_two_lathes_in_parallel(): void
    {
        // 10 Débit, 20 Tour 1, 30 Tour 2 en DD sur 20, 40 après 20 et 30, 50, 60.
        $tasks = $this->tasks([1 => 10, 2 => 20, 3 => 30, 4 => 40, 5 => 50, 6 => 60]);
        $this->link($tasks, 3, 2, 'SS');
        $this->link($tasks, 4, 2);
        $this->link($tasks, 4, 3);

        $graph = DependencyGraph::fromTasks($tasks);

        $this->assertTrue($graph->hasExplicitLinks());
        $this->assertSame([1, 2, 3, 4, 5, 6], $graph->topologicalOrder());

        // La 30 n'a plus de lien implicite : seul son DD saisi compte.
        $this->assertCount(1, $graph->incoming(3));
        $this->assertSame(TaskDependencyType::StartToStart, $graph->incoming(3)[0]->type);
        $this->assertFalse($graph->incoming(3)[0]->implicit);

        // La 40 attend les deux tours ; la 50 suit la 40 implicitement.
        $this->assertSame([2, 3], array_map(fn ($e) => $e->from, $graph->incoming(4)));
        $this->assertTrue($graph->incoming(5)[0]->implicit);
        $this->assertSame(4, $graph->incoming(5)[0]->from);
    }

    public function test_a_cycle_through_an_implicit_link_is_detected(): void
    {
        // « 10 après 20 » alors que 20, sans lien saisi, suit toujours 10.
        $tasks = $this->tasks([1 => 10, 2 => 20]);
        $this->link($tasks, 1, 2);

        $graph = DependencyGraph::fromTasks($tasks);

        $this->assertTrue($graph->hasCycle());
        $this->assertSame([2, 1, 2], $graph->cycle());
        $this->assertSame('20 → 10 → 20', $graph->describeCycle());
    }

    public function test_equal_ordre_is_broken_by_id(): void
    {
        $graph = DependencyGraph::fromTasks($this->tasks([7 => 10, 5 => 10, 6 => 20]));

        $this->assertSame([5, 7, 6], $graph->topologicalOrder());
        $this->assertSame(5, $graph->incoming(7)[0]->from);
    }

    public function test_a_link_to_an_operation_outside_the_routing_is_ignored(): void
    {
        $tasks = $this->tasks([1 => 10, 2 => 20]);
        $this->link($tasks, 2, 99);

        $graph = DependencyGraph::fromTasks($tasks);

        $this->assertCount(1, $graph->ignoredLinks());
        $this->assertFalse($graph->hasExplicitLinks());
        $this->assertTrue($graph->incoming(2)[0]->implicit, 'sans lien valable, l\'opération suit la précédente');
    }

    public function test_short_notation_is_read_and_written(): void
    {
        $tasks = $this->tasks([1 => 10, 2 => 20, 3 => 30, 4 => 40]);
        $parsed = DependencyNotation::parse('20FD ; 30dd+2h; 10FF+10pc ; 30SS+1,5', ['10' => 1, '20' => 2, '30' => 3, '40' => 4]);

        $this->assertCount(4, $parsed);
        $this->assertSame(2, $parsed[0]['predecessor_task_id']);
        $this->assertSame(TaskDependencyType::FinishToStart, $parsed[0]['type']);
        $this->assertSame(TaskDependencyType::StartToStart, $parsed[1]['type']);
        $this->assertSame(2.0, $parsed[1]['lag_value']);
        $this->assertSame(TaskDependencyLagUnit::Piece, $parsed[2]['lag_unit']);
        $this->assertSame(1.5, $parsed[3]['lag_value']);

        foreach ($parsed as $link) {
            $tasks[4]->getRelation('predecessorLinks')->push(new TaskDependency(['task_id' => 4] + $link));
        }
        $graph = DependencyGraph::fromTasks($tasks);

        app()->setLocale('fr');
        $this->assertSame('20FD;30DD+2h;10FF+10pc;30DD+1.5h', DependencyNotation::format($graph->incoming(4), [1 => 10, 2 => 20, 3 => 30, 4 => 40]));
    }

    public function test_short_notation_rejects_an_unknown_operation(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DependencyNotation::parse('25FD', ['10' => 1, '20' => 2]);
    }
}
