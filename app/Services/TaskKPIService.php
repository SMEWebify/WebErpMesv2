<?php 

namespace App\Services;

use Carbon\Carbon;
use App\Models\Planning\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Get the average processing time for tasks with an end date.
     *
     * This method calculates the average processing time for tasks that have an end date.
     * The result is cached for 10 minutes to improve performance.
     *
     * @return float The average processing time in seconds.
     */
    public function getAverageProcessingTime()
    {
        $cacheKey = 'average_processing_time_' . now()->year;
        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $averageProcessingTime = 0;
            $tasksWithEndDate = Task::whereNotNull('end_date')->get();

            if ($tasksWithEndDate->count() > 0) {
                $totalTime = $tasksWithEndDate->sum(function ($task) {
                    return $task->getTotalLogTime() * 3600; // en secondes
                });
                $averageProcessingTime = $totalTime / $tasksWithEndDate->count();
            }

            return $averageProcessingTime;
        });
    }

   // Productivity per user
    public function getUserProductivity()
    {
        $cacheKey = 'user_productivity_' . now()->year;
        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return DB::table('task_activities')
                ->join('users', 'task_activities.user_id', '=', 'users.id')
                ->select('users.name', DB::raw('count(task_activities.id) as tasks_count'))
                ->groupBy('users.name')
                ->get();
        });
    }

    // Total number of resources allocated
    public function getTotalResourcesAllocated()
    {
        return TaskResources::count();
    }

    /**
     * Retrieve the total hours worked by each resource across all tasks.
     *
     * This function fetches all tasks along with their associated resources,
     * then calculates the total time spent by each resource on all tasks.
     * The result is an associative array where the keys are resource names
     * and the values are the total hours worked by those resources.
     *
     * @return array An associative array with resource names as keys and total hours as values.
     */
    public function getResourceHours()
    {
        // Seules les tâches affectées à une ressource comptent ; quantités de ligne
        // préchargées pour que TotalTime() ne relise pas la ligne tâche par tâche.
        return Cache::remember('task_resource_hours', now()->addMinutes(10), function () {
            $tasks = Task::query()
                ->select(['id', 'order_lines_id', 'quote_lines_id', 'qty', 'planned_qty', 'unit_time', 'seting_time'])
                ->whereHas('resources')
                ->with(['resources:methods_ressources.id,methods_ressources.label', 'OrderLines:id,qty', 'QuoteLines:id,qty'])
                ->get();
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
        });
    }

    /**
     * Get the total produced hours for the current month.
     *
     * This method calculates the total hours produced by tasks that have been completed
     * within the current month. The result is cached for 10 minutes to improve performance.
     *
     * @return float The total produced hours for the current month, rounded to two decimal places.
     */
    public function getTotalProducedHoursCurrentMonth(): float
    {
        $cacheKey = 'total_produced_hour_current_month_' . now()->year;
        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $currentMonthStart = Carbon::now()->startOfMonth();
            $currentMonthEnd = Carbon::now()->endOfMonth();

            $tasks = Task::whereBetween('start_date', [$currentMonthStart, $currentMonthEnd])
                            ->whereNotNull('end_date')
                            ->get();

            $totalHours = $tasks->sum(function ($task) {
                return $task->getTotalLogTime();
            });

            return round($totalHours, 2);
        });
    }

    /**
     * Get the monthly average TRS (Task Rating Score) for the current month.
     *
     * This method retrieves all tasks for the current month and calculates the average TRS.
     * The result is cached for 10 minutes to improve performance.
     *
     * @return float The average TRS for the current month. Returns 0 if there are no tasks.
     */
    public function getMonthlyAverageTRS()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $cacheKey = 'monthly_average_trs_' . now()->year;
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($currentMonth, $currentYear) {

            // Retrieve tasks for the current month
            $tasks = Task::whereMonth('start_date', $currentMonth)
                        ->whereYear('start_date', $currentYear)
                        ->get();


            if ($tasks->count() === 0) {
                return 0; // Returns 0 if no task
            }

            // Calculate the sum of the TRS
            $totalTRS = $tasks->sum(function ($task) {
                $trs = $task->getTRSAttribute();
                return is_numeric($trs) ? $trs : 0; 
            });

            // Calculate the average TRS
            return $totalTRS / $tasks->count();
        });
    }

}
