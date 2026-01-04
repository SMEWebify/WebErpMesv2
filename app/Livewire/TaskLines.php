<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsRessources;

class TaskLines extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $searchIdService = '';
    public $searchIdRessource = '';
    public $sortField = 'end_date'; // default sorting field
    public $sortAsc = true; // default sort direction
    public $ShowGenericTask = false;
    public $selectedStatuses = [];

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
         // Initialization in the mount method
         $this->Tasklist = Task::with('OrderLines.order')
                            ->where(function ($query) {
                                $query->whereNotNull('sub_assembly_id')
                                    ->whereHas('SubAssembly', function ($query) {
                                        $query->whereNotNull('order_lines_id');
                                    });
                            })
                            ->orWhere(function ($query) {
                                $query->whereNotNull('order_lines_id');
                            })
                            ->get();

        $this->selectedStatuses = Status::where('title', '!=', 'Finished')
                                        ->pluck('id')
                                        ->toArray();

        if(empty($this->selectedStatuses)){
            $this->selectedStatuses = Status::pluck('id')->toArray();
        }
    }

    public function render()
    {
        
        $ServicesSelect = MethodsServices::select('id', 'label')->orderBy('ordre')->get();
        $StatusSelect = Status::orderBy('order', 'ASC')->get();
        $RessourceSelect = MethodsRessources::select('id', 'label')->orderBy('label')->get();

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
                                        ->where('label','like', '%'.$this->search.'%')
                                        ->when($this->searchIdRessource, function($query){
                                            $query->whereHas('resources', fn($q) => $q->where('methods_ressources.id', $this->searchIdRessource));
                                        })
                                        ->when($this->selectedStatuses, function($query){
                                            $query->whereIn('status_id', $this->selectedStatuses);
                                        })
                                        ->get();
        }
        else{
            $Tasklist = $this->Tasklist = Task::with('OrderLines.order')
                            ->where(function ($query) {
                                $query->where(function ($query) {
                                    $query->whereNotNull('sub_assembly_id')
                                            ->whereHas('SubAssembly', function ($query) {
                                                $query->whereNotNull('order_lines_id');
                                            });
                                })
                                ->orWhereNotNull('order_lines_id'); // Combine 'or' within the first 'where'
                            })
                            ->where('methods_services_id', 'like', '%'.$this->searchIdService.'%')
                            ->where('label', 'like', '%'.$this->search.'%')
                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                            ->when($this->searchIdRessource, function($query){
                                $query->whereHas('resources', fn($q) => $q->where('methods_ressources.id', $this->searchIdRessource));
                            })
                            ->when($this->selectedStatuses, function($query){
                                $query->whereIn('status_id', $this->selectedStatuses);
                            })
                            ->get();

        }

        return view('livewire.task-lines', [
            'Tasklist' => $Tasklist,
            'ServicesSelect' => $ServicesSelect,
            'StatusSelect' => $StatusSelect,
            'RessourceSelect' => $RessourceSelect,
        ]);
    }
}
