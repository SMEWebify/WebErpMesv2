<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;

class TaskLines extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'id'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $Tasklist;
    public $Factory = [];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortAsc = !$this->sortAsc; 
        } else {
            $this->sortAsc = true; 
        }
        $this->sortField = $field;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function mount() 
    {
        $this->Factory = Factory::first();
    }

    public function render()
    {
        $Tasklist = $this->Tasklist = Task::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->whereNotNull('order_lines_id')->where('LABEL','like', '%'.$this->search.'%')->get();
        return view('livewire.task-lines', [
            'Tasklist' => $Tasklist,
        ]);
    }
}
