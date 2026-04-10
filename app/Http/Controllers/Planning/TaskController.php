<?php

namespace App\Http\Controllers\Planning;

use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Services\TaskService;
use App\Events\TaskChangeStatu;
use App\Models\Planning\Status;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Services\TaskKPIService;
use App\Models\Products\Products;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Http\Controllers\Controller;
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
        return view('workflow/task-gtd');
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

        return view('workflow/task-manage', compact('Document','LineInfo', 'id_type', 'id_page', 'id_line'));
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
        $tasksOpen = $this->taskKPIService->getOpenTasksCount();
        $tasksInProgress = $this->taskKPIService->getInProgressTasksCount();
        $tasksPending = $this->taskKPIService->getPendingTasksCount();
        $tasksOngoing = $this->taskKPIService->getSuppliedTasksCount();
        $tasksCompleted = $this->taskKPIService->getFinishedTasksCount();
        $averageProcessingTime = $this->taskKPIService->getAverageProcessingTime();
        $userProductivity = $this->taskKPIService->getUserProductivity();
        $totalResourcesAllocated = $this->taskKPIService->getTotalResourcesAllocated();
        $resourceHours = $this->taskKPIService->getResourceHours();
        $totalProducedHours = $this->taskKPIService->getTotalProducedHoursCurrentMonth();
        $averageTRS = $this->taskKPIService->getMonthlyAverageTRS();
        return view('workflow/task-statu', compact(
                                            'tasksOpen',
                                            'tasksInProgress',
                                            'tasksPending',
                                            'tasksOngoing',
                                            'tasksCompleted',
                                            'averageProcessingTime',
                                            'userProductivity',
                                            'totalResourcesAllocated',
                                            'resourceHours',
                                            'totalProducedHours',
                                            'averageTRS'
                                            ), ['TaskId' => $request->id]);
    }
}
