<?php

namespace Tests\Unit\Planning;

use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

/**
 * Quantité propre d'une opération (`planned_qty`) : un lot partagé entre deux
 * machines. Vide, rien ne change ; saisie, le temps, le coût, le prix et la
 * quantité à produire de l'opération se calculent sur sa part.
 */
class OperationQuantityTest extends TestCase
{
    public function test_without_operation_quantity_nothing_changes(): void
    {
        foreach ([[0, 0, 1], [1.5, 0.25, 4], [0.333, 0.017, 37], [2, 0.75, 1], [0, 1.2, 3]] as [$setup, $unit, $qty]) {
            $task = PlanningScenarios::task(1, 10, $setup, $unit, $qty, null, []);
            $task->unit_cost = 12.34;
            $task->unit_price = 15.67;

            $this->assertSame((float) $task->GetOrderQtyLine(), $task->operationQty());
            $this->assertSame(round($task->GetOrderQtyLine() * $unit + $setup, 2), $task->TotalTime());
            $this->assertSame(round($task->GetOrderQtyLine() * 12.34, 2), $task->TotalCost());
            $this->assertSame(round($task->GetOrderQtyLine() * 15.67, 2), $task->TotalPrice());
            $this->assertSame(1.0, $task->lineShare($qty));
        }
    }

    public function test_time_cost_and_price_follow_the_operation_quantity(): void
    {
        // Ligne de 100, dont 60 sur ce tour : 1 h de réglage + 60 × 0,1 h.
        $line = new OrderLines();
        $line->qty = 100;
        $task = PlanningScenarios::task(1, 10, 1, 0.1, 1, null, []);
        $task->order_lines_id = 1;
        $task->setRelation('OrderLines', $line);
        $task->unit_cost = 2;
        $task->unit_price = 3;
        $task->planned_qty = 60;

        $this->assertSame(60.0, $task->operationQty());
        $this->assertSame(7.0, $task->TotalTime());
        $this->assertSame(120.0, $task->TotalCost());
        $this->assertSame(180.0, $task->TotalPrice());
        $this->assertSame(60.0, $task->getQualityRequiredAttribute(), 'quantité à produire sur ce poste');
        $this->assertSame(0.6, $task->lineShare(100));
    }

    public function test_the_line_unit_price_counts_each_operation_for_its_share(): void
    {
        // 20 et 30 se partagent le lot (60 + 40) ; 40 traite tout.
        $tasks = [
            PlanningScenarios::task(2, 20, 0, 0.1, 1, null, []),
            PlanningScenarios::task(3, 30, 0, 0.2, 1, null, []),
            PlanningScenarios::task(4, 40, 0, 0.5, 1, null, []),
        ];
        foreach ([[10, 20, 60], [5, 8, 40], [1, 2, null]] as $i => [$cost, $price, $share]) {
            $tasks[$i]->unit_cost = $cost;
            $tasks[$i]->unit_price = $price;
            $tasks[$i]->planned_qty = $share;
        }

        foreach ([new QuoteLines(), new OrderLines()] as $line) {
            $line->qty = 100;
            $line->setRelation('TechnicalCut', new Collection($tasks));

            $this->assertEqualsWithDelta(0.6 * 10 + 0.4 * 5 + 1, $line->getTechnicalCutTotalUnitCostAttribute(), 1e-9);
            $this->assertEqualsWithDelta(0.6 * 20 + 0.4 * 8 + 2, $line->getTechnicalCutTotalUnitPricettribute(), 1e-9);
            $this->assertEqualsWithDelta(0.6 * 0.1 + 0.4 * 0.2 + 0.5, $line->getTechnicalCutTotalUnitTimeAttribute(), 1e-9);
        }
    }
}
