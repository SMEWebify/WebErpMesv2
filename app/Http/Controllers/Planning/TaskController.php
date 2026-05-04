<?php

namespace App\Http\Controllers\Planning;

use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Services\TaskService;
use App\Events\TaskChangeStatu;
use App\Models\Planning\Status;
use App\Models\Planning\TaskActivities;
use App\Models\User;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Services\TaskKPIService;
use App\Models\Products\Products;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Http\Controllers\Controller;
use App\Models\Admin\Factory;
use App\Models\Planning\SubAssembly;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsRessources;
use App\Models\Methods\MethodsStandardNomenclature;

class TaskController extends Controller
{

    protected $taskKPIService;
    protected $taskService;

    public function __construct(TaskKPIService $taskKPIService, TaskService $taskService)
    {
        $this->taskKPIService = $taskKPIService;
        $this->taskService = $taskService;
    }
    
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $tasks = Task::with([
            'service',
            'status',
            'resources',
            'OrderLines.order',
            'SubAssembly.OrderLines.order',
            'SubAssembly.Child',
            'Component',
        ])->where(function ($query) {
            $query->where(function ($q) {
                $q->whereNotNull('sub_assembly_id')
                  ->whereHas('SubAssembly', fn ($q) => $q->whereNotNull('order_lines_id'));
            })
            ->orWhereNotNull('order_lines_id')
            ->orWhere(function ($q) {
                $q->whereNull('quote_lines_id')
                  ->whereNull('order_lines_id')
                  ->whereNull('products_id')
                  ->whereNull('sub_assembly_id');
            });
        })->get();

        $tasksData = $tasks->map(function ($task) {
            return [
                'id'                  => $task->id,
                'label'               => $task->label,
                'type'                => $task->type,
                'seting_time'         => $task->seting_time,
                'unit_time'           => $task->unit_time,
                'qty'                 => $task->qty,
                'total_time'          => $task->TotalTime(),
                'progress'            => $task->progress(),
                'formatted_end_date'  => $task->getFormattedEndDateAttribute(),
                'quality_required'    => $task->getQualityRequiredAttribute(),
                'status_id'           => $task->status_id,
                'methods_services_id' => $task->methods_services_id,
                'order_lines_id'      => $task->order_lines_id,
                'sub_assembly_id'     => $task->sub_assembly_id,
                'quote_lines_id'      => $task->quote_lines_id,
                'products_id'         => $task->products_id,
                'component_id'        => $task->component_id,
                'status'   => $task->status   ? ['id' => $task->status->id,   'title' => $task->status->title]   : null,
                'service'  => $task->service  ? ['id' => $task->service->id,  'label' => $task->service->label,  'color' => $task->service->color, 'picture' => $task->service->picture] : null,
                'resources' => $task->resources->map(fn ($r) => ['id' => $r->id, 'label' => $r->label, 'picture' => $r->picture])->values()->toArray(),
                'component' => $task->Component ? ['id' => $task->Component->id, 'label' => $task->Component->label, 'stock_color' => $task->Component->getColorStockStatu()] : null,
                'order_lines' => $task->OrderLines ? [
                    'id'       => $task->OrderLines->id,
                    'orders_id'=> $task->OrderLines->orders_id,
                    'qty'      => $task->OrderLines->qty,
                    'label'    => $task->OrderLines->label,
                    'order'    => $task->OrderLines->order ? ['id' => $task->OrderLines->order->id, 'code' => $task->OrderLines->order->code] : null,
                ] : null,
                'sub_assembly' => $task->SubAssembly ? [
                    'id'            => $task->SubAssembly->id,
                    'qty'           => $task->SubAssembly->qty,
                    'order_lines_id'=> $task->SubAssembly->order_lines_id,
                    'order_lines'   => $task->SubAssembly->OrderLines ? [
                        'orders_id' => $task->SubAssembly->OrderLines->orders_id,
                        'order'     => $task->SubAssembly->OrderLines->order ? ['code' => $task->SubAssembly->OrderLines->order->code] : null,
                    ] : null,
                    'child' => $task->SubAssembly->Child ? ['label' => $task->SubAssembly->Child->label] : null,
                ] : null,
            ];
        });

