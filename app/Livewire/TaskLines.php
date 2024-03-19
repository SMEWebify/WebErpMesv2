<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
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
    public $sortField = 'end_date'; // default sorting field
    public $sortAsc = true; // default sort direction
    public $ShowGenericTask = false;

    public $Tasklist;
    public $Factory = [];
    public $todayDate = '';

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
        $this->todayDate = Carbon::today();
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
                                                return $query // Tasks with non-null id lines
                                                        ->whereNull('quote_lines_id')
                                                        ->whereNull('order_lines_id')
                                                        ->whereNull('products_id') //https://github.com/SMEWebify/WebErpMesv2/issues/334
                                                        ->whereNull('sub_assembly_id');
                                        })
                                        ->where('methods_services_id', 'like', '%'.$this->searchIdService.'%')
                                        ->where('status_id', 'like', '%'.$this->searchIdStatus.'%')
                                        ->where('label','like', '%'.$this->search.'%')
                                        ->get();
        }
        else{
            $Tasklist = $this->Tasklist = Task::with('OrderLines.order')
                                        ->where(function ($query) { //https://github.com/SMEWebify/WebErpMesv2/issues/334
                                            $query->whereNotNull('sub_assembly_id') // Tasks with non-null sub_assembly_id
                                                ->WhereHas('SubAssembly', function ($query) {
                                                    $query->whereNotNull('order_lines_id'); // Subassemblies with non-null order_lines_id
                                                });
                                        })
                                        ->orwhereNotNull('order_lines_id') // Tasks with non-null order_lines_id
                                        ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                        ->where('methods_services_id', 'like', '%'.$this->searchIdService.'%')
                                        ->where('status_id', 'like', '%'.$this->searchIdStatus.'%')
                                        ->where('label','like', '%'.$this->search.'%')
                                        ->get();
        }
        return view('livewire.task-lines', [
            'Tasklist' => $Tasklist,
            'ServicesSelect' => $ServicesSelect,
            'StatusSelect' => $StatusSelect,
        ]);
    }
}
