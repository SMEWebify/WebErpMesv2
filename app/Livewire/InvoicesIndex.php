<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Workflow\Invoices;

class InvoicesIndex extends Component
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
    public $invoice_type;
    public $accounting_status;
    public $user_id;
    public $bank_id ;
    public $order_id;  
    public $comment;

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
        $Invoices = Invoices::withCount('InvoiceLines')
                                ->where('label','like', '%'.$this->search.'%')
                                ->where('statu', 'like', '%'.$this->searchIdStatus.'%')
                                ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                ->paginate(15);

        return view('livewire.invoices-index', [
            'InvoicesList' => $Invoices,
        ]);
    }
}
