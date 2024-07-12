<?php

namespace App\Http\Controllers\Planning;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Events\TaskChangeStatu;
use App\Models\Planning\Status;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use Illuminate\Support\Facades\DB;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Http\Controllers\Controller;
use App\Models\Planning\SubAssembly;
use Illuminate\Support\Facades\Auth;
use App\Models\Planning\TaskResources;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\TaskActivities;
use App\Models\Methods\MethodsStandardNomenclature;

class TaskController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {

        return view('workflow/task-index');
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
                        TaskActivities::create([
                            'task_id'=> $task['id'],
                            'user_id'=> Auth::user()->id,
                            'type'=>'1',
                            'timestamp' =>Carbon::now(),
                            'comment'=>'',
                        ]);
                    }
                    elseif($status['id'] == $StatusFinishId->id){
                        // Create Line
                        TaskActivities::create([
                            'task_id'=> $task['id'],
                            'user_id'=> Auth::user()->id,
                            'type'=>'3',
                            'timestamp' =>Carbon::now(),
                            'comment'=>'',
                        ]);
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
        // Number of current OFs
        $tasksOpen = Task::whereHas('status', function($query) {
            $query->where('title', 'Open');
        })->count();

        $tasksInProgress = Task::whereHas('status', function($query) {
            $query->where('title', 'In Progress');
        })->count();

        // État des OF
        $tasksPending = Task::whereHas('status', function($query) {
            $query->where('title', 'Pending');
        })->count();

        $tasksOngoing = Task::whereHas('status', function($query) {
            $query->where('title', 'Supplied');
        })->count();

        $tasksCompleted = Task::whereHas('status', function($query) {
            $query->where('title', 'Finished');
        })->count();

        // Calculation of the average OF processing time
        $tasksWithEndDate = Task::whereNotNull('end_date')->get();
        $totalTime = $tasksWithEndDate->sum(function ($task) {
            return $task->getTotalLogTime() * 3600; //in second time
        });
        $averageProcessingTime = $totalTime / $tasksWithEndDate->count();

        // User productivity
        $userProductivity = DB::table('task_activities')
            ->join('users', 'task_activities.user_id', '=', 'users.id')
            ->select('users.name', DB::raw('count(task_activities.id) as tasks_count'))
            ->groupBy('users.name')
            ->get();

        //Ressources Time
        $totalResourcesAllocated = TaskResources::count();
        $tasks = Task::with('resources')->get();

        $resourceHours = [];
        
        foreach ($tasks as $task) {
            foreach ($task->resources as $resource) {
                $resourceName = $resource->label;
                $totalTime = $task->TotalTime();
        
                if (array_key_exists($resourceName, $resourceHours)) {
                    $resourceHours[$resourceName] += $totalTime;
                } else {
                    $resourceHours[$resourceName] = $totalTime;
                }
            }
        }
        

        return view('workflow/task-statu', compact(
                                            'tasksOpen',
                                            'tasksInProgress',
                                            'tasksPending',
                                            'tasksOngoing',
                                            'tasksCompleted',
                                            'averageProcessingTime',
                                            'userProductivity',
                                            'totalResourcesAllocated',
                                            'resourceHours'
                                            ), ['TaskId' => $request->id]);
    }
}