        $services        = MethodsServices::select('id', 'label')->orderBy('ordre')->get();
        $statuses        = Status::orderBy('order', 'ASC')->get(['id', 'title', 'order']);
        $resources       = MethodsRessources::select('id', 'label')->orderBy('label')->get();
        $defaultStatusIds = Status::where('title', '!=', 'Finished')->pluck('id')->toArray();

        if (empty($defaultStatusIds)) {
            $defaultStatusIds = Status::pluck('id')->toArray();
        }

        $trans = [
            'search_task_trans_key'      => __('general_content.search_task_trans_key'),
            'service_trans_key'          => __('general_content.service_trans_key'),
            'select_service_trans_key'   => __('general_content.select_service_trans_key'),
            'no_service_trans_key'       => __('general_content.no_service_trans_key'),
            'status_trans_key'           => __('general_content.status_trans_key'),
            'no_data_trans_key'          => __('general_content.no_data_trans_key'),
            'ressource_trans_key'        => __('general_content.ressource_trans_key'),
            'select_ressource_trans_key' => __('general_content.select_ressource_trans_key'),
            'show_generic_task_trans_key'=> __('general_content.show_generic_task_trans_key'),
            'order_trans_key'            => __('general_content.order_trans_key'),
            'qty_trans_key'              => __('general_content.qty_trans_key'),
            'label_trans_key'            => __('general_content.label_trans_key'),
            'product_trans_key'          => __('general_content.product_trans_key'),
            'setting_time_trans_key'     => __('general_content.setting_time_trans_key'),
            'unit_time_trans_key'        => __('general_content.unit_time_trans_key'),
            'total_time_trans_key'       => __('general_content.total_time_trans_key'),
            'progress_trans_key'         => __('general_content.progress_trans_key'),
            'end_date_trans_key'         => __('general_content.end_date_trans_key'),
            'description_trans_key'      => __('general_content.description_trans_key'),
            'view_trans_key'             => __('general_content.view_trans_key'),
            'generic_trans_key'          => __('general_content.generic_trans_key'),
        ];

