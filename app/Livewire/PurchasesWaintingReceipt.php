<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\TaskService;
use App\Services\SelectDataService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Services\DocumentCodeGenerator;
use App\Services\PurchaseReceiptService;
use App\Models\Purchases\PurchaseReceipt;

class PurchasesWaintingReceipt extends Component
{
    public $companies_id = '';
    public $sortField = 'id'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $LastReceipt= null;
    public $document_type = 'RC';
    public $deliveryNoteNumber;


    public $code, $label, $user_id; 
    public $updateLines = false;
    public $CompanieSelect = [];
    public $data = [];
    public $qty = [];
    protected $taskService;
    protected $purchaseReceiptService;
    protected $documentCodeGenerator;
    protected $SelectDataService;

    public function __construct()
    {
        // Resolve the service via the Laravel container
        $this->taskService = App::make(TaskService::class);
        $this->purchaseReceiptService = App::make(PurchaseReceiptService::class);
        $this->documentCodeGenerator = App::make(DocumentCodeGenerator::class);
        $this->SelectDataService = App::make(SelectDataService::class);
    }

    // Validation Rules
    protected function rules()
    { 
        return [
            'code' =>'required|unique:purchase_receipts',
            'label' =>'required',
            'companies_id'=>'required',
            'user_id'=>'required',
            'data.*.accepted_qty' => 'nullable|integer|min:0',
            'data.*.rejected_qty' => 'nullable|integer|min:0',
            'data.*.inspection_result' => 'nullable|string|max:255',
            'data.*.inspection_date' => 'nullable|date',
            'data.*.inspected_by' => 'nullable|exists:users,id',
            'data.*.quality_non_conformity_id' => 'nullable|exists:quality_non_conformities,id',
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
        // get last id
        $this->LastReceipt =  PurchaseReceipt::latest()->first();
        $purchaseReceiptId = $this->LastReceipt ? $this->LastReceipt->id : 0;
        $this->code = $this->documentCodeGenerator->generateDocumentCode('purchase-receipt', $purchaseReceiptId);
        $this->label = $this->code;

    }

    public function render()
    {
        $userSelect = $this->SelectDataService->getUsers();

        $companyIdsInPurchaseLines = $this->purchaseReceiptService->getUniqueCompanyIdsWithOpenPurchaseLines();

        $this->CompanieSelect = $this->SelectDataService->getSupplier($companyIdsInPurchaseLines); 

        $PurchasesWaintingReceiptLineslist = $this->purchaseReceiptService
        ->getPurchasesWaintingReceiptLines($this->companies_id, $this->sortField, $this->sortAsc);

        return view('livewire.purchases-wainting-receipt', [
            'PurchasesWaintingReceiptLineslist' => $PurchasesWaintingReceiptLineslist,
            'userSelect' => $userSelect,
        ]);
    }

    public function storeReciep()
    {
        $this->validate();

        try {
            // Données du reçu d'achat
            $receiptData = [
                'code' => $this->code,
                'label' => $this->label,
                'companies_id' => $this->companies_id,
                'delivery_note_number' => $this->deliveryNoteNumber,
                'user_id' => $this->user_id,
            ];

            // Appel au service pour la création du reçu
            $ReceiptCreated = $this->purchaseReceiptService->createPurchaseReceipt($this->data, $receiptData);

            return redirect()->route('purchase.receipts.show', ['id' => $ReceiptCreated->id])
                ->with('success', 'Successfully created new receipt');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function storeEmptyReceipt()
    {
        $this->validate();

        try {
            $receiptData = [
                'code' => $this->code,
                'label' => $this->label,
                'companies_id' => $this->companies_id,
                'delivery_note_number' => $this->deliveryNoteNumber,
                'user_id' => $this->user_id,
            ];

            $receiptCreated = $this->purchaseReceiptService->createEmptyPurchaseReceipt($receiptData);

            return redirect()->route('purchase.receipts.show', ['id' => $receiptCreated->id])
                ->with('success', 'Successfully created new receipt');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
