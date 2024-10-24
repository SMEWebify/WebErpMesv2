<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Models\Purchases\PurchaseLines;
use App\Services\AccountingEntryService;
use App\Models\Purchases\PurchaseInvoice;
use App\Models\Purchases\PurchaseInvoiceLines;
use App\Models\Purchases\PurchaseReceiptLines;


class PurchasesWaintingInvoice extends Component
{
    public $companies_id = '';
    public $sortField = 'id'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $label;
    public $LastInvoice;
    public $document_type = 'PU-IN';

    public $PurchasesWaintingInvoiceLineslist;
    public $code, $user_id; 
    public $updateLines = false;
    public $CompanieSelect = [];
    public $data = [];

    protected $accountingEntryService;

    public function __construct()
    {
        $this->accountingEntryService = App::make(AccountingEntryService::class);
    }

    // Validation Rules
    protected function rules()
    { 
        return [
            'code' =>'required|unique:purchase_invoices',
            'companies_id'=>'required',
            'user_id'=>'required',
        ];
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortAsc = !$this->sortAsc; 
        } else {
            $this->sortAsc = true; 
        }
        $this->sortField = $field;
    }

    /**
     * Generate invoice code and label.
     *
     * @return void
     */
    private function generateInvoiceCodeAndLabel()
    {
        if ($this->LastInvoice === null) {
            $this->LastInvoice = 0;
            $this->code = $this->document_type . "-0";
            $this->label = $this->document_type . "-0";
        } else {
            $this->LastInvoice = $this->LastInvoice->id;
            $this->code = $this->document_type . "-" . $this->LastInvoice;
            $this->label = $this->document_type . "-" . $this->LastInvoice;
        }
    }

    public function mount() 
    {
        $this->user_id = Auth::id();
        $this->LastInvoice = PurchaseInvoice::latest()->first();
        $this->generateInvoiceCodeAndLabel();

        $this->CompanieSelect = Companies::select('id', 'code','client_type','civility','label','last_name')->where('statu_supplier', '=', 2)->orderBy('code')->get();
    }

    
    public function render()
    {
        $userSelect = User::select('id', 'name')->get();
        //Select task where statu is open and only purchase type
        $PurchasesWaintingInvoiceLineslist = $this->PurchasesWaintingInvoiceLineslist = PurchaseReceiptLines::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                                        ->whereHas('purchaseLines', function($query) {
                                                            $query->whereColumn('invoiced_qty', '<', 'qty'); // Comparer receipt_qty avec qty
                                                        })
                                                        ->get();

        return view('livewire.purchases-wainting-invoice', [
            'PurchasesWaintingInvoiceLineslist' => $PurchasesWaintingInvoiceLineslist,
            'userSelect' => $userSelect,
        ]);
    }

    public function storeInvoice()
    {
        // Check rules
        $this->validate();

        // Check if any delivery line exists
        if ($this->linesExist()) {
            
            // Create purchase invoice
            $InvoiceCreated = $this->createInvoice();

            // Create invoice lines
            $this->createInvoiceLines($InvoiceCreated);

            return redirect()->route('purchase.invoices.show', ['id' => $InvoiceCreated->id])->with('success', 'Successfully created new purchase invoice');
        
        }
        else {
            return redirect()->route('invoices-request')->with('error', 'No lines selected');
        }
    }

    /**
     * Check if lines exist.
     *
     * @return bool
     */
    private function linesExist()
    {
        foreach ($this->data as $key => $item) {
            if (array_key_exists("purchase_receipt_line_id", $item) && $item['purchase_receipt_line_id'] != false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Create a new purchase invoice.
     *
     * @return \App\Models\Purchases\PurchaseInvoice
     */
    private function createInvoice()
    {
        return PurchaseInvoice::create([
            'code' => $this->code,
            'label' => $this->label,
            'companies_id' => $this->companies_id,
            'user_id' => $this->user_id,
        ]);
    }

    /**
     * Create invoice lines.
     *
     * @param \App\Models\Purchases\PurchaseInvoice $InvoiceCreated
     * @return void
     */
    private function createInvoiceLines($InvoiceCreated)
    {
        foreach ($this->data as $key => $item) {
            $PurchaseReceiptLine = PurchaseReceiptLines::find($key);

            $allocationId = $this->accountingEntryService->getAllocationId(2, $PurchaseReceiptLine->purchaseLines->accounting_vats_id);

            // Create invoice line
            $PurchaseInvoiceLines = PurchaseInvoiceLines::create([
                'purchase_invoice_id' => $InvoiceCreated->id,
                'purchase_receipt_line_id' => $PurchaseReceiptLine->id,
                'purchase_line_id' => $PurchaseReceiptLine->purchase_line_id,
                'accounting_allocation_id' => $allocationId,
            ]);

            if($allocationId != null){
                // Créer une entrée comptable pour cette ligne de facture
                $this->accountingEntryService->createPurchaseEntry($PurchaseInvoiceLines);
            }

            // Update delivery line status
            $this->updatePurchaseLineStatus($PurchaseReceiptLine);

        }
    }

    private function updatePurchaseLineStatus($PurchaseReceiptLine)
    {
        // Update status line of purchase order line
        PurchaseLines::where('id', $PurchaseReceiptLine->purchase_line_id)->update(['invoiced_qty' => $PurchaseReceiptLine->receipt_qty]);
        
    }
}
