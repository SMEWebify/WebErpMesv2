<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;

class PurchasesIndex extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'created_at'; // default sorting field
    public $sortAsc = false; // default sort direction
    public $searchIdStatus = '';

    public $code; 
    public $label; 
    public $customer_reference;
    public $companies_id; 
    public $companies_contacts_id;   
    public $companies_addresses_id; 
    public $statu; 
    public $user_id;   
    public $comment;

    public $idCompanie = '';

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

    }

    public function render()
    {
        if(is_numeric($this->idCompanie)){
            $Purchases = Purchases::withCount('PurchaseLines')
                            ->where('companies_id', $this->idCompanie)
                            ->where('label','like', '%'.$this->search.'%')
                            ->where('statu', 'like', '%'.$this->searchIdStatus.'%')
                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                            ->paginate(15);
        }
        else{
            $Purchases = Purchases::withCount('PurchaseLines')
                ->where('label','like', '%'.$this->search.'%')
                ->where('statu', 'like', '%'.$this->searchIdStatus.'%')
                ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                ->paginate(15);
        }

        return view('livewire.purchases-index', [
            'PurchasesList' => $Purchases,
        ]);
    }
}
