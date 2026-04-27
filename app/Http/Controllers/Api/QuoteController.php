<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Planning\Task;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuoteResource;
use App\Http\Requests\Api\UpsertQuoteRequest;

class QuoteController extends Controller
{
    public function index()
    {
        return QuoteResource::collection(
            Quotes::with(['companie', 'contact', 'adresse', 'QuoteLines'])->paginate(10)
        );
    }

    public function show(Quotes $quote)
    {
        $quote->load(['companie', 'contact', 'adresse', 'QuoteLines.QuoteLineDetails', 'QuoteLines.Task']);

        return new QuoteResource($quote);
    }

    public function store(UpsertQuoteRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $quote = Quotes::create(array_merge(
                $request->only([
                    'code', 'label', 'customer_reference',
                    'companies_id', 'companies_contacts_id', 'companies_addresses_id',
                    'validity_date', 'statu', 'opportunities_id',
                    'accounting_payment_conditions_id', 'accounting_payment_methods_id',
                    'accounting_deliveries_id', 'comment',
                ]),
                ['user_id' => auth()->id(), 'uuid' => Str::uuid()]
            ));

            if ($request->has('lines')) {
                $this->syncLines($quote, $request->input('lines', []));
            }

            $quote->load(['companie', 'contact', 'adresse', 'QuoteLines.QuoteLineDetails', 'QuoteLines.Task']);

            return new QuoteResource($quote);
        });
    }

    public function update(UpsertQuoteRequest $request, Quotes $quote)
    {
        return DB::transaction(function () use ($request, $quote) {
            $quote->update($request->only([
                'code', 'label', 'customer_reference',
                'companies_id', 'companies_contacts_id', 'companies_addresses_id',
                'validity_date', 'statu', 'opportunities_id',
                'accounting_payment_conditions_id', 'accounting_payment_methods_id',
                'accounting_deliveries_id', 'comment',
            ]));

            if ($request->has('lines')) {
                $this->syncLines($quote, $request->input('lines', []));
            }

            $quote->load(['companie', 'contact', 'adresse', 'QuoteLines.QuoteLineDetails', 'QuoteLines.Task']);

            return new QuoteResource($quote);
        });
    }

    private function syncLines(Quotes $quote, array $lines): void
    {
        $submittedLineIds = [];

        foreach ($lines as $lineData) {
            $lineId = $lineData['id'] ?? null;

            $linePayload = collect($lineData)->only([
                'ordre', 'code', 'product_id', 'label', 'qty',
                'methods_units_id', 'selling_price', 'discount',
                'accounting_vats_id', 'delivery_date', 'statu', 'use_calculated_price',
            ])->all();

            $linePayload['quotes_id'] = $quote->id;

            if ($lineId) {
                $line = QuoteLines::findOrFail($lineId);
                $line->update($linePayload);
            } else {
                $line = QuoteLines::create($linePayload);
            }

            $submittedLineIds[] = $line->id;

            if (array_key_exists('detail', $lineData) && $lineData['detail'] !== null) {
                $detailPayload = collect($lineData['detail'])->only([
                    'x_size', 'y_size', 'z_size', 'x_oversize', 'y_oversize', 'z_oversize',
                    'diameter', 'diameter_oversize', 'material', 'thickness', 'finishing',
                    'weight', 'bend_count', 'material_loss_rate',
                    'internal_comment', 'external_comment', 'custom_requirements',
                ])->all();

                QuoteLineDetails::updateOrCreate(
                    ['quote_lines_id' => $line->id],
                    $detailPayload
                );
            }

            if (array_key_exists('tasks', $lineData)) {
                $this->syncTasks($line, $lineData['tasks'] ?? []);
            }
        }

        // Soft-delete lines not present in the submitted payload
        $quote->QuoteLines()
            ->when(!empty($submittedLineIds), fn($q) => $q->whereNotIn('id', $submittedLineIds))
            ->when(empty($submittedLineIds), fn($q) => $q)
            ->each(fn(QuoteLines $line) => $line->delete());
    }

    private function syncTasks(QuoteLines $line, array $tasks): void
    {
        $submittedTaskIds = [];

        foreach ($tasks as $taskData) {
            $taskId = $taskData['id'] ?? null;

            $taskPayload = collect($taskData)->only([
                'code', 'label', 'ordre', 'type', 'qty', 'qty_init', 'qty_aviable',
                'methods_services_id', 'methods_units_id', 'methods_tools_id', 'products_id',
                'seting_time', 'unit_time', 'remaining_time', 'unit_cost', 'unit_price',
                'status_id', 'priority', 'delay', 'due_date',
                'material', 'thickness', 'weight',
                'x_size', 'y_size', 'z_size', 'x_oversize', 'y_oversize', 'z_oversize',
                'diameter', 'diameter_oversize', 'to_schedule', 'not_recalculate',
            ])->all();

            $taskPayload['quote_lines_id'] = $line->id;

            if ($taskId) {
                $task = Task::findOrFail($taskId);
                $task->update($taskPayload);
            } else {
                $task = Task::create($taskPayload);
            }

            $submittedTaskIds[] = $task->id;
        }

        // Soft-delete tasks not present in the submitted payload
        $line->Task()
            ->when(!empty($submittedTaskIds), fn($q) => $q->whereNotIn('id', $submittedTaskIds))
            ->when(empty($submittedTaskIds), fn($q) => $q)
            ->each(fn(Task $task) => $task->delete());
    }
}
