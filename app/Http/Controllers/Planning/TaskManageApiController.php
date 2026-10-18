<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Listeners\CheckOrderLineTaskStatus;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsStandardNomenclature;
use App\Models\Methods\MethodsStandardTask;
use App\Models\Methods\MethodsTools;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\SubAssembly;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\Admin\Factory;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Enums\TaskDependencyLagUnit;
use App\Enums\TaskDependencyType;
use App\Models\Planning\TaskDependency;
use App\Services\Planning\BillOfMaterialsCopier;
use App\Services\Planning\DependencyEdge;
use App\Services\Planning\DependencyGraph;
use App\Support\Planning\DependencyNotation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TaskManageApiController extends Controller
{
    // -------------------------------------------------------------------------
    // Context resolution helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the Line model (QuoteLine, OrderLine, Product, SubAssembly…)
     * from the URL discriminator triplet.
     */
    private function resolveLine(string $idType, string $idPage, string $idLine): mixed
    {
        return match ($idType) {
            'quote_lines_id'       => QuoteLines::findOrFail($idLine),
            'order_lines_id'       => OrderLines::findOrFail($idLine),
            'products_id'          => Products::findOrFail($idPage),
            'sub_assembly_id'      => SubAssembly::findOrFail($idLine),
            'nomenclature_lines_id'=> MethodsStandardNomenclature::findOrFail($idPage),
            'generic'              => (object) ['id' => null, 'label' => '', 'qty' => 1],
            default                => abort(404),
        };
    }

    /**
     * Return the product_id associated with the current line context
     * (used to pre-filter tools linked to that product).
     */
    private function resolveContextProductId(string $idType, string $idLine): ?int
    {
        return match ($idType) {
            'products_id'     => (int) $idLine,
            'quote_lines_id'  => (int) (QuoteLines::whereKey($idLine)->value('product_id') ?? 0) ?: null,
            'order_lines_id'  => (int) (OrderLines::whereKey($idLine)->value('product_id') ?? 0) ?: null,
            'sub_assembly_id' => (int) (SubAssembly::whereKey($idLine)->value('products_id') ?? 0) ?: null,
            default           => null,
        };
    }

    /**
     * Canonical qty for the line (used in cost formula for TechCut).
     */
    private function resolveLineQty(string $idType, mixed $line): float
    {
        return match ($idType) {
            'products_id', 'nomenclature_lines_id' => 1.0,
            default => (float) ($line->qty ?? 1),
        };
    }

    /**
     * Check whether a tool is already booked in the given date range,
     * optionally excluding a task id (for updates).
     */
    private function toolIsAlreadyBooked(?int $toolId, ?string $start, ?string $end, ?int $excludeTaskId = null): bool
    {
        if (!$toolId || !$start || !$end) {
            return false;
        }

        $query = Task::where('methods_tools_id', $toolId)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function ($q2) use ($start, $end) {
                      $q2->where('start_date', '<=', $start)
                         ->where('end_date', '>=', $end);
                  });
            });

        if ($excludeTaskId) {
            $query->where('id', '!=', $excludeTaskId);
        }

        return $query->exists();
    }

    // -------------------------------------------------------------------------
    // Format helpers
    // -------------------------------------------------------------------------

    private function formatTask(Task $task, string $currency): array
    {
        return [
            'id'                   => $task->id,
            'ordre'                => $task->ordre,
            'label'                => $task->label,
            'code'                 => $task->code,
            'type'                 => $task->type,
            'methods_services_id'  => $task->methods_services_id,
            'service'              => $task->service
                ? ['id' => $task->service->id, 'code' => $task->service->code, 'label' => $task->service->label,
                   'color' => $task->service->color, 'picture' => $task->service->picture,
                   'margin' => (float) $task->service->margin, 'hourly_rate' => (float) $task->service->hourly_rate]
                : null,
            'component_id'         => $task->component_id,
            'component'            => $task->Component
                ? ['id' => $task->Component->id, 'code' => $task->Component->code, 'label' => $task->Component->label]
                : null,
            'methods_tools_id'     => $task->methods_tools_id,
            'tool'                 => $task->MethodsTools
                ? ['id' => $task->MethodsTools->id, 'code' => $task->MethodsTools->code, 'label' => $task->MethodsTools->label]
                : null,
            'seting_time'          => (float) $task->seting_time,
            'unit_time'            => (float) $task->unit_time,
            'qty'                  => (float) ($task->qty ?? 0),
            'unit_cost'            => (float) $task->unit_cost,
            'unit_price'           => (float) $task->unit_price,
            'start_date'           => $task->start_date,
            'end_date'             => $task->end_date,
            'status_id'            => $task->status_id,
            'status'               => $task->status
                ? ['id' => $task->status->id, 'title' => $task->status->title]
                : null,
            'progress'             => (float) $task->progress(),
            'total_time'           => (float) $task->TotalTime(),
            'currency'             => $currency,
        ];
    }

    private function formatSubAssembly(SubAssembly $sa): array
    {
        return [
            'id'        => $sa->id,
            'ordre'     => $sa->ordre,
            'child_id'  => $sa->child_id,
            'child'     => $sa->Child
                ? ['id' => $sa->Child->id, 'code' => $sa->Child->code, 'label' => $sa->Child->label, 'drawing_file' => $sa->Child->drawing_file ?? null]
                : null,
            'qty'        => (float) $sa->qty,
            'unit_price' => (float) $sa->unit_price,
        ];
    }

    private function formatStandardTask(\App\Models\Methods\MethodsStandardTask $task, string $currency): array
    {
        return [
            'id'                   => $task->id,
            'ordre'                => $task->ordre,
            'label'                => $task->label,
            'code'                 => $task->service?->code,
            'type'                 => $task->type,
            'methods_services_id'  => $task->methods_services_id,
            'service'              => $task->service
                ? ['id' => $task->service->id, 'code' => $task->service->code, 'label' => $task->service->label,
                   'color' => $task->service->color, 'picture' => $task->service->picture,
                   'margin' => (float) $task->service->margin, 'hourly_rate' => (float) $task->service->hourly_rate]
                : null,
            'component_id'         => $task->component_id,
            'component'            => $task->Component
                ? ['id' => $task->Component->id, 'code' => $task->Component->code, 'label' => $task->Component->label]
                : null,
            'methods_tools_id'     => $task->methods_tools_id,
            'tool'                 => $task->MethodsTools
                ? ['id' => $task->MethodsTools->id, 'code' => $task->MethodsTools->code, 'label' => $task->MethodsTools->label]
                : null,
            'seting_time'          => (float) ($task->seting_time ?? 0),
            'unit_time'            => (float) ($task->unit_time ?? 0),
            'qty'                  => (float) ($task->qty ?? 0),
            'unit_cost'            => (float) ($task->unit_cost ?? 0),
            'unit_price'           => (float) ($task->unit_price ?? 0),
            'start_date'           => null,
            'end_date'             => null,
            'status_id'            => null,
            'progress'             => 0.0,
            'total_time'           => (float) (($task->seting_time ?? 0) + ($task->unit_time ?? 0)),
            'currency'             => $currency,
        ];
    }

    // -------------------------------------------------------------------------
    // GET — initial data
    // -------------------------------------------------------------------------

    public function initialDataJson(string $idType, string $idPage, string $idLine)
    {
        abort_unless(auth()->check(), 403);

        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';

        // Generic mode: no parent document, empty lists. The card is only used
        // as an entry point to create standalone tasks.
        if ($idType === 'generic') {
            return response()->json([
                'line' => [
                    'id'                  => null,
                    'label'               => '',
                    'qty'                 => 1,
                    'custom_requirements' => [],
                ],
                'tasks' => [
                    'technical_cut' => [],
                    'bom'           => [],
                ],
                'sub_assemblies' => [],
            ]);
        }

        $line    = $this->resolveLine($idType, $idPage, $idLine);
        $lineQty = $this->resolveLineQty($idType, $line);

        // Custom requirements
        $detail = null;
        if ($idType === 'quote_lines_id') {
            $detail = $line->QuoteLineDetails ?? null;
        } elseif ($idType === 'order_lines_id') {
            $detail = $line->OrderLineDetails ?? null;
        }
        $customRequirements = collect($detail->custom_requirements ?? [])
            ->filter(fn($r) => !empty($r['label'] ?? null) || !empty($r['value'] ?? null))
            ->values();

        // Tasks
        if ($idType === 'nomenclature_lines_id') {
            // Standard tasks from MethodsStandardTask
            $stdTasks = \App\Models\Methods\MethodsStandardTask::with(['service', 'Component', 'MethodsTools', 'status'])
                ->where('methods_nomenclature_standard_id', $idLine)
                ->orderBy('ordre')
                ->get();
            $techCut = $stdTasks->filter(fn($t) => in_array($t->type, [1, 7]))->values();
            $bom     = $stdTasks->filter(fn($t) => in_array($t->type, [2, 3, 4, 5, 6, 8]))->values();
            $technicalCutData = $techCut->map(fn($t) => $this->formatStandardTask($t, $currency))->values();
            $bomData          = $bom->map(fn($t) => $this->formatStandardTask($t, $currency))->values();
            $subAssembliesData = collect();
        } else {
            $fk = $idType; // e.g. 'quote_lines_id'
            $techCut = Task::with(['service', 'Component', 'MethodsTools', 'status'])
                ->where($fk, $idLine)
                ->whereIn('type', [1, 7])
                ->orderBy('ordre')
                ->get();
            $bom = Task::with(['service', 'Component', 'MethodsTools', 'status'])
                ->where($fk, $idLine)
                ->whereIn('type', [2, 3, 4, 5, 6, 8])
                ->orderBy('ordre')
                ->get();
            $subAssemblies = SubAssembly::with('Child')
                ->where($fk, $idLine)
                ->orderBy('ordre')
                ->get();

            $technicalCutData = $techCut->map(fn($t) => $this->formatTask($t, $currency))->values();
            $bomData          = $bom->map(fn($t) => $this->formatTask($t, $currency))->values();
            $subAssembliesData = $subAssemblies->map(fn($sa) => $this->formatSubAssembly($sa))->values();
        }

        return response()->json([
            'line' => [
                'id'                  => $line->id,
                'label'               => $line->label ?? ($line->code ?? ''),
                'qty'                 => $lineQty,
                'custom_requirements' => $customRequirements,
            ],
            'tasks' => [
                'technical_cut' => $technicalCutData,
                'bom'           => $bomData,
            ],
            'sub_assemblies' => $subAssembliesData,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET — select data (dropdowns)
    // -------------------------------------------------------------------------

    public function selectDataJson(string $idType, string $idPage, string $idLine)
    {
        abort_unless(auth()->check(), 403);

        $contextProductId = $this->resolveContextProductId($idType, $idLine);

        // Tools: prefer product-linked tools, fall back to all available
        if ($contextProductId) {
            $product = Products::with('tools')->find($contextProductId);
            $tools = $product ? $product->tools->sortBy('code')->values() : MethodsTools::available()->orderBy('code')->get();
        } else {
            $tools = MethodsTools::available()->orderBy('code')->get();
        }

        return response()->json([
            'services_techcut' => MethodsServices::select('id', 'code', 'label', 'type', 'color', 'picture', 'margin', 'hourly_rate', 'ordre')
                ->whereIn('type', [1, 7])
                ->orderBy('ordre')
                ->get(),
            'services_bom' => MethodsServices::select('id', 'code', 'label', 'type', 'color', 'picture', 'margin', 'hourly_rate', 'ordre')
                ->whereIn('type', [2, 3, 4, 5, 6, 8])
                ->orderBy('ordre')
                ->get(),
            'tools' => $tools->map(fn($t) => ['id' => $t->id, 'code' => $t->code, 'label' => $t->label]),
            'products_bom' => Products::select('id', 'code', 'label', 'purchased_price', 'methods_services_id')
                ->with('service:id,type')
                ->whereRelation('service', function ($q) {
                    $q->whereIn('type', [2, 3, 4, 5, 6, 8]);
                })
                ->orderBy('code')
                ->get()
                ->map(fn($p) => ['id' => $p->id, 'code' => $p->code, 'label' => $p->label, 'purchased_price' => (float) ($p->purchased_price ?? 0)]),
            'products_subassembly' => Products::select('id', 'code', 'label', 'selling_price')
                ->with('service:id,type')
                ->whereRelation('service', 'type', 8)
                ->orderBy('code')
                ->get()
                ->map(fn($p) => ['id' => $p->id, 'code' => $p->code, 'label' => $p->label, 'selling_price' => (float) ($p->selling_price ?? 0)]),
            'units' => MethodsUnits::select('id', 'code', 'label')->orderBy('label')->get(),
            'nomenclatures' => MethodsStandardNomenclature::withCount('tasks')->orderBy('id')->get()
                ->map(fn($n) => ['id' => $n->id, 'label' => $n->label, 'task_count' => $n->tasks_count]),
            'initial_status_id' => Status::orderBy('order')->value('id'),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST — store task
    // -------------------------------------------------------------------------

    public function storeTaskJson(string $idType, string $idPage, string $idLine, Request $request)
    {
        abort_unless(auth()->check(), 403);

        if ($idType !== 'nomenclature_lines_id') {
            $this->abortIfDocumentLocked($idType, $idPage);
        }

        $validated = $request->validate([
            'label'               => 'required|string|max:255',
            'ordre'               => 'required|numeric|gt:0',
            'methods_services_id' => 'required|exists:methods_services,id',
            'type'                => 'required|integer',
            'component_id'        => 'nullable|exists:products,id',
            'methods_tools_id'    => 'nullable|exists:methods_tools,id',
            'seting_time'         => 'nullable|numeric|min:0',
            'unit_time'           => 'nullable|numeric|min:0',
            'qty'                 => 'nullable|numeric|min:0',
            'planned_qty'         => 'nullable|numeric|min:0',
            'unit_cost'           => 'required|numeric|gt:0',
            'unit_price'          => 'required|numeric|gt:0',
            'methods_units_id'    => 'nullable|exists:methods_units,id',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($this->toolIsAlreadyBooked($validated['methods_tools_id'] ?? null, $validated['start_date'] ?? null, $validated['end_date'] ?? null)) {
            return response()->json(['errors' => ['methods_tools_id' => [__('general_content.tool_already_booked_trans_key')]]], 422);
        }

        $service = MethodsServices::find($validated['methods_services_id']);

        $taskData = array_merge($validated, [
            'code'      => $service?->code,
            'status_id' => Status::orderBy('order')->value('id'),
            'qty_init'  => $validated['qty'] ?? null,
            'origin'    => '0',
        ]);

        // Generic tasks have no parent (no FK). All other types attach via $idType => $idLine.
        if ($idType !== 'generic') {
            $taskData[$idType] = $idLine;
        }

        if ($idType === 'nomenclature_lines_id') {
            $taskData['methods_nomenclature_standard_id'] = $idLine;
            unset($taskData[$idType]);
            \App\Models\Methods\MethodsStandardTask::create($taskData);
            return response()->json(['message' => 'Task created'], 201);
        }

        $task = Task::create($taskData);

        $this->promoteOrderLineTaskStatus($idType, $idLine);

        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $task->load(['service', 'component', 'MethodsTools', 'status']);

        return response()->json(['task' => $this->formatTask($task, $currency)], 201);
    }

    // -------------------------------------------------------------------------
    // PUT — update task
    // -------------------------------------------------------------------------

    public function updateTaskJson(string $idType, string $idPage, int $id, Request $request)
    {
        abort_unless(auth()->check(), 403);

        $task = Task::findOrFail($id);

        $validated = $request->validate([
            'label'               => 'required|string|max:255',
            'ordre'               => 'required|numeric|gt:0',
            'methods_services_id' => 'required|exists:methods_services,id',
            'type'                => 'required|integer',
            'component_id'        => 'nullable|exists:products,id',
            'methods_tools_id'    => 'nullable|exists:methods_tools,id',
            'seting_time'         => 'nullable|numeric|min:0',
            'unit_time'           => 'nullable|numeric|min:0',
            'qty'                 => 'nullable|numeric|min:0',
            'planned_qty'         => 'nullable|numeric|min:0',
            'unit_cost'           => 'required|numeric|gt:0',
            'unit_price'          => 'required|numeric|gt:0',
            'methods_units_id'    => 'nullable|exists:methods_units,id',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($this->toolIsAlreadyBooked($validated['methods_tools_id'] ?? null, $validated['start_date'] ?? null, $validated['end_date'] ?? null, $id)) {
            return response()->json(['errors' => ['methods_tools_id' => [__('general_content.tool_already_booked_trans_key')]]], 422);
        }

        $service = MethodsServices::find($validated['methods_services_id']);

        $task->update(array_merge($validated, [
            'code'     => $service?->code,
            'qty_init' => $validated['qty'] ?? $task->qty_init,
        ]));

        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $task->load(['service', 'component', 'MethodsTools', 'status']);

        return response()->json(['task' => $this->formatTask($task, $currency)]);
    }

    // -------------------------------------------------------------------------
    // DELETE — destroy task
    // -------------------------------------------------------------------------

    public function destroyTaskJson(string $idType, string $idPage, int $id)
    {
        abort_unless(auth()->check(), 403);

        $task = Task::findOrFail($id);
        $orderLineId = $task->order_lines_id;
        $task->delete();

        if ($orderLineId) {
            CheckOrderLineTaskStatus::syncOrderLine((int) $orderLineId);
        }

        return response()->json(['message' => 'Deleted']);
    }

    // -------------------------------------------------------------------------
    // POST — duplicate task
    // -------------------------------------------------------------------------

    public function duplicateTaskJson(string $idType, string $idPage, int $id)
    {
        abort_unless(auth()->check(), 403);

        $task = Task::findOrFail($id);

        // Copie et liens ensemble : pas de double à moitié lié si un lien échoue.
        $new = DB::transaction(function () use ($task) {
            $new  = $task->replicate();
            $new->ordre  = $task->ordre + 1;
            $new->label  = $task->label . ' #copy';
            $new->origin = '5';
            $new->save();

            // Le double démarre dans les mêmes conditions que l'original (le second
            // tour d'un lot partagé) : ses liens entrants saisis sont recopiés, pas
            // les sortants.
            foreach (TaskDependency::where('task_id', $task->id)->get() as $link) {
                TaskDependency::create([
                    'task_id'             => $new->id,
                    'predecessor_task_id' => $link->predecessor_task_id,
                    'type'                => $link->type,
                    'lag_value'           => $link->lag_value,
                    'lag_unit'            => $link->lag_unit,
                ]);
            }

            return $new;
        });

        if ($new->order_lines_id) {
            $this->promoteOrderLineTaskStatus('order_lines_id', (string) $new->order_lines_id);
        }

        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $new->load(['service', 'component', 'MethodsTools', 'status']);

        return response()->json(['task' => $this->formatTask($new, $currency)], 201);
    }

    // -------------------------------------------------------------------------
    // GET / PUT — liens entre opérations (task_dependencies)
    // -------------------------------------------------------------------------

    /**
     * Liens entrants d'une opération et liens de toute sa gamme. Une opération
     * sans lien saisi suit la précédente (fin-début implicite).
     */
    public function dependenciesJson(string $idType, string $idPage, int $id)
    {
        abort_unless(auth()->check(), 403);

        $task = Task::findOrFail($id);

        return response()->json($this->formatDependencies($task, DependencyGraph::fromTasks($this->routingTasks($task))));
    }

    /**
     * Remplace l'ensemble des liens entrants d'une opération. Accepte une liste
     * `links` ou la notation courte `notation` (« 20FD;30DD+2h »), vide pour
     * revenir à l'enchaînement sur l'opération précédente.
     *
     * Refusé si : autre gamme, opération d'achat, décalage en pièces sur un
     * fin-début, doublon, ou boucle dans le graphe effectif (liens implicites
     * compris).
     */
    public function updateDependenciesJson(string $idType, string $idPage, int $id, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $this->abortIfDocumentLocked($idType, $idPage);

        $validated = $request->validate([
            'notation'                    => 'nullable|string|max:500',
            'links'                       => 'nullable|array',
            'links.*.predecessor_task_id' => 'required|integer',
            'links.*.type'                => ['required', Rule::enum(TaskDependencyType::class)],
            'links.*.lag_value'           => 'nullable|numeric|min:0',
            'links.*.lag_unit'            => ['nullable', Rule::enum(TaskDependencyLagUnit::class)],
        ]);

        $task    = Task::findOrFail($id);
        $routing = $this->routingTasks($task)->keyBy('id');

        try {
            $links = $request->has('notation')
                ? DependencyNotation::parse((string) ($validated['notation'] ?? ''), $this->taskIdByOrdre($routing))
                : array_map(fn (array $link) => [
                    'predecessor_task_id' => (int) $link['predecessor_task_id'],
                    'type'                => TaskDependencyType::from($link['type']),
                    'lag_value'           => (float) ($link['lag_value'] ?? 0),
                    'lag_unit'            => TaskDependencyLagUnit::from($link['lag_unit'] ?? TaskDependencyLagUnit::Hour->value),
                ], $validated['links'] ?? []);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['errors' => ['links' => [$e->getMessage()]]], 422);
        }

        $errors = $this->dependencyErrors($task, $links, $routing);
        if ($errors !== []) {
            return response()->json(['errors' => ['links' => $errors]], 422);
        }

        DB::transaction(function () use ($task, $links) {
            TaskDependency::where('task_id', $task->id)->delete();

            foreach ($links as $link) {
                TaskDependency::create(['task_id' => $task->id] + $link);
            }
        });

        return response()->json($this->formatDependencies($task, DependencyGraph::fromTasks($this->routingTasks($task))));
    }

    /**
     * Opérations planifiables (fabrication, sous-traitance) de la même gamme :
     * même porteur (ligne de devis, ligne de commande ou article) et même
     * sous-ensemble.
     */
    private function routingTasks(Task $task): EloquentCollection
    {
        $query = Task::with('predecessorLinks')
            ->whereIn('type', [1, 7])
            ->orderBy('ordre')
            ->orderBy('id');

        $carrier = collect(BillOfMaterialsCopier::KEYS)->first(fn (string $key) => ! empty($task->{$key}));

        if ($carrier === null && empty($task->sub_assembly_id)) {
            return $query->whereKey($task->id)->get();
        }

        if ($carrier !== null) {
            $query->where($carrier, $task->{$carrier});
        }

        return empty($task->sub_assembly_id)
            ? $query->whereNull('sub_assembly_id')->get()
            : $query->where('sub_assembly_id', $task->sub_assembly_id)->get();
    }

    /** Numéro d'opération → id ; à numéro égal, la première opération de la gamme. */
    private function taskIdByOrdre($routing): array
    {
        $map = [];
        foreach ($routing as $task) {
            $map[(string) $task->ordre] ??= (int) $task->id;
        }

        return $map;
    }

    /**
     * @param  array<int, array{predecessor_task_id: int, type: TaskDependencyType, lag_value: float, lag_unit: TaskDependencyLagUnit}>  $links
     * @return string[]
     */
    private function dependencyErrors(Task $task, array $links, $routing): array
    {
        if (! in_array((int) $task->type, [1, 7], true)) {
            return [__('planning.dependency_not_schedulable')];
        }

        $errors = [];
        $seen   = [];

        foreach ($links as $link) {
            $predecessorId = $link['predecessor_task_id'];

            if ($predecessorId === (int) $task->id) {
                $errors[] = __('planning.dependency_self');
                continue;
            }

            if (! $routing->has($predecessorId)) {
                $other = Task::find($predecessorId);
                $errors[] = $other && ! in_array((int) $other->type, [1, 7], true)
                    ? __('planning.dependency_not_schedulable')
                    : __('planning.dependency_other_routing', ['ordre' => $other->ordre ?? ('#' . $predecessorId)]);
                continue;
            }

            if ($link['lag_unit'] === TaskDependencyLagUnit::Piece && ! $link['type']->acceptsPieceLag()) {
                $errors[] = __('planning.dependency_piece_lag');
            }

            // Saisie en liste comme en notation : au-delà, la colonne refuse l'écriture.
            if ($link['lag_value'] > TaskDependency::MAX_LAG) {
                $errors[] = __('planning.dependency_lag_too_large', ['max' => TaskDependency::MAX_LAG]);
            }

            $key = $predecessorId . $link['type']->value;
            if (isset($seen[$key])) {
                $errors[] = __('planning.dependency_duplicate', ['link' => $routing[$predecessorId]->ordre . $link['type']->shortLabel()]);
            }
            $seen[$key] = true;
        }

        if ($errors !== []) {
            return $errors;
        }

        // Graphe effectif une fois les liens remplacés : une boucle peut passer
        // par un lien implicite (« 10 après 20 » quand 20 suit encore 10).
        $candidate = $routing->values()->map(function (Task $other) use ($task, $links) {
            if ((int) $other->id !== (int) $task->id) {
                return $other;
            }

            $copy = clone $other;
            $copy->setRelation('predecessorLinks', new EloquentCollection(array_map(
                fn (array $link) => new TaskDependency(['task_id' => $task->id] + $link),
                $links
            )));

            return $copy;
        });

        $graph = DependencyGraph::fromTasks($candidate);

        return $graph->hasCycle()
            ? [__('planning.dependency_cycle', ['cycle' => $graph->describeCycle()])]
            : [];
    }

    /**
     * Liens de chaque opération de la gamme, par id : implicite ou non, notation
     * saisie, texte affiché (« après 20 » ou la notation) et détail des liens.
     *
     * @return array<int, array{implicit: bool, notation: string, display: string, links: array}>
     */
    private function routingLinks(DependencyGraph $graph): array
    {
        $ordre = array_map(fn (Task $t) => $t->ordre, $graph->tasks());
        $out   = [];

        foreach ($graph->tasks() as $id => $t) {
            $incoming = $graph->incoming($id);
            $implicit = $incoming !== [] && $incoming[0]->implicit;
            $notation = $implicit ? '' : DependencyNotation::format($incoming, $ordre);

            $out[$id] = [
                'implicit' => $implicit,
                'notation' => $notation,
                'display'  => $implicit
                    ? __('planning.dependency_implicit', ['ordre' => $ordre[$incoming[0]->from] ?? ''])
                    : $notation,
                'links'    => array_map(fn (DependencyEdge $edge) => [
                    'predecessor_task_id' => $edge->from,
                    'predecessor_ordre'   => $ordre[$edge->from] ?? null,
                    'type'                => $edge->type->value,
                    'type_label'          => $edge->type->shortLabel(),
                    'lag_value'           => $edge->lagValue,
                    'lag_unit'            => $edge->lagUnit->value,
                    'implicit'            => $edge->implicit,
                ], $incoming),
            ];
        }

        return $out;
    }

    private function formatDependencies(Task $task, DependencyGraph $graph): array
    {
        $links = $this->routingLinks($graph);

        return [
            'task_id' => $task->id,
            'cycle'   => $graph->hasCycle() ? $graph->describeCycle() : null,
            'task'    => $links[$task->id] ?? ['implicit' => false, 'notation' => '', 'display' => '', 'links' => []],
            'routing' => array_values(array_map(fn (Task $t) => [
                'id'    => $t->id,
                'ordre' => $t->ordre,
                'label' => $t->label,
            ] + $links[$t->id], $graph->tasks())),
        ];
    }

    // -------------------------------------------------------------------------
    // POST — store sub-assembly
    // -------------------------------------------------------------------------

    public function storeSubAssemblyJson(string $idType, string $idPage, string $idLine, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $this->abortIfDocumentLocked($idType, $idPage);

        $validated = $request->validate([
            'ordre'    => 'required|numeric|gt:0',
            'child_id' => 'required|exists:products,id',
            'qty'      => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $saData = $idType === 'generic' ? $validated : array_merge($validated, [$idType => $idLine]);
        $sa = SubAssembly::create($saData);
        $sa->load('Child');

        return response()->json(['sub_assembly' => $this->formatSubAssembly($sa)], 201);
    }

    // -------------------------------------------------------------------------
    // PUT — update sub-assembly
    // -------------------------------------------------------------------------

    public function updateSubAssemblyJson(string $idType, string $idPage, int $id, Request $request)
    {
        abort_unless(auth()->check(), 403);

        $sa = SubAssembly::findOrFail($id);

        $validated = $request->validate([
            'ordre'      => 'required|numeric|gt:0',
            'child_id'   => 'required|exists:products,id',
            'qty'        => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $sa->update($validated);
        $sa->load('Child');

        return response()->json(['sub_assembly' => $this->formatSubAssembly($sa)]);
    }

    // -------------------------------------------------------------------------
    // DELETE — destroy sub-assembly
    // -------------------------------------------------------------------------

    public function destroySubAssemblyJson(string $idType, string $idPage, int $id)
    {
        abort_unless(auth()->check(), 403);

        SubAssembly::findOrFail($id)->delete();

        return response()->json(['message' => 'Deleted']);
    }

    // -------------------------------------------------------------------------
    // POST — duplicate sub-assembly
    // -------------------------------------------------------------------------

    public function duplicateSubAssemblyJson(string $idType, string $idPage, int $id)
    {
        abort_unless(auth()->check(), 403);

        $sa  = SubAssembly::findOrFail($id);
        $new = $sa->replicate();
        $new->ordre = $sa->ordre + 1;
        $new->save();
        $new->load('Child');

        return response()->json(['sub_assembly' => $this->formatSubAssembly($new)], 201);
    }

    // -------------------------------------------------------------------------
    // POST — import BOM CSV
    // -------------------------------------------------------------------------

    public function importCsvJson(string $idType, string $idPage, string $idLine, Request $request)
    {
        abort_unless(auth()->check(), 403);

        $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);

        $path   = $request->file('csv_file')->getRealPath();
        $rows   = array_map('str_getcsv', file($path));
        $header = array_shift($rows);

        $successCount  = 0;
        $errorMessages = [];

        foreach ($rows as $row) {
            if (count($row) !== count($header)) {
                continue;
            }
            $rowData = array_combine($header, $row);

            $service = MethodsServices::where('code', $rowData['service_code'] ?? '')->first();
            $product = Products::where('code', $rowData['component_code'] ?? '')->first();
            $unit    = MethodsUnits::where('code', $rowData['unit_code'] ?? '')->first();

            $rowData['methods_services_id'] = $service?->id;
            $rowData['type']                = $service?->type;
            $rowData['component_id']        = $product?->id;
            $rowData['methods_units_id']    = $unit?->id;
            $rowData[$idType]               = $idLine;

            unset($rowData['service_code'], $rowData['component_code'], $rowData['unit_code']);

            $v = Validator::make($rowData, [
                'ordre'               => 'required|integer',
                'methods_services_id' => 'required|exists:methods_services,id',
                'label'               => 'required|string|max:255',
                'component_id'        => 'required|exists:products,id',
                'methods_units_id'    => 'required|exists:methods_units,id',
                'qty'                 => 'required|numeric|min:1',
                'unit_cost'           => 'required|numeric',
                'unit_price'          => 'required|numeric',
            ]);

            if ($v->fails()) {
                $errorMessages[] = 'Ligne : ' . implode(', ', $row) . ' — ' . $v->errors()->first();
                continue;
            }

            Task::create($rowData);
            $successCount++;
        }

        if ($successCount > 0) {
            $this->promoteOrderLineTaskStatus($idType, $idLine);
        }

        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $fk       = $idType;
        $bomTasks = Task::with(['service', 'Component', 'MethodsTools', 'status'])
            ->where($fk, $idLine)
            ->whereIn('type', [2, 3, 4, 5, 6, 8])
            ->orderBy('ordre')
            ->get()
            ->map(fn($t) => $this->formatTask($t, $currency))
            ->values();

        return response()->json([
            'success_count' => $successCount,
            'errors'        => $errorMessages,
            'bom'           => $bomTasks,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST — apply standard nomenclature template
    // -------------------------------------------------------------------------

    public function applyNomenclatureJson(string $idType, string $idPage, string $idLine, int $nomenclatureId)
    {
        abort_unless(auth()->check(), 403);

        $standardTasks = MethodsStandardTask::with('service:id,code')
            ->where('methods_nomenclature_standard_id', $nomenclatureId)
            ->get();

        foreach ($standardTasks as $std) {
            $taskData = $std->only([
                'label', 'ordre', 'methods_services_id', 'component_id',
                'seting_time', 'unit_time', 'remaining_time',
                'type', 'qty', 'qty_init', 'unit_cost', 'unit_price',
                'methods_units_id', 'x_size', 'y_size', 'z_size',
                'x_oversize', 'y_oversize', 'z_oversize',
                'diameter', 'diameter_oversize', 'to_schedule',
                'material', 'thickness', 'weight', 'methods_tools_id',
            ]);
            $taskData['code']      = $std->service?->code;
            $taskData['status_id'] = Status::orderBy('order')->value('id');
            $taskData['origin']    = '1';
            $taskData[$idType]     = $idLine;
            Task::create($taskData);
        }

        if ($standardTasks->isNotEmpty()) {
            $this->promoteOrderLineTaskStatus($idType, $idLine);
        }

        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $fk       = $idType;

        $techCut = Task::with(['service', 'Component', 'MethodsTools', 'status'])
            ->where($fk, $idLine)->whereIn('type', [1, 7])->orderBy('ordre')->get()
            ->map(fn($t) => $this->formatTask($t, $currency))->values();
        $bom = Task::with(['service', 'Component', 'MethodsTools', 'status'])
            ->where($fk, $idLine)->whereIn('type', [2, 3, 4, 5, 6, 8])->orderBy('ordre')->get()
            ->map(fn($t) => $this->formatTask($t, $currency))->values();

        return response()->json([
            'technical_cut' => $techCut,
            'bom'           => $bom,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private: abort if parent document is not open (statu != 1)
    // -------------------------------------------------------------------------

    private function promoteOrderLineTaskStatus(string $idType, string $idLine): void
    {
        if ($idType !== 'order_lines_id') {
            return;
        }

        CheckOrderLineTaskStatus::syncOrderLine((int) $idLine);
    }

    private function abortIfDocumentLocked(string $idType, string $idPage): void
    {
        $statu = match ($idType) {
            'quote_lines_id'  => \App\Models\Workflow\Quotes::whereKey($idPage)->value('statu'),
            'order_lines_id'  => \App\Models\Workflow\Orders::whereKey($idPage)->value('statu'),
            'products_id', 'sub_assembly_id' => 1, // products are always editable
            default           => 1,
        };

        abort_if($statu != 1, 403, 'Document is locked.');
    }
}
