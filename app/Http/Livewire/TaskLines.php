<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Planning\Task;
use App\Models\Admin\Factory;

class TaskLines extends Component
{
    
    public $Tasklist;

    public $Factory = [];

    public function mount() 
    {
        $this->Factory = Factory::first();
    }

    public function render()
    {
        $Tasklist = $this->Tasklist = Task::orderBy('id', 'DESC')->whereNotNull('order_lines_id')->get();
        return view('livewire.task-lines', [
            'Tasklist' => $Tasklist,
        ]);
    }
}
