<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Purchases\PurchaseQuotationLines;

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

    public $purchase_quotation_id;
    public $purchase_quotation_line_id;
    public $updateLines = false;
    public $ordre = 10;
    public $line_label = '';
    public $qty_to_order = 0;
    public $unit_price = 0;
    public $OrderStatu;
    public $PurchaseQuotationOptions = [];

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

    public function updatedPurchaseQuotationId($value)
    {
        if (!$value) {
            $this->OrderStatu = null;
            return;
        }

        $quotation = PurchasesQuotation::select('id', 'statu')->find($value);
        $this->OrderStatu = $quotation?->statu;
        $this->ordre = $this->getNextQuotationLineOrder($value);
    }

    public function mount()
    {
        // Initialization logic can be added here if needed
    }

    public function render()
    {
        $purchasesQuotations = $this->getPurchasesQuotations();
        $this->PurchaseQuotationOptions = PurchasesQuotation::select('id', 'code', 'label', 'statu')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.purchases-quotation-index', [
            'PurchasesQuotationList' => $purchasesQuotations,
        ]);
    }

    private function getPurchasesQuotations()
    {
        return PurchasesQuotation::with(['companie', 'rfqGroup'])
                                ->withCount('PurchaseQuotationLines')
                                ->where('label', 'like', '%' . $this->search . '%')
                                ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                ->paginate(15);
    }

    private function getNextQuotationLineOrder($quotationId)
    {
        $lastOrder = PurchaseQuotationLines::where('purchases_quotation_id', $quotationId)->max('ordre');
        return $lastOrder ? $lastOrder + 10 : 10;
    }

    public function storePurchaseQuotationLine()
    {
        $this->validate([
            'purchase_quotation_id' => 'required|numeric',
            'ordre' => 'required|numeric|gt:0',
            'line_label' => 'required|string',
            'qty_to_order' => 'required|numeric|gt:0',
            'unit_price' => 'required|numeric|min:0',
        ]);

        PurchaseQuotationLines::create([
            'purchases_quotation_id' => $this->purchase_quotation_id,
            'tasks_id' => 0,
            'label' => $this->line_label,
            'ordre' => $this->ordre,
            'qty_to_order' => $this->qty_to_order,
            'unit_price' => $this->unit_price,
            'total_price' => $this->unit_price * $this->qty_to_order,
        ]);

        session()->flash('success', 'Line added Successfully');
        $this->resetLineFields();
    }

    public function editPurchaseQuotationLine($id)
    {
        $line = PurchaseQuotationLines::findOrFail($id);
        $this->purchase_quotation_line_id = $id;
        $this->purchase_quotation_id = $line->purchases_quotation_id;
        $this->ordre = $line->ordre;
        $this->line_label = $line->label;
        $this->qty_to_order = $line->qty_to_order;
        $this->unit_price = $line->unit_price;
        $this->updateLines = true;
    }

    public function updatePurchaseQuotationLine()
    {
        $this->validate([
            'purchase_quotation_id' => 'required|numeric',
            'ordre' => 'required|numeric|gt:0',
            'line_label' => 'required|string',
            'qty_to_order' => 'required|numeric|gt:0',
            'unit_price' => 'required|numeric|min:0',
        ]);

        PurchaseQuotationLines::find($this->purchase_quotation_line_id)->fill([
            'ordre' => $this->ordre,
            'label' => $this->line_label,
            'qty_to_order' => $this->qty_to_order,
            'unit_price' => $this->unit_price,
            'total_price' => $this->unit_price * $this->qty_to_order,
        ])->save();

        session()->flash('success', 'Line Updated Successfully');
        $this->resetLineFields();
        $this->updateLines = false;
    }

    private function resetLineFields()
    {
        if ($this->purchase_quotation_id) {
            $this->ordre = $this->getNextQuotationLineOrder($this->purchase_quotation_id);
        } else {
            $this->ordre = 10;
        }
        $this->line_label = '';
        $this->qty_to_order = 0;
        $this->unit_price = 0;
    }
}
