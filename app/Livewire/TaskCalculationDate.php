<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Planning\Task;
use Illuminate\Support\Facades\DB;
use App\Models\Workflow\OrderLines;
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

                $this->progressRessourceLog .= '<li>'. $resource->label. ' affected to task #'. $task->id  .' for '.  $task->service['label']  .' service </li>';
            } else {
                // Aucune ressource trouvée pour ce service, gestion des erreurs ou autre action nécessaire
                // Par exemple, vous pouvez journaliser un avertissement ou effectuer une autre logique
                // en fonction des besoins de votre application.
                $this->progressRessourceLog .= '<li> No ressource affected to task #'. $task->id  .' for '.  $task->service['label']  .' service </li>';
            }
            $this->countTaskCalculateRessource += 1;
            $this->progressRessource  += (1/$countLines)*100; 
        }     

        $this->toBeCalculateRessource = false;
    }

    public function calculateDate()
    {
        $countLines = DB::table('order_lines')
                                ->join('orders', 'order_lines.orders_id', '=', 'orders.id')
                                ->where('order_lines.tasks_status', '!=', 4)
                                ->orderBy('order_lines.internal_delay')
                                ->count();

        $OrderLines = OrderLines::with('order')
                                ->join('orders', 'order_lines.orders_id', '=', 'orders.id')
                                ->where('order_lines.tasks_status', '!=', 4)
                                ->orderBy('order_lines.internal_delay')
                                ->select('order_lines.*')
                                ->get();
                                

        //value to substrac
        $totalTaskLineTime = 0;
        foreach($OrderLines as $Line){
            //get timetamps for substract hours in decimal, dont forge for 1h = 3600 sec
            $DatetimeLine = strtotime(Carbon::parse($Line->internal_delay)->toDatetimeString());
        
            //check if internal delay is weekend
            //2 day substrac
            if(date('N', strtotime($Line->internal_delay)) == 1) $totalTaskLineTime+=3600*48;
            //1 day substrac
            if(date('N', strtotime($Line->internal_delay)) == 7) $totalTaskLineTime+=3600*24;

            // first substrac not working time from 18:00 to 0:00
            $totalTaskLineTime += 3600*7;
            $Tasks = Task::where('order_lines_id', '=', $Line->id)
                        ->where('not_recalculate', '=', 0)
                        ->where(function (Builder $query) {
                            return $query->where('tasks.type', 1)
                                        ->orWhere('tasks.type', 7);
                        })
                        ->orderByDesc('ordre')
                        ->get();

                $order = 0;
                $addfirsthour = 1;
                foreach($Tasks as $Task){
                    $endDate = date("Y-m-d H:i:s", $DatetimeLine-$totalTaskLineTime);
                    $UpdateTask = Task::find($Task->id);
                    $UpdateTask->end_date = $endDate;
                    $UpdateTask->save();
                    
                    $this->progressDateLog .= '<li>End date : '. $endDate .' updated for task #'. $Task->id  .'</li>';
                    
                    if($order ==  $Task->order_lines_id){
                        $addfirsthour = 0;
                    }
                    else{
                        $addfirsthour = 1;
                    }

                    //the range working hour, is 8h per day, so for each step of 8h from total time task, we must be add 16h
                    $loopDayCount = floor($Task->TotalTime()/8);
                    $loopWeekendCount = floor($loopDayCount/5);
                    //add 16h per day
                    $addTime = $loopDayCount*16;
                    //add 48h per weekend
                    $addTime += $loopWeekendCount * 48;
                    
                    //now we add time in sec
                    $totalTaskLineTime += ($Task->TotalTime()+$addfirsthour+$addTime)*3600;
                    $order = $Task->order_lines_id;

                    $this->countTaskCalculateDate += 1;

                }
            
            $totalTaskLineTime = 0;
            $this->progressDate  += (1/$countLines)*100; 
        }     

        $this->toBeCalculateDate = false;
    }
    
}
