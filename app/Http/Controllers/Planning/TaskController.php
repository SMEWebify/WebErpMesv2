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

        return view('workflow/task-index');
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
