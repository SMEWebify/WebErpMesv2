<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Products\SerialNumbers;

class SerialNumbersIndex extends Component
{

    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    
    public $search = '';
    public $sortField = 'serial_number'; // default sorting field
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
            $SerialNumbers = SerialNumbers::where('products_id', $this->product_id)
                                    ->where('serial_number','like', '%'.$this->search.'%')
                                    ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                    ->paginate(15);
        
        }
        else{
            $SerialNumbers = SerialNumbers::where('serial_number','like', '%'.$this->search.'%')
                                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                            ->paginate(15);
        }
        
        return view('livewire.serial-numbers-index',[
            'SerialNumberslist' => $SerialNumbers,
        ]);
    }
}