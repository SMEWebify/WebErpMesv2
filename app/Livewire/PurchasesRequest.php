<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Services\SelectDataService;
use Illuminate\Support\Facades\App;
use Carbon\Carbon;
use App\Services\PurchaseOrderService;
use App\Services\PurchaseQuotationService;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;

class PurchasesRequest extends Component
{
    // Properties for managing state
    public $companies_id = '';
    public $selected_companies = [];
    public $sortField = 'tasks.id'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $LastPurchase = null;
    public $LastPurchaseQuotation = null;
    public $document_type = 'PU';
    public $document_type_label = 'PU';

    public $PurchasesRequestsLineslist;
    public $code, $label; 
    public $updateLines = false;
    public $CompanieSelect = [];
    public $data = [];
    public $qty = [];

    // Services for handling purchase orders and quotations
    protected $purchaseOrderService;
    protected $purchaseQuotationService;
    protected $SelectDataService;
    
    public function __construct()
    {
        // Resolve the service via the Laravel container
        $this->purchaseOrderService = App::make(PurchaseOrderService::class);
        $this->purchaseQuotationService = App::make(PurchaseQuotationService::class);
        $this->SelectDataService = App::make(SelectDataService::class);
    }

    // Validation Rules
    protected function rules()
    { 
        if($this->document_type == 'PU'){  
            return [
                'code' =>'required|unique:purchases',
                'label' =>'required',
                'companies_id'=>'required',
            ];
        }
        elseif($this->document_type == 'PQ'){
            return [
                'code' =>'required|unique:purchase_rfq_groups,code',
                'label' =>'required',
                'selected_companies' => 'required|array|min:1',
            ];
        }
    }

    public function mount() 
    {
        // Get the last purchase and quotation codes
        $this->LastPurchase = $this->purchaseOrderService->generatePurchaseCode();
        $this->LastPurchaseQuotation = $this->purchaseQuotationService->generatePurchasesQuotationCode();
        $this->changeDocument();
    }

    public function updatedDocumentType()
    {
        $this->changeDocument();
    }
    
    public function sortBy($field)
    {
        // Toggle sorting direction if the same field is sorted, otherwise set to ascending
        if ($this->sortField === $field) {
            $this->sortAsc = !$this->sortAsc; 
        } else {
            $this->sortAsc = true; 
        }
        $this->sortField = $field;
    }
    
    public function changeDocument() 
    {
        // Change the document code and label based on the document type
        if($this->document_type == 'PU'){ 
            $this->code =  $this->LastPurchase;
            $this->label =  $this->LastPurchase;
        }
        elseif($this->document_type == 'PQ'){
            $this->code =  $this->LastPurchaseQuotation;
            $this->label =  $this->LastPurchaseQuotation;
        }
        else{
            session()->flash('error', 'Please select on type of document.');
        }
    }

    public function render()
    {
        // Get the list of users and the first status
        $userSelect = $this->SelectDataService->getUsers();

        $this->CompanieSelect = $this->SelectDataService->getSupplier(); 
        
        $Status = Status::select('id')->orderBy('order')->first();
        //Select task where statu is open and only purchase type
        $PurchasesRequestsLineslist = $this->PurchasesRequestsLineslist = Task::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                                                        ->where('status_id', '=', $Status->id)
                                                                        ->whereNotNull('order_lines_id')
                                                                        ->where(
                                                                            function($query) {
                                                                                return $query
                                                                                    ->Where('type', '=', '2')
                                                                                    ->orWhere('type', '=', '3')
                                                                                    ->orWhere('type', '=', '4')
                                                                                    ->orWhere('type', '=', '5')
                                                                                    ->orWhere('type', '=', '6')
                                                                                    ->orWhere('type', '=', '7');
                                                                            })
                                                                            ->when($this->companies_id, function ($query) {
                                                                                return $query->whereHas('Component.preferredSuppliers', function ($supplierQuery) {
                                                                                    $supplierQuery->where('companies_id', $this->companies_id);
                                                                                });
                                                                            })->get();

        $openOrderNotStartedCount = $this->getOpenOrderNotStartedOrderLinesQuery()->count();
        return view('livewire.purchases-request', [
            'PurchasesRequestsLineslist' => $PurchasesRequestsLineslist,
            'userSelect' => $userSelect,
            'openOrderNotStartedCount' => $openOrderNotStartedCount,
        ]);
    }

