<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use Illuminate\Support\Facades\Auth;
use App\Models\Planning\TaskActivities;

class TaskStatu extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    
    public $Task;
    public $taskActivities;
    public $lastTaskActivities;

    public $search = '';
    public $Factory = []; 
    public $user_id ;

    public function mount() 
    {
        $this->Factory = Factory::first();
        $this->user_id = Auth::id();
    }

    public function render()
    {
        if(!empty($this->search)){
                $lastTaskActivities = $this->lastTaskActivities = TaskActivities::where('task_id', $this->search)
                                                        ->latest()
                                                        ->first();
                
                $taskActivities = $this->taskActivities = TaskActivities::where('task_id', $this->search)->get();
                
                $Task = $this->Task = Task::with('OrderLines.order')
                                                ->find($this->search);

            }
                            
        
        return view('livewire.task-statu', [
            'Task' => $this->Task,
            'taskActivities' => $this->taskActivities,
            'lastTaskActivities' => $this->lastTaskActivities,
        ]);
    }

    public function StartTimeTask($taskId)
    {
        // Create Line
        TaskActivities::create([
            'task_id'=> $taskId,
            'user_id'=>$this->user_id,
            'type'=>'1',
            'timestamp' =>Carbon::now(),
            'comment'=>'',
        ]);
        // Set Flash Message
        session()->flash('success','Log activitie added successfully');
    }

    public function EndTimeTask($taskId)
    {
        // Create Line
        TaskActivities::create([
            'task_id'=> $taskId,
            'user_id'=>$this->user_id,
            'type'=>'2',
            'timestamp' =>Carbon::now(),
            'comment'=>'',
        ]);
        // Set Flash Message
        session()->flash('success','Log activitie added successfully');
    }
    
    public function EndTask($taskId)
    {
        // Create Line
        TaskActivities::create([
            'task_id'=> $taskId,
            'user_id'=>$this->user_id,
            'type'=>'3',
            'timestamp' =>Carbon::now(),
            'comment'=>'',
        ]);
        // Set Flash Message
        session()->flash('success','Log activitie added successfully');
    }
}
