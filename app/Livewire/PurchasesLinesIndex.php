<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Purchases\PurchaseLines;

class PurchasesLinesIndex extends Component
{

    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    
    public $search = '';
    public $sortField = 'label'; // default sorting field
    public $sortAsc = true; // default sort direction
    public $product_id = '';
    public $purchase_id = '';

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

    public function render()
    {
        if(is_numeric($this->product_id)){
            $PurchaseLines = PurchaseLines::where('product_id', $this->product_id)
                                    ->where('label','like', '%'.$this->search.'%')
                                    ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                    ->paginate(15);
        
        }
        else{
            $PurchaseLines = PurchaseLines::where('purchases_id', $this->purchase_id)
                                            ->where('label','like', '%'.$this->search.'%')
                                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->paginate(15);
        }
        
        return view('livewire.purchases-lines-index',[
            'PurchasesLineslist' => $PurchaseLines,
        ]);
    }
}
