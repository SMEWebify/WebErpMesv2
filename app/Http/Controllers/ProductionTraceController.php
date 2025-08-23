<?php

namespace App\Http\Controllers;

use App\Models\Products\SerialNumbers;

use App\Models\Products\StockMove;
use App\Models\Planning\Task;
use App\Models\Quality\QualityNonConformity;

class ProductionTraceController extends Controller
{
    /**
     * Display production trace for a given serial number.
     *
     * @param string $serial
     * @return \Illuminate\Contracts\View\View
     */
    public function show(string $serial)
    {
        // Retrieve serial number
        $serialNumber = SerialNumbers::with('Product')
            ->where('serial_number', $serial)
            ->firstOrFail();

        $timeline = collect();

        // Serial number creation event
        $timeline->push([
            'date' => $serialNumber->created_at,
            'operation' => __('Serial number created'),
            'user' => '',
            'component' => optional($serialNumber->Product)->code,
        ]);

        // Stock moves related to this serial number
        $stockMoves = StockMove::with(['UserManagement', 'StockLocationProducts.product'])
            ->where('tracability', $serial)
            ->get();

        foreach ($stockMoves as $move) {
            $timeline->push([
                'date' => $move->created_at,
                'operation' => __('Stock move'),
                'user' => optional($move->UserManagement)->name,
                'component' => optional(optional($move->StockLocationProducts)->product)->code,
            ]);
        }

        // Tasks linked to order line
        $tasks = Task::with('Component')
            ->where('order_lines_id', $serialNumber->order_line_id)
            ->get();

        foreach ($tasks as $task) {
            $timeline->push([
                'date' => $task->created_at,
                'operation' => __('Task') . ' ' . $task->label,
                'user' => '',
                'component' => optional($task->Component)->code,
            ]);
        }

        // Quality non conformities
        $nonConformities = QualityNonConformity::with('UserManagement')
            ->where('order_lines_id', $serialNumber->order_line_id)
            ->orWhereIn('task_id', $tasks->pluck('id'))
            ->get();

        foreach ($nonConformities as $nc) {
            $timeline->push([
                'date' => $nc->created_at,
                'operation' => __('Non conformity') . ' ' . $nc->code,
                'user' => optional($nc->UserManagement)->name,
                'component' => '',
            ]);
        }

        $timeline = $timeline->sortBy('date');

        return view('production-trace', [
            'serialNumber' => $serialNumber,
            'timeline' => $timeline,
        ]);
    }
}
