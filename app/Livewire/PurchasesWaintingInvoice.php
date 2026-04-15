<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\SelectDataService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Models\Purchases\PurchaseLines;
use App\Services\DocumentCodeGenerator;
use App\Services\AccountingEntryService;
use App\Services\PurchaseInvoiceService;
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

    public $code, $user_id; 
    public $updateLines = false;
    public $CompanieSelect = [];
    public $data = [];

    protected $accountingEntryService;
    protected $documentCodeGenerator;
    protected $SelectDataService;
    protected $purchaseInvoiceService;

    public function __construct()
    {
        // Resolve the service via the Laravel container
        $this->accountingEntryService = App::make(AccountingEntryService::class);
        $this->documentCodeGenerator = App::make(DocumentCodeGenerator::class);
        $this->SelectDataService = App::make(SelectDataService::class);
        $this->purchaseInvoiceService = App::make(PurchaseInvoiceService::class);
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

    public function mount() 
    {
        $this->user_id = Auth::id();
        $this->LastInvoice = PurchaseInvoice::latest()->first();
        $purchaseInvoicetId = $this->LastInvoice ? $this->LastInvoice->id : 0;
        $this->code = $this->documentCodeGenerator->generateDocumentCode('purchase-invoice', $purchaseInvoicetId);
        $this->label = $this->code;
    }

    
    public function render()
    {
        $userSelect = $this->SelectDataService->getUsers();

        $companyIdsInRecieptLines = $this->purchaseInvoiceService->getUniqueCompanyIdsWithOpenPurchaseReceiptLines();

        $this->CompanieSelect = $this->SelectDataService->getSupplier($companyIdsInRecieptLines); 

        $PurchasesWaintingInvoiceLineslist = $this->purchaseInvoiceService
        ->getPurchasesWaintingInvoiceLines($this->companies_id, $this->sortField, $this->sortAsc);

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
            return redirect()->route('purchases.wainting.invoice')->with('error', 'No lines selected');
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
            $accountingType = 2;
            if($PurchaseReceiptLine-> purchaseLines->tasks_id == 0){
                $accountingType = 5;
            }
            elseif($PurchaseReceiptLine-> purchaseLines->tasks->OrderLines->order->type == 2){
                $accountingType = 5;
            }

            $allocationId = $this->accountingEntryService->getAllocationId($accountingType, $PurchaseReceiptLine->purchaseLines->accounting_vats_id);

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
        // Accumulate invoiced qty (supports multiple partial receipts on the same purchase line)
        PurchaseLines::where('id', $PurchaseReceiptLine->purchase_line_id)->increment('invoiced_qty', $PurchaseReceiptLine->receipt_qty);
        
    }
}