        return view('workflow/task-index', compact('tasksData', 'services', 'statuses', 'resources', 'defaultStatusIds', 'trans'));
    }

    public function gtd()
    {
        $props = [
            'endpoints' => [
                'board'    => route('production.gtd.json.board'),
                'move'     => route('production.gtd.json.move'),
                'assign'   => route('production.gtd.json.assign'),
                'priority' => route('production.gtd.json.priority'),
                'comment'  => route('production.gtd.json.comment'),
            ],
        ];
        return view('workflow/task-gtd', compact('props'));
    }

    public function gtdBoard()
    {
        $statuses   = Status::orderBy('order')->get();
        $statusMap  = $this->resolveGtdStatusMap($statuses);

        $tasks = Task::query()
            ->with([
                'user:id,name',
                'secondaryAssignee:id,name',
                'status:id,title',
                'taskActivities' => fn($q) => $q
                    ->where('type', TaskActivities::TYPE_COMMENT)
                    ->with('user:id,name')
                    ->latest()
                    ->take(5),
            ])
            ->whereNull('quote_lines_id')
            ->whereNull('order_lines_id')
            ->whereNull('products_id')
            ->whereNull('sub_assembly_id')
            ->orderBy('priority')
            ->orderByRaw('due_date IS NULL, due_date')
            ->orderBy('label')
            ->get();

        $columns = [];
        foreach (['backlog', 'in_progress', 'done'] as $col) {
            $ids = $statusMap[$col] ?? [];
            $colTasks = $tasks->filter(function ($t) use ($col, $ids) {
                if ($col === 'backlog') {
                    return is_null($t->status_id) || in_array($t->status_id, $ids);
                }
                return in_array($t->status_id, $ids);
            });

            $columns[$col] = [
                'title'          => match($col) {
                    'backlog'     => __('Backlog'),
                    'in_progress' => __('In progress'),
                    'done'        => __('Done'),
                },
                'default_status' => $ids[0] ?? null,
                'tasks'          => $colTasks->map(fn($t) => $this->serializeGtdTask($t))->values(),
            ];
        }

        return response()->json([
            'columns'    => $columns,
            'users'      => User::select('id', 'name')->orderBy('name')->get(),
            'priorities' => Task::priorityLabels(),
        ]);
    }

    public function gtdMove(Request $request)
    {
        $request->validate(['task_id' => 'required|integer', 'status_id' => 'required|integer']);

        $task = Task::findOrFail($request->task_id);

        if ($task->status_id === $request->status_id) {
            return response()->json(['ok' => true]);
        }

        $statuses  = Status::orderBy('order')->get();
        $statusMap = $this->resolveGtdStatusMap($statuses);

        $task->status_id = $request->status_id;
        $task->save();

        if (in_array($request->status_id, $statusMap['in_progress'] ?? [], true)) {
            $this->taskService->recordTaskActivity($task->id, TaskActivities::TYPE_START);
        } elseif (in_array($request->status_id, $statusMap['done'] ?? [], true)) {
            $this->taskService->recordTaskActivity($task->id, TaskActivities::TYPE_FINISH);
        }

        event(new TaskChangeStatu($task->id));

        return response()->json(['ok' => true]);
    }

    public function gtdAssign(Request $request)
    {
        $request->validate(['task_id' => 'required|integer']);

        $task = Task::findOrFail($request->task_id);
        $task->user_id           = $request->filled('user_id') ? (int) $request->user_id : null;
        $task->secondary_user_id = $request->filled('secondary_user_id') ? (int) $request->secondary_user_id : null;
        $task->save();

        return response()->json(['ok' => true]);
    }

    public function gtdPriority(Request $request)
    {
        $request->validate(['task_id' => 'required|integer', 'priority' => 'required|integer']);

        $valid = array_keys(Task::priorityLabels());
        $priority = in_array($request->priority, $valid) ? $request->priority : Task::PRIORITY_MEDIUM;

        Task::findOrFail($request->task_id)->update(['priority' => $priority]);

        return response()->json(['ok' => true]);
    }

    public function gtdComment(Request $request)
    {
        $request->validate(['task_id' => 'required|integer', 'comment' => 'required|string|max:2000']);

        $this->taskService->recordTaskActivity($request->task_id, TaskActivities::TYPE_COMMENT, 0, 0, $request->comment);

        return response()->json(['ok' => true]);
    }

    protected function resolveGtdStatusMap($statuses): array
    {
        $match = fn($labels) => $statuses
            ->filter(fn($s) => collect($labels)->contains(mb_strtolower($s->title)))
            ->pluck('id')->all();

        $map = [
            'backlog'     => $match(['open', 'pending', 'to do']),
            'in_progress' => $match(['in progress', 'started', 'ongoing']),
            'done'        => $match(['finished', 'done', 'completed']),
        ];

        if (empty($map['backlog']) && $statuses->isNotEmpty()) {
            $map['backlog'] = [$statuses->first()->id];
        }
        if (empty($map['in_progress']) && $statuses->count() > 1) {
            $map['in_progress'] = [$statuses->get(1)->id ?? $statuses->first()->id];
        }
        if (empty($map['done']) && $statuses->isNotEmpty()) {
            $map['done'] = [$statuses->last()->id];
        }

        return $map;
    }

    protected function serializeGtdTask(Task $task): array
    {
        return [
            'id'                  => $task->id,
            'label'               => $task->label,
            'priority'            => $task->priority,
            'priority_label'      => $task->priority_label,
            'status_title'        => $task->status?->title,
            'user_id'             => $task->user_id,
            'user_name'           => $task->user?->name,
            'secondary_user_id'   => $task->secondary_user_id,
            'secondary_user_name' => $task->secondaryAssignee?->name,
            'due_date'            => $task->due_date?->format('Y-m-d'),
            'is_overdue'          => $task->due_date?->isPast() && !$task->due_date->isToday(),
            'is_today'            => $task->due_date?->isToday(),
            'comments'            => $task->taskActivities->map(fn($a) => [
                'user'       => $a->user?->name ?? __('System'),
                'created_at' => $a->created_at->format('d/m/Y H:i'),
                'comment'    => $a->comment,
            ])->values()->toArray(),
        ];
    }

    /**
     * @param  $id_type, $id_page, $id_line
     * @return \Illuminate\Contracts\View\View
     */
    public function manage($id_type, $id_page, $id_line)
    {
        if($id_type == 'products_id'){
            $LineInfo = SubAssembly::findOrFail($id_line); //https://github.com/SMEWebify/WebErpMesv2/issues/334
            $Document = Products::findOrFail($id_page);
        }
        elseif($id_type == 'quote_lines_id'){
            $Document = Quotes::findOrFail($id_page);
            $LineInfo = QuoteLines::findOrFail($id_line);
        }
        elseif($id_type == 'order_lines_id'){
            $Document = Orders::findOrFail($id_page);
            $LineInfo = OrderLines::findOrFail($id_line);
        }
        elseif($id_type == 'sub_assembly_id'){ //https://github.com/SMEWebify/WebErpMesv2/issues/334
            $LineInfo = SubAssembly::findOrFail($id_line);
            if($LineInfo->quote_lines_id){
                $Document = Quotes::findOrFail($id_page);
            }
            elseif($LineInfo->order_lines_id){
                $Document = Orders::findOrFail($id_page);
            }
            elseif($LineInfo->products_id){
                $Document = Products::findOrFail($id_page);
                $Document->statu = 1;
            }
        }
        elseif($id_type == 'nomenclature_lines_id'){
            $Document = MethodsStandardNomenclature::findOrFail($id_page);
            $LineInfo = $Document;
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, Something goes wrong ']);
        }

        $Factory = app('Factory');

        return view('workflow/task-manage', compact('Document','LineInfo', 'id_type', 'id_page', 'id_line', 'Factory'));
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function kanban(Request $request)
    {
         // Retrieve services
        $services = MethodsServices::all();

        // Initialize the task query
        $tasksQuery = Status::where('title', '!=', 'Supplied')
                            ->orderBy('order', 'ASC')
                            ->with(['tasks' => function ($query) use ($request) {
                                // Filter tasks by methods_services_id if provided
                                if ($request->has('methods_services_id') && !empty($request->input('methods_services_id'))) {
                                    $query->where('tasks.methods_services_id', $request->input('methods_services_id'));
                                }
                            }, 'tasks.OrderLines.order', 'tasks.service']);

        // Retrieve filtered tasks
        $tasks = $tasksQuery->get();

        return view('workflow/kanban-index', compact('tasks', 'services'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function sync(Request $request)
    {
        $this->validate(request(), [
            'columns' => ['required', 'array']
        ]);

        $StatusInProgessId = Status::select('id')->where('title', 'In Progress')->first();
        $StatusFinishId = Status::select('id')->where('title', 'Finished')->first();

        foreach ($request->columns as $status) {
            foreach ($status['tasks'] as $i => $task) {
                if ($task['status_id'] !== $status['id']) {
                    Task::find($task['id'])->update(['status_id' => $status['id']]);

                    if($status['id'] == $StatusInProgessId->id){
                         // Create Line
                        $this->taskService->recordTaskActivity($task['id'], 1, 0, 0);
                    }
                    elseif($status['id'] == $StatusFinishId->id){
                        // Create Line
                        $this->taskService->recordTaskActivity($task['id'], 3, 0, 0);
                    }

                    event(new TaskChangeStatu($task['id']));
                }
            }
        }

        $tasks = Status::where('title', '!=', 'Supplied')
                        ->orderBy('order', 'ASC')
                        ->with('tasks.OrderLines.order')
                        ->with('tasks.service')->get();
        return  $tasks;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function statu(Request $request)
    {
        $rawResourceHours = $this->taskKPIService->getResourceHours();

        $kpi = [
            'tasksOpen'             => $this->taskKPIService->getOpenTasksCount(),
            'tasksInProgress'       => $this->taskKPIService->getInProgressTasksCount(),
            'tasksPending'          => $this->taskKPIService->getPendingTasksCount(),
            'tasksOngoing'          => $this->taskKPIService->getSuppliedTasksCount(),
            'tasksCompleted'        => $this->taskKPIService->getFinishedTasksCount(),
            'averageProcessingTime' => $this->taskKPIService->getAverageProcessingTime(),
            'totalProducedHours'    => $this->taskKPIService->getTotalProducedHoursCurrentMonth(),
            'averageTRS'            => $this->taskKPIService->getMonthlyAverageTRS(),
        ];

        $userProductivity = $this->taskKPIService->getUserProductivity()->values();

        $resourceHours = collect($rawResourceHours)
            ->map(fn ($hours, $name) => ['name' => $name, 'hours' => round($hours, 2)])
            ->values();

        $trans = [
            'search_task_trans_key'              => __('general_content.search_task_trans_key'),
            'current_count_task_trans_key'       => __('general_content.current_count_task_trans_key'),
            'total_hours_per_month_trans_key'    => __('general_content.total_hours_per_month_trans_key'),
            'goal_task_trans_key'                => __('general_content.goal_task_trans_key'),
            'open_trans_key'                     => __('general_content.open_trans_key'),
            'suspended_trans_key'                => __('general_content.suspended_trans_key'),
            'supplied_trans_key'                 => __('general_content.supplied_trans_key'),
            'finished_trans_key'                 => __('general_content.finished_trans_key'),
            'average_time_task_trans_key'        => __('general_content.average_time_task_trans_key'),
            'trs_per_month_trans_key'            => __('general_content.trs_per_month_trans_key'),
            'user_productivity_trans_key'        => __('general_content.user_productivity_trans_key'),
            'user_trans_key'                     => __('general_content.user_trans_key'),
            'task_count_trans_key'               => __('general_content.task_count_trans_key'),
            'total_hours_per_resource_trans_key' => __('general_content.total_hours_per_resource_trans_key'),
            'ressource_trans_key'                => __('general_content.ressource_trans_key'),
            'total_time_trans_key'               => __('general_content.total_time_trans_key'),
            // task detail
            'quote_task_trans_key'               => __('general_content.quote_task_trans_key'),
            'task_detail_trans_key'              => __('general_content.task_detail_trans_key'),
            'informations_trans_key'             => __('general_content.informations_trans_key'),
            'line_trans_key'                     => __('general_content.line_trans_key'),
            'finish_part_qty_trans_key'          => __('general_content.finish_part_qty_trans_key'),
            'bad_part_qty_trans_key'             => __('general_content.bad_part_qty_trans_key'),
            'net_production_qty_trans_key'       => __('general_content.net_production_qty_trans_key'),
            'statu_trans_key'                    => __('general_content.statu_trans_key'),
            'qty_trans_key'                      => __('general_content.qty_trans_key'),
            'cost_trans_key'                     => __('general_content.cost_trans_key'),
            'margin_trans_key'                   => __('general_content.margin_trans_key'),
            'price_trans_key'                    => __('general_content.price_trans_key'),
            'setting_time_trans_key'             => __('general_content.setting_time_trans_key'),
            'unit_time_trans_key'                => __('general_content.unit_time_trans_key'),
            'trs_trans_key'                      => __('general_content.trs_trans_key'),
            'progress_trans_key'                 => __('general_content.progress_trans_key'),
            'logs_activity_trans_key'            => __('general_content.logs_activity_trans_key'),
            'task_trans_key'                     => __('general_content.task_trans_key'),
            'component_trans_key'                => __('general_content.component_trans_key'),
            'play_trans_key'                     => __('general_content.play_trans_key'),
            'pause_trans_key'                    => __('general_content.pause_trans_key'),
            'end_trans_key'                      => __('general_content.end_trans_key'),
            'new_purchase_document_trans_key'    => __('general_content.new_purchase_document_trans_key'),
            'remove_from_stock_trans_key'        => __('general_content.remove_from_stock_trans_key'),
            'good_rejected_trans_key'            => __('general_content.good_rejected_trans_key'),
            'quantity_rejected_trans_key'        => __('general_content.quantity_rejected_trans_key'),
            'end_date_trans_key'                 => __('general_content.end_date_trans_key'),
            'select_ressource_trans_key'         => __('general_content.select_ressource_trans_key'),
            'user_choise_trans_key'              => __('general_content.user_choise_trans_key'),
            'new_non_conformitie_trans_key'      => __('general_content.new_non_conformitie_trans_key'),
        ];

        return view('workflow/task-statu', compact('kpi', 'userProductivity', 'resourceHours', 'trans'), [
            'taskId' => $request->id,
        ]);
    }
}
