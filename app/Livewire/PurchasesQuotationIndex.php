<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Purchases\PurchasesQuotation;

class PurchasesQuotationIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'created_at'; // default sorting field
    public $sortAsc = false; // default sort direction

    public $code;
    public $label;
    public $customer_reference;
    public $companies_id;
    public $companies_contacts_id;
    public $companies_addresses_id;
    public $statu;
    public $user_id;
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
        // Initialization logic can be added here if needed
    }

    public function render()
    {
        $purchasesQuotations = $this->getPurchasesQuotations();

        return view('livewire.purchases-quotation-index', [
            'PurchasesQuotationList' => $purchasesQuotations,
        ]);
    }

    private function getPurchasesQuotations()
    {
        return PurchasesQuotation::withCount('PurchaseQuotationLines')
                                ->where('label', 'like', '%' . $this->search . '%')
                                ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                ->paginate(15);
    }
}