    public function storePurchase(){
        // Validate the input data
        $this->validate(); 
        
        // Check if any lines are selected
        $taskIds = collect($this->data)->pluck('task_id')->filter()->count();

        // Get default settings for the purchase order or quotation
        // Create puchase order
        if($this->document_type == 'PU'){
            $defaultSettings = [
                'AccountingVat' => $this->purchaseOrderService->getAccountingVat(),
                'defaultAddress' => CompaniesAddresses::getDefault(['companies_id' => $this->companies_id]),
                'defaultContact' => CompaniesContacts::getDefault(['companies_id' => $this->companies_id]),
            ];

            // Check if all default settings are available
            foreach ($defaultSettings as $key => $setting) {
                if (is_null($setting)) {
                    return redirect()->back()->with('error', 'No default settings for ' . str_replace('_', ' ', $key));
                }
                $defaultSettings[$key] = $setting->id;
            }

            // Get the status update for the purchase order
            $statusUpdate = $this->purchaseOrderService->getStatusUpdate();

            if (!$statusUpdate) {
                return redirect()->back()->with('error', 'No status "Supplied" or "In progress" in kanban for defining progress');
            }

            // Create the purchase order
            $PurchaseOrderCreated = $this->purchaseOrderService->createPurchaseOrder($this->companies_id, 
                                                                $this->code , 
                                                                $this->label , 
                                                                $defaultSettings['defaultAddress'],
                                                                $defaultSettings['defaultContact']);

            if (!$PurchaseOrderCreated) {
                return redirect()->back()->withErrors(['msg' => 'Something went wrong (like no default settings for address, contact or accounting vat)']);
            }

            if ($taskIds > 0) {
                // Process the purchase request lines
                $this->purchaseOrderService->processPurchaseRequestLines(
                    $this->data,
                    $PurchaseOrderCreated,
                    $statusUpdate->id
                );
            }

            return redirect()->route('purchases.show', ['id' => $PurchaseOrderCreated->id])
                                ->with('success', 'Successfully created new purchase order');
        }
        // Create purchase quotation
        elseif($this->document_type == 'PQ'){

            // Get the status update for the purchase quotation
            $statusUpdate = $this->purchaseQuotationService->getStatusUpdate();

            if (!$statusUpdate) {
                return redirect()->back()->with('error', 'No status "RFQ in progress" or "Started" in kanban for defining progress');
            }

            $rfqGroup = $this->purchaseQuotationService->createRfqGroup($this->code, $this->label);
            $selectedCompanies = collect($this->selected_companies)->filter()->unique()->values();

            foreach ($selectedCompanies as $companyId) {
                $company = Companies::find($companyId);

                if (!$company) {
                    return redirect()->back()->withErrors(['msg' => 'Supplier not found']);
                }

                $defaultSettings = [
                    'AccountingVat' => $this->purchaseOrderService->getAccountingVat(),
                    'defaultAddress' => CompaniesAddresses::getDefault(['companies_id' => $companyId]),
                    'defaultContact' => CompaniesContacts::getDefault(['companies_id' => $companyId]),
                ];

                foreach ($defaultSettings as $key => $setting) {
                    if (is_null($setting)) {
                        return redirect()->back()->with('error', 'No default settings for ' . str_replace('_', ' ', $key));
                    }
                    $defaultSettings[$key] = $setting->id;
                }

                $quotationCode = $this->purchaseQuotationService->generateGroupedQuotationCode($this->code, $company);
                $quotationLabel = $this->label . ' - ' . $company->label;

                $PurchaseQuotationCreated = $this->purchaseQuotationService->createPurchasesQuotation(
                    $companyId,
                    $quotationCode,
                    $quotationLabel,
                    $defaultSettings['defaultAddress'],
                    $defaultSettings['defaultContact'],
                    $rfqGroup->id
                );

                if (!$PurchaseQuotationCreated) {
                    return redirect()->back()->withErrors(['msg' => 'Something went wrong (like no default settings for address, contact or accounting vat)']);
                }

                if ($taskIds > 0) {
                    $this->purchaseQuotationService->processPurchaseRequestLines(
                        $this->data,
                        $PurchaseQuotationCreated,
                        $statusUpdate->id
                    );
                }
            }

            $firstQuotation = PurchasesQuotation::where('rfq_group_id', $rfqGroup->id)->orderBy('id')->first();

            return redirect()->route('purchases.quotations.show', ['id' => $firstQuotation->id])
                ->with('success', 'Successfully created new purchase quotation');
        }
        else{
            return redirect()->back()->with('error', 'no document type');
        }
    }

    public function exportOpenOrderNotStartedCsv()
    {
        $orderLines = $this->getOpenOrderNotStartedOrderLinesQuery()
                            ->with(['order.companie', 'OrderLineDetails', 'Task.service'])
                            ->get();

        $filename = 'purchase-request-open-orders-not-started-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($orderLines) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ExternalId',
                'OF',
                'Designation',
                'Material',
                'Thickness',
                'Quantity',
                'Orientation',
                'CutDeadline',
                'DeliveryDeadline',
                'SymPath',
                'DxfPath',
                'Client',
                'NextOperation',
            ], ';');

            foreach ($orderLines as $orderLine) {
                $order = $orderLine->order;
                $orderLineDetails = $orderLine->OrderLineDetails;
                $nextTask = $orderLine->Task->first();
                $service = $nextTask?->service;
                $cutDeadline = $nextTask?->due_date ? $nextTask->due_date->format('Y-m-d') : '';
                $deliveryDeadline = $orderLine->delivery_date
                    ? Carbon::parse($orderLine->delivery_date)->format('Y-m-d')
                    : '';

                fputcsv($handle, [
                    $orderLine->id,
                    $order?->code ?? '',
                    $orderLine->label ?? '',
                    $orderLineDetails?->material ?? '',
                    $orderLineDetails?->thickness ?? '',
                    $orderLine->qty,
                    0,
                    $cutDeadline,
                    $deliveryDeadline,
                    $orderLineDetails?->cam_file_path ?? '',
                    $orderLineDetails?->cad_file_path ?? '',
                    $order?->companie?->label ?? '',
                    $service?->label ?? '',
                ], ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function getOpenOrderNotStartedOrderLinesQuery()
    {
        return OrderLines::query()
            ->whereIn('tasks_status', [1, 2])
            ->whereHas('order', function ($query) {
                $query->where('statu', 1);
            })
            ->orderBy('order_lines.id');
    }
}
