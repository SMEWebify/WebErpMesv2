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
        // Dans votre contrôleur ou ailleurs où vous avez besoin de cette information
        $countLines = Task::whereNotNull('order_lines_id')->whereDoesntHave('resources')->count();

        $taskWithoutRessources = Task::whereNotNull('order_lines_id')->whereDoesntHave('resources')->get();

        foreach ($taskWithoutRessources as $task) {
            // Obtenez le service associé à la tâche
            $service = $task->service;
        
            // Obtenez la première ressource associée à ce service (ajustez selon vos besoins)
            $resource = $service->Ressources()->first();

            if ($resource) {
                // Attachez la ressource à la tâche
                $task->resources()->attach($resource->id, [
                    'autoselected_ressource' => 0,
                    'userforced_ressource' => 0,
                ]);

                $message = $resource->label. ' affected to task #'. $task->id  .' for '.  $task->service['label']  .' service';
                $this->progressRessourceMessages[] = $message;
                TaskCalculationLog::create([
                    'task_id' => $task->id,
                    'type'    => 'resource',
                    'message' => $message,
                ]);
            } else {
                // Aucune ressource trouvée pour ce service, gestion des erreurs ou autre action nécessaire
                // Par exemple, vous pouvez journaliser un avertissement ou effectuer une autre logique
                // en fonction des besoins de votre application.
                $message = 'No ressource affected to task #'. $task->id  .' for '.  $task->service['label']  .' service';
                $this->progressRessourceMessages[] = $message;
                TaskCalculationLog::create([
                    'task_id' => $task->id,
                    'type'    => 'resource',
                    'message' => $message,
                ]);
            }
            $this->countTaskCalculateRessource += 1;
            $this->progressRessource  += (1/$countLines)*100;
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
