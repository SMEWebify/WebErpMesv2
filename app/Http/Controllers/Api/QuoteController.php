<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DocumentCodeGenerator;
use App\Models\Workflow\Quotes;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingVat;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsServices;
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

    public function store(UpsertQuoteRequest $request, DocumentCodeGenerator $codeGenerator)
    {
        $logger = Log::channel('quote_import');

        $logger->info('STORE — payload reçu', [
            'user_id'    => Auth::id(),
            'ip'         => $request->ip(),
            'payload'    => $request->except(['password']),
        ]);

        try {
            $result = DB::transaction(function () use ($request, $codeGenerator) {
                $data = $request->only([
                    'code', 'label', 'customer_reference',
                    'companies_id', 'companies_contacts_id', 'companies_addresses_id',
                    'validity_date', 'statu', 'opportunities_id',
                    'accounting_payment_conditions_id', 'accounting_payment_methods_id',
                    'accounting_deliveries_id', 'comment',
                ]);

                if (empty($data['code'])) {
                    $data['code'] = $codeGenerator->generateDocumentCode('quote');
                }

                if (empty($data['accounting_payment_conditions_id'])) {
                    $data['accounting_payment_conditions_id'] = AccountingPaymentConditions::where('default', 1)->value('id');
                }

                if (empty($data['accounting_payment_methods_id'])) {
                    $data['accounting_payment_methods_id'] = AccountingPaymentMethod::where('default', 1)->value('id');
                }

                if (empty($data['accounting_deliveries_id'])) {
                    $data['accounting_deliveries_id'] = AccountingDelivery::where('default', 1)->value('id');
                }

                $data['uuid']    = (string) Str::uuid();
                $data['user_id'] = Auth::id();

                $quote = Quotes::create($data);

                $lines = $request->input('lines', []);
                if ($request->has('lines')) {
                    $this->syncLines($quote, $lines);
                }

                $quote->load(['companie', 'contact', 'adresse', 'QuoteLines.QuoteLineDetails', 'QuoteLines.Task']);

                return [$quote, count($lines)];
            });

            [$quote, $lineCount] = $result;

            $logger->info('STORE — devis créé', [
                'quote_id'   => $quote->id,
                'uuid'       => $quote->uuid,
                'code'       => $quote->code,
                'lines'      => $lineCount,
                'tasks'      => $quote->QuoteLines->sum(fn ($l) => $l->Task->count()),
            ]);

            return new QuoteResource($quote);

        } catch (\Throwable $e) {
            $logger->error('STORE — échec', [
                'user_id' => Auth::id(),
                'error'   => $e->getMessage(),
                'payload' => $request->except(['password']),
            ]);
            throw $e;
        }
    }

    public function update(UpsertQuoteRequest $request, Quotes $quote)
    {
        $logger = Log::channel('quote_import');

        $logger->info('UPDATE — payload reçu', [
            'quote_id' => $quote->id,
            'uuid'     => $quote->uuid,
            'code'     => $quote->code,
            'user_id'  => Auth::id(),
            'ip'       => $request->ip(),
            'payload'  => $request->except(['password']),
        ]);

        try {
            $result = DB::transaction(function () use ($request, $quote) {
                $quote->update($request->only([
                    'code', 'label', 'customer_reference',
                    'companies_id', 'companies_contacts_id', 'companies_addresses_id',
                    'validity_date', 'statu', 'opportunities_id',
                    'accounting_payment_conditions_id', 'accounting_payment_methods_id',
                    'accounting_deliveries_id', 'comment',
                ]));

                $lines = $request->input('lines', []);
                if ($request->has('lines')) {
                    $this->syncLines($quote, $lines);
                }

                $quote->load(['companie', 'contact', 'adresse', 'QuoteLines.QuoteLineDetails', 'QuoteLines.Task']);

                return [$quote, count($lines)];
            });

            [$quote, $lineCount] = $result;

            $logger->info('UPDATE — devis mis à jour', [
                'quote_id' => $quote->id,
                'uuid'     => $quote->uuid,
                'code'     => $quote->code,
                'lines'    => $lineCount,
                'tasks'    => $quote->QuoteLines->sum(fn ($l) => $l->Task->count()),
            ]);

            return new QuoteResource($quote);

        } catch (\Throwable $e) {
            $logger->error('UPDATE — échec', [
                'quote_id' => $quote->id,
                'user_id'  => Auth::id(),
                'error'    => $e->getMessage(),
                'payload'  => $request->except(['password']),
            ]);
            throw $e;
        }
    }

    private function syncLines(Quotes $quote, array $lines): void
    {
        $submittedLineIds = [];
        $defaultUnitId = MethodsUnits::getDefault()?->id;
        $defaultVatId  = AccountingVat::getDefault()?->id;

        foreach ($lines as $lineData) {
            $lineId = $lineData['id'] ?? null;

            $linePayload = collect($lineData)->only([
                'ordre', 'code', 'product_id', 'label', 'qty',
                'methods_units_id', 'selling_price', 'discount',
                'accounting_vats_id', 'delivery_date', 'statu', 'use_calculated_price',
            ])->all();

            $linePayload['quotes_id']        = $quote->id;
            $linePayload['methods_units_id'] = $linePayload['methods_units_id'] ?? $defaultUnitId;
            $linePayload['accounting_vats_id'] = $linePayload['accounting_vats_id'] ?? $defaultVatId;

            if ($lineId) {
                $line = QuoteLines::where('quotes_id', $quote->id)->findOrFail($lineId);
                $line->update($linePayload);
            } else {
                $line = QuoteLines::create($linePayload);
            }

            $submittedLineIds[] = $line->id;

            if (\array_key_exists('detail', $lineData) && $lineData['detail'] !== null) {
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

            if (\array_key_exists('tasks', $lineData)) {
                $this->syncTasks($line, $lineData['tasks'] ?? []);
            }
        }

        // Soft-delete lines not present in the submitted payload
        $quote->QuoteLines()
            ->when(!empty($submittedLineIds), fn ($q) => $q->whereNotIn('id', $submittedLineIds))
            ->each(fn (QuoteLines $line) => $line->delete());
    }

    private function resolveServicesMap(array $tasks): array
    {
        // Collecte operation_code en priorité, sinon code
        $codes = collect($tasks)
            ->map(fn ($t) => $t['operation_code'] ?? $t['code'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($codes)) {
            return [];
        }

        // Retourne id ET type par code pour peupler les deux champs sur la tâche
        return MethodsServices::whereIn('code', $codes)
            ->get(['code', 'id', 'type'])
            ->keyBy('code')
            ->map(fn ($s) => ['id' => $s->id, 'type' => $s->type])
            ->all();
    }

    private function syncTasks(QuoteLines $line, array $tasks): void
    {
        $submittedTaskIds = [];
        $logger           = Log::channel('quote_import');
        $servicesMap      = $this->resolveServicesMap($tasks);
        $nextOrdre        = 1;

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

            // 1. Résolution du service : operation_code prioritaire, sinon code
            if (empty($taskPayload['methods_services_id'])) {
                $lookupCode = $taskData['operation_code'] ?? $taskData['code'] ?? null;
                if ($lookupCode) {
                    if (isset($servicesMap[$lookupCode])) {
                        $taskPayload['methods_services_id'] = $servicesMap[$lookupCode]['id'];
                        // Copie le type du service si non fourni explicitement
                        $taskPayload['type'] ??= $servicesMap[$lookupCode]['type'];
                    } else {
                        $logger->warning('operation_code non trouvé dans methods_services — methods_services_id laissé vide', [
                            'lookup_code'    => $lookupCode,
                            'task_label'     => $taskData['label'] ?? null,
                            'quote_lines_id' => $line->id,
                        ]);
                    }
                }
            }

            // 2. Origine import (8 = Nest4Quote interface)
            $taskPayload['origin'] ??= '8';

            // 3. Ordre auto-incrémenté si absent
            if (empty($taskPayload['ordre'])) {
                $taskPayload['ordre'] = $nextOrdre;
            }
            $nextOrdre = $taskPayload['ordre'] + 1;

            $taskPayload['quote_lines_id'] = $line->id;

            if ($taskId) {
                $task = Task::where('quote_lines_id', $line->id)->findOrFail($taskId);
                $task->update($taskPayload);
            } else {
                $task = Task::create($taskPayload);
            }

            $submittedTaskIds[] = $task->id;
        }

        // Soft-delete tasks not present in the submitted payload
        $line->Task()
            ->when(!empty($submittedTaskIds), fn ($q) => $q->whereNotIn('id', $submittedTaskIds))
            ->each(fn (Task $task) => $task->delete());
    }
}
