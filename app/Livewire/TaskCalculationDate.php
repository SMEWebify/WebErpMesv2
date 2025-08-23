<?php

namespace App\Livewire;


use Carbon\Carbon;
use App\Support\WorkingTime;
use Livewire\Component;
use App\Models\Planning\Task;
use App\Services\TaskDateCalculator;
use App\Models\Workflow\OrderLines;
use Illuminate\Database\Eloquent\Builder;


class TaskCalculationDate extends Component
{
    protected TaskDateCalculator $taskDateCalculator;

    public $Tasklists = [];
    public $progressDate = 0;
    public $progressRessource = 0;
    public $toBeCalculateDate = true;
    public $toBeCalculateRessource = true;

    public $progressDateLog  = '';
    public $countTaskCalculateDate = 0;
    public $progressRessourceMessages  = [];
    public $countTaskCalculateRessource = 0;
    
    public function boot(TaskDateCalculator $taskDateCalculator): void
    {
        $this->taskDateCalculator = $taskDateCalculator;
    }

    public function render()
    {
        return view('livewire.task-calculation-date', [
            'Tasklists' =>  $this->Tasklists,
            'countTaskCalculateDate' =>  $this->countTaskCalculateDate,
            'countTaskCalculateRessource' =>  $this->countTaskCalculateRessource,
            'progressDateMessages' =>  $this->progressDateMessages,
            'progressRessourceMessages' =>  $this->progressRessourceMessages,
        ]);
    }

    public function calculateRessource()
    {
        $countLines = Task::whereNotNull('order_lines_id')
            ->whereDoesntHave('resources')
            ->count();

        $taskWithoutRessources = Task::whereNotNull('order_lines_id')
            ->whereDoesntHave('resources')
            ->get();

        foreach ($taskWithoutRessources as $task) {
            $service = $task->service;
            $taskDate = $task->start_date ? Carbon::parse($task->start_date) : Carbon::today();

            $resource = $service->Ressources
                ->first(function ($res) use ($taskDate, $task) {
                    return $res->remainingCapacity($taskDate) >= $task->TotalTime();
                });

            if ($resource) {
                $task->resources()->attach($resource->id, [
                    'autoselected_ressource' => 0,
                    'userforced_ressource' => 0,
                ]);

                $this->progressRessourceLog .= '<li>' . $resource->label . ' affected to task #' . $task->id . ' for ' . $task->service['label'] . ' service </li>';
            } else {
                $this->progressRessourceLog .= '<li> No ressource available for task #' . $task->id . ' for ' . $task->service['label'] . ' service </li>';
                throw new \RuntimeException('No resource has remaining capacity for task #' . $task->id);
            }


            $this->countTaskCalculateRessource += 1;
            $this->progressRessource += (1 / $countLines) * 100;
        }

        $this->toBeCalculateRessource = false;
    }

    public function calculateDate()
    {
        $OrderLines = OrderLines::with(['order', 'Task' => function ($query) {
                                $query->where('not_recalculate', 0)
                                        ->where(function (Builder $query) {
                                            return $query->where('tasks.type', 1)
                                                        ->orWhere('tasks.type', 7);
                                        })
                                        ->orderBy('ordre');
                                }])
                                ->join('orders', 'order_lines.orders_id', '=', 'orders.id')
                                ->where('order_lines.tasks_status', '!=', 4)
                                ->orderBy('order_lines.internal_delay')
                                ->select('order_lines.*')
                                ->get();

        $countLines = $OrderLines->count();

        if ($countLines === 0) {
            $this->toBeCalculateDate = false;
            return;
        }

        foreach ($OrderLines as $line) {
            $taskEndDate = Carbon::parse($line->internal_delay);
            $taskEndDate = $this->adjustForWeekends($taskEndDate);

            $elapsedTimeInSeconds = 0;

            // Trier correctement les tâches en ordre croissant
            $tasks = $line->Task->sortByDesc('ordre'); // Correction du tri

            foreach ($tasks as $task) {
                // Date de fin de la tâche actuelle
                $endDate = $this->adjustForWorkingHours(clone $taskEndDate, $elapsedTimeInSeconds);
                $task->end_date = $endDate;
        
                $this->progressDateLog .= '<li>End date : '. $endDate .' updated for task #'. $task->id .' ordre '. $task->ordre .'</li>';
        
                // Calcul du temps à retrancher en tenant compte des jours ouvrés
                $totalTaskHours = $task->TotalTime();
                $secondsToSubtract = $this->calculateWorkingHours($endDate, $totalTaskHours);

                // Calcul de la date de début
                $elapsedTimeInSeconds += $secondsToSubtract;
                $startDate = $this->adjustForWorkingHours(clone $taskEndDate, $elapsedTimeInSeconds);
                $task->start_date = $startDate;
                $task->save();
        
                // Mise à jour de taskEndDate pour la prochaine tâche
                $taskEndDate = $startDate;
            }

            $this->countTaskCalculateRessource += 1;
            $this->progressRessource += (1 / $countLines) * 100;

        }

        $this->toBeCalculateRessource = false;
    }


    /**
     * Calcule précisément le temps à retrancher en tenant compte des
     * horaires de travail, des week-ends et des jours fériés.
     */
    private function calculateWorkingHours(Carbon $fromDate, int $totalTaskHours): int
    {
        $startDate = WorkingTime::subtractWorkingHours($fromDate, $totalTaskHours);
        return $fromDate->diffInSeconds($startDate);

    }
    
}
