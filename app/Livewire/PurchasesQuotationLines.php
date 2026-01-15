<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Admin\Factory;
use App\Models\Products\Products;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Purchases\PurchaseQuotationLines;

class PurchasesQuotationLines extends Component
{
    public $purchaseQuotationId;
    public $purchase_quotation_id;
    public $purchase_quotation_line_id;
    public $updateLines = false;
    public $ordre = 10;
    public $line_label = '';
    public $qty_to_order = 0;
    public $unit_price = 0;
    public $code = '';
    public $product_id;
    public $OrderStatu;
    public $Factory;
    public $ProductsSelect = [];

    public function mount($purchaseQuotationId)
    {
        $this->purchaseQuotationId = $purchaseQuotationId;
        $quotation = PurchasesQuotation::select('id', 'statu')->findOrFail($purchaseQuotationId);
        $this->purchase_quotation_id = $quotation->id;
        $this->OrderStatu = $quotation->statu;
        $this->ordre = $this->getNextQuotationLineOrder($quotation->id);
        $this->Factory = Factory::first();
        $this->ProductsSelect = Products::select('id', 'label', 'code')
            ->orderBy('code')
            ->get();
    }

    public function render()
    {
        $purchaseQuotation = PurchasesQuotation::with([
            'PurchaseQuotationLines.tasks.OrderLines.order',
            'PurchaseQuotationLines.tasks.Component',
            'PurchaseQuotationLines.product',
        ])->findOrFail($this->purchaseQuotationId);

        return view('livewire.purchases-quotation-lines', [
            'PurchaseQuotation' => $purchaseQuotation,
        ]);
    }

    public function storePurchaseQuotationLine()
    {
        $this->validate([
            'purchase_quotation_id' => 'required|numeric',
            'ordre' => 'required|numeric|gt:0',
            'line_label' => 'required|string',
            'qty_to_order' => 'required|numeric|gt:0',
            'unit_price' => 'required|numeric|min:0',
            'product_id' => 'nullable|numeric',
            'code' => 'nullable|string',
        ]);

        PurchaseQuotationLines::create([
            'purchases_quotation_id' => $this->purchase_quotation_id,
            'tasks_id' => 0,
            'code' => $this->code,
            'product_id' => $this->product_id,
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
        $this->code = $line->code;
        $this->product_id = $line->product_id;
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
            'product_id' => 'nullable|numeric',
            'code' => 'nullable|string',
        ]);

        PurchaseQuotationLines::find($this->purchase_quotation_line_id)->fill([
            'ordre' => $this->ordre,
            'label' => $this->line_label,
            'qty_to_order' => $this->qty_to_order,
            'unit_price' => $this->unit_price,
            'total_price' => $this->unit_price * $this->qty_to_order,
            'code' => $this->code,
            'product_id' => $this->product_id,
        ])->save();

        session()->flash('success', 'Line Updated Successfully');
        $this->resetLineFields();
        $this->updateLines = false;
    }

    private function resetLineFields()
    {
        $this->ordre = $this->getNextQuotationLineOrder($this->purchase_quotation_id);
        $this->line_label = '';
        $this->qty_to_order = 0;
        $this->unit_price = 0;
        $this->code = '';
        $this->product_id = null;
    }

    public function ChangeCodelabel()
    {
        $product = Products::select('id', 'label', 'code', 'selling_price')
            ->where('id', $this->product_id)
            ->first();

        if ($product) {
            $this->code = $product->code;
            $this->line_label = $product->label;
            $this->unit_price = $product->selling_price;
            return;
        }

        $this->code = '';
        $this->line_label = '';
        $this->unit_price = 0;
    }

    private function getNextQuotationLineOrder($quotationId)
    {
        $lastOrder = PurchaseQuotationLines::where('purchases_quotation_id', $quotationId)->max('ordre');
        return $lastOrder ? $lastOrder + 10 : 10;
    }
}
