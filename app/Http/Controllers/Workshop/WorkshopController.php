<?php

namespace App\Http\Controllers\Workshop;

use Illuminate\Http\Request;
use App\Models\Planning\Task;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Planning\TaskResources;

class WorkshopController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('workshop/workshop');
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function taskLines()
    {
        return view('workshop/workshop-task-lines');
    }

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

        return view('workshop/workshop-task-statu', compact(
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

    public function stockDetail(Request $request)
    {
        return view('workshop/workshop-stock-detail', ['StockDetailId' => $request->id]);
    }
    
}
