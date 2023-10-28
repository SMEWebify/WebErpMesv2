<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Methods\MethodsServices;

class TaskLines extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $searchIdService = '';
    public $searchIdStatus = '';
    public $sortField = 'created_at'; // default sorting field
    public $sortAsc = false; // default sort direction
    public $ShowGenericTask = false;

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
        
        $ServicesSelect = MethodsServices::select('id', 'label')->orderBy('ordre')->get();
        $StatusSelect = Status::orderBy('order', 'ASC')->get();

        if($this->ShowGenericTask){
            $Tasklist = $this->Tasklist = Task::with('OrderLines.order')
                                        ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                        ->orWhere(
                                            function($query) {
                                                return $query
                                                        ->whereNull('quote_lines_id')
                                                        ->whereNull('order_lines_id')
                                                        ->whereNull('products_id');
                                        })
                                        ->where('methods_services_id', 'like', '%'.$this->searchIdService.'%')
                                        ->where('status_id', 'like', '%'.$this->searchIdStatus.'%')
                                        ->where('label','like', '%'.$this->search.'%')->get();
        }
        else{
            $Tasklist = $this->Tasklist = Task::with('OrderLines.order')
                                        ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                        ->whereNotNull('order_lines_id')
                                        ->where('methods_services_id', 'like', '%'.$this->searchIdService.'%')
                                        ->where('status_id', 'like', '%'.$this->searchIdStatus.'%')
                                        ->where('label','like', '%'.$this->search.'%')->get();
        }
        return view('livewire.task-lines', [
            'Tasklist' => $Tasklist,
            'ServicesSelect' => $ServicesSelect,
            'StatusSelect' => $StatusSelect,
        ]);
    }
}
