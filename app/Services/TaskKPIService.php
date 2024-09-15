<?php 

namespace App\Services;

use Carbon\Carbon;
use App\Models\Planning\Task;
use Illuminate\Support\Facades\DB;
use App\Models\Planning\TaskResources;

class TaskKPIService
{
    // Number of tasks with status "Open"
    public function getOpenTasksCount()
    {
        return Task::whereHas('status', function($query) {
            $query->where('title', 'Open');
        })->count();
    }

    // Number of tasks with status "In Progress"
    public function getInProgressTasksCount()
    {
        return Task::whereHas('status', function($query) {
            $query->where('title', 'In Progress');
        })->count();
    }

    // Number of tasks with status "Pending"
    public function getPendingTasksCount()
    {
        return Task::whereHas('status', function($query) {
            $query->where('title', 'Pending');
        })->count();
    }

    // Number of tasks with status "Supplied"
    public function getSuppliedTasksCount()
    {
        return Task::whereHas('status', function($query) {
            $query->where('title', 'Supplied');
        })->count();
    }

    // Number of tasks with status "Finished"
    public function getFinishedTasksCount()
    {
        return Task::whereHas('status', function($query) {
            $query->where('title', 'Finished');
        })->count();
    }

    // Calculation of average task processing time
    public function getAverageProcessingTime()
    {
        $averageProcessingTime = 0;
        $tasksWithEndDate = Task::whereNotNull('end_date')->get();

        if ($tasksWithEndDate->count() > 0) {
            $totalTime = $tasksWithEndDate->sum(function ($task) {
                return $task->getTotalLogTime() * 3600; // en secondes
            });
            $averageProcessingTime = $totalTime / $tasksWithEndDate->count();
        }

        return $averageProcessingTime;
    }

   // Productivity per user
    public function getUserProductivity()
    {
        return DB::table('task_activities')
            ->join('users', 'task_activities.user_id', '=', 'users.id')
            ->select('users.name', DB::raw('count(task_activities.id) as tasks_count'))
            ->groupBy('users.name')
            ->get();
    }

    // Total number of resources allocated
    public function getTotalResourcesAllocated()
    {
        return TaskResources::count();
    }

    // Hours allocated per resource
    public function getResourceHours()
    {
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

        return $resourceHours;
    }

    /**
     * Calculates the total hours produced in the current month
     * 
     * @return float
     */
    public function getTotalProducedHoursCurrentMonth(): float
    {
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        $tasks = Task::whereBetween('start_date', [$currentMonthStart, $currentMonthEnd])
                        ->whereNotNull('end_date')
                        ->get();

        $totalHours = $tasks->sum(function ($task) {
            return $task->getTotalLogTime();
        });

        return round($totalHours, 2);
    }

    /**
     * Calculates the TRS in the current month
     * 
     * @return float
     */
    public function getMonthlyAverageTRS()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Retrieve tasks for the current month
        $tasks = Task::whereMonth('start_date', $currentMonth)
                    ->whereYear('start_date', $currentYear)
                    ->get();

        if ($tasks->count() === 0) {
            return 0; // Returns 0 if no task
        }

       // Calculate the sum of the TRS
        $totalTRS = $tasks->sum(function ($task) {
            return $task->getTRSAttribute();
        });

       // Calculate the average TRS
        return $totalTRS / $tasks->count();
    }

}
