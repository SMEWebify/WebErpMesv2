<?php

namespace App\Listeners;

use App\Models\Planning\Task;
use App\Events\TaskChangeStatu;
use App\Models\Planning\Status;
use App\Models\Workflow\OrderLines;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckOrderLineTaskStatus
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TaskChangeStatu $event)
    {
        $task = Task::find($event->taskId);
        $orderLineId = $task->order_lines_id;
    
        // Trouvez l'ID du statut "Finished"
        $finishedStatusId = Status::where('title', 'Finished')->value('id');
        
        $allLinesOderLine = Task::where('order_lines_id', $orderLineId)
            ->where('status_id', '<>', $finishedStatusId) 
            ->doesntExist();
            
        #1 = No task
        #2 = Created
        #3 = In progress
        #4 = Finished (all the tasks are finished)
        if ($allLinesOderLine) {
            OrderLines::where('id', $orderLineId)->update(['tasks_status' => 4]);
        }
        else{
            
            OrderLines::where('id',$orderLineId)->update(['tasks_status'=> 3]);
        }
    }
}
