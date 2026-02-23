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
    public $selectedOrderRef = '';

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

    public function filterByOrderRef(string $orderRef): void
    {
        $this->selectedOrderRef = $orderRef;
    }

    public function clearOrderRefFilter(): void
    {
        $this->selectedOrderRef = '';
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

        $taskQuery = $this->buildTaskQuery();

        $availableOrderRefs = (clone $taskQuery)
            ->get()
            ->map(function ($task) {
                if ($task->SubAssembly && $task->SubAssembly->order_lines_id) {
                    return $task->SubAssembly->OrderLines->order->code ?? null;
                }

                return $task->OrderLines->order->code ?? null;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($this->selectedOrderRef) {
            $taskQuery->where(function ($query) {
                $query->whereHas('OrderLines.order', function ($orderQuery) {
                    $orderQuery->where('code', $this->selectedOrderRef);
                })->orWhereHas('SubAssembly.OrderLines.order', function ($orderQuery) {
                    $orderQuery->where('code', $this->selectedOrderRef);
                });
            });
        }

        $Tasklist = $this->Tasklist = $taskQuery
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
            ->get();

        return view('livewire.task-lines', [
            'Tasklist' => $Tasklist,
            'ServicesSelect' => $ServicesSelect,
            'StatusSelect' => $StatusSelect,
            'RessourceSelect' => $RessourceSelect,
            'availableOrderRefs' => $availableOrderRefs,
        ]);
    }

    private function buildTaskQuery()
    {
        $query = Task::with(['OrderLines.order', 'SubAssembly.OrderLines.order']);

        if ($this->ShowGenericTask) {
            $query->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereNotNull('sub_assembly_id')
                        ->whereHas('SubAssembly', function ($query) {
                            $query->whereNotNull('order_lines_id');
                        });
                })
                ->orWhereNotNull('order_lines_id')
                ->orWhere(function ($query) {
                    $query->whereNull('quote_lines_id')
                        ->whereNull('order_lines_id')
                        ->whereNull('products_id')
                        ->whereNull('sub_assembly_id');
                });
            });
        } else {
            $query->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereNotNull('sub_assembly_id')
                        ->whereHas('SubAssembly', function ($query) {
                            $query->whereNotNull('order_lines_id');
                        });
                })
                ->orWhereNotNull('order_lines_id');
            });
        }

        return $query
            ->where('methods_services_id', 'like', '%'.$this->searchIdService.'%')
            ->where('label', 'like', '%'.$this->search.'%')
            ->when($this->searchIdRessource, function ($query) {
                $query->whereHas('resources', fn ($q) => $q->where('methods_ressources.id', $this->searchIdRessource));
            })
            ->when($this->selectedStatuses, function ($query) {
                $query->whereIn('status_id', $this->selectedStatuses);
            });
    }
}
