<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Planning\Task;
use App\Services\TaskDateCalculator;


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

    public function __construct(TaskDateCalculator $taskDateCalculator)
    {
        parent::__construct();
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
        $result = $this->taskDateCalculator->calculateDate();
        $this->progressDate = $result['progressDate'];
        $this->progressDateLog = $result['progressDateLog'];
        $this->countTaskCalculateDate = $result['countTaskCalculateDate'];
        $this->toBeCalculateDate = $result['toBeCalculateDate'];
    }
    
}
