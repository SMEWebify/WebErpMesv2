<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Planning\Task;
use App\Events\TaskChangeStatu;
use App\Models\Planning\Status;
use Illuminate\Support\Facades\Auth;
use App\Models\Planning\TaskActivities;
use App\Models\Quality\QualityNonConformity;

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

    
    public $addGoodQt = 0;
    public $addBadQt = 0;
    public $not_recalculate = true;
    private $RecalculateBooleanValue = 0;
    public $end_date;

    // Validation Rules
    protected $rules = [
        'addGoodQt' =>'required|numeric|min:0',
        'addBadQt' =>'required|numeric|min:0',
    ];

    public function mount($id) 
    {
        $this->user_id = Auth::id();
        $this->search = $id;
        $this->lastTaskActivities = TaskActivities::where('task_id', $this->search)->latest()->first();
        $this->taskActivities = TaskActivities::where('task_id', $this->search)->get();
        $this->Task = Task::with('OrderLines.order')->find($this->search);
       // $this->end_date = $this->Task->end_date;
    }

    public function render()
    {
        
        if(!empty($this->search)){
            $this->lastTaskActivities = TaskActivities::where('task_id', $this->search)->latest()->first();
            $this->taskActivities = TaskActivities::where('task_id', $this->search)->get();
            $this->Task = Task::with('OrderLines.order')->find($this->search);
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

        $StatusUpdate = Status::select('id')->where('title', 'In progress')->first();

        /* // update task statu on Kanban*/
        if($StatusUpdate->id){
            $Task = Task::where('id',$taskId)->update(['status_id'=>$StatusUpdate->id]);
            event(new TaskChangeStatu($taskId));
        }

        $this->render();

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

        $this->render();

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
        $StatusUpdate = Status::select('id')->where('title', 'Finished')->first();

        /* // update task statu on Kanban*/
        if($StatusUpdate->id){
            $Task = Task::where('id',$taskId)->update(['status_id'=>$StatusUpdate->id]);
            event(new TaskChangeStatu($taskId));
        }

        $this->render();

        // Set Flash Message
        session()->flash('success','Log activitie added successfully');
    }

    public function addGoodQt()
    {
        $this->validate();
        // Create Line
        TaskActivities::create([
            'task_id'=> $this->search,
            'user_id'=>$this->user_id,
            'type'=>'4',
            'good_qt'=>$this->addGoodQt,
            'comment'=>'',
        ]);

        $this->render();

        // Set Flash Message
        session()->flash('success','Log activitie added successfully');
    }

    public function addRejectedQt()
    {
        $this->validate();
        // Create Line
        TaskActivities::create([
            'task_id'=> $this->search,
            'user_id'=>$this->user_id,
            'type'=>'5',
            'bad_qt'=>$this->addBadQt,
            'comment'=>'',
        ]);

        $this->render();

        // Set Flash Message
        session()->flash('success','Log activitie added successfully');
    }

    public function updateDateTask(){
        if($this->not_recalculate) $this->RecalculateBooleanValue = 1;
        Task::find($this->search)->fill([
            'not_recalculate'=>$this->RecalculateBooleanValue,
            'end_date'=>$this->end_date,
        ])->save();

        session()->flash('success','Date Updated Successfully');
    }

    public function createNC($id, $companie_id, $id_service){
        $NewNonConformity = QualityNonConformity::create([
            'code'=> "NC-TASK-#". $id,
            'label'=>"NC-TASK-#". $id,
            'statu'=>1,
            'type'=>1,
            'user_id'=>Auth::id(),
            'methods_services_id' =>$id_service,
            'companie_id'=>$companie_id,
            'task_id'=>$id,
        ]);

return redirect()->route('quality')->with('success', 'Successfully created non conformitie.');
}
}
