<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Planning\Task;
use App\Models\Workflow\OrderLines;
use App\Models\Times\TimesBanckHoliday;
use Illuminate\Database\Eloquent\Builder;

class TaskCalculationDate extends Component
{
    public $Tasklists = [];
    public $progressDate = 0;
    public $progressRessource = 0;
    public $toBeCalculateDate = true;
    public $toBeCalculateRessource = true;
    
    public $progressDateLog  = '';
    public $countTaskCalculateDate = 0;
    public $progressRessourceLog  = '';
    public $countTaskCalculateRessource = 0;

    public function render()
    {
        return view('livewire.task-calculation-date', [
            'Tasklists' =>  $this->Tasklists,
            'countTaskCalculateDate' =>  $this->countTaskCalculateDate,
            'countTaskCalculateRessource' =>  $this->countTaskCalculateRessource,
            'progressDateLog' =>  $this->progressDateLog,
            'progressRessourceLog' =>  $this->progressRessourceLog,
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
        
                // Calcul de la durée ajustée de la tâche
                $totalTaskHours = $task->TotalTime();
                $adjustedTaskHours = $this->calculateWorkingHours($totalTaskHours);
        
                // Calcul de la date de début
                $elapsedTimeInSeconds += ($adjustedTaskHours * 3600);
                $startDate = $this->adjustForWorkingHours(clone $taskEndDate, $elapsedTimeInSeconds);
                $task->start_date = $startDate;
                $task->save();
        
                // Mise à jour de taskEndDate pour la prochaine tâche
                $taskEndDate = $startDate;
            }

            $this->progressDate += (1 / $countLines) * 100;
        }

        $this->toBeCalculateDate = false;
    }

    /**
     * Ajuste une date pour éviter les week-ends
     */
    private function adjustForWeekends(Carbon $date): Carbon
    {
        if ($date->isSunday()) {
            return $date->subDay(); // Reculer d’un jour si c'est dimanche
        } elseif ($date->isMonday()) {
            return $date->subDays(2); // Reculer de 2 jours si c'est lundi
        }
        return $date;
    }

    /**
     * Ajuste une date en fonction des horaires de travail (8h - 18h)
     */
    private function adjustForWorkingHours(Carbon $date, int $subtractSeconds): Carbon
    {
        // On soustrait les secondes en premier
        $date->subSeconds($subtractSeconds);

        // Sécurité : on limite les itérations pour éviter une boucle infinie
        $maxIterations = 100;
        $iterations = 0;

        while ($iterations < $maxIterations) {
            // Vérifie si c'est un week-end ou un jour férié
            if ($date->isSaturday()) {
                $date->subDay()->hour(18)->minute(0);
            } elseif ($date->isSunday()) {
                $date->subDays(2)->hour(18)->minute(0);
            } elseif (TimesBanckHoliday::isBankHoliday($date)) {
                // Si c'est un jour férié, on recule d'un jour et remet l'heure à 18h
                $date->subDay()->hour(18)->minute(0);
            }

            // Si l'heure est hors des horaires de travail (8h - 18h)
            if ($date->hour < 8) {
                $date->subHours($date->hour + 10); // On revient à 18h du jour précédent
            } elseif ($date->hour >= 18) {
                $date->hour(18)->minute(0)->subSecond();
            } else {
                // On est bien dans une plage correcte et ce n'est ni un week-end ni un jour férié
                break;
            }

            $iterations++;
        }

        if ($iterations >= $maxIterations) {
            throw new \Exception("Loop limit exceeded in adjustForWorkingHours()! Date: " . $date->toDateTimeString());
        }

        return $date;
    }

    /**
     * Calcule le temps ajusté en tenant compte des week-ends et des horaires
     */
    private function calculateWorkingHours(int $totalTaskHours): int
    {
        $workingDays = floor($totalTaskHours / 8);
        $weekends = floor($workingDays / 5);
        return $totalTaskHours + ($workingDays * 16) + ($weekends * 48);
    }
    
}
