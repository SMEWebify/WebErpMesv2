<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\InvoiceService;
use App\Models\Workflow\Invoices;
use App\Events\DeliveryLineUpdated;
use App\Models\Workflow\OrderLines;
use App\Services\SelectDataService;
use Illuminate\Support\Facades\App;
use App\Services\InvoiceDataService;
use App\Services\InvoiceLineService;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\DeliveryLines;
use App\Services\DocumentCodeGenerator;

class InvoicesRequest extends Component
{
    public $sortField = 'id'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $LastInvoice = null;

    public $InvoicesRequestsLineslist;
    public $code, $label, $companies_id, $companies_addresses_id, $companies_contacts_id, $user_id; 
    public $updateLines = false;
    public $CompanieSelect = [];
    public $deliveryDateStart;
    public $deliveryDateEnd;
    public $data = [];
    public $qty = [];
    public $idCompanie = '';
    private $ordre = 10;

    protected $invoiceLineService;
    protected $invoiceService;
    protected $documentCodeGenerator;
    protected $DeliveryDataService;
    protected $SelectDataService;
    protected $InvoiceDataService;

    public function __construct()
    {
        // Resolve the service via the Laravel container
        $this->invoiceLineService = App::make(InvoiceLineService::class);
        $this->invoiceService = App::make(InvoiceService::class);
        $this->documentCodeGenerator = App::make(DocumentCodeGenerator::class);
        $this->SelectDataService = App::make(SelectDataService::class);
        $this->InvoiceDataService = App::make(InvoiceDataService::class);
    }

    // Validation Rules
    protected $rules = [
        'code' =>'required|unique:invoices',
        'label' =>'required',
        'companies_id'=>'required',
        'companies_addresses_id' =>'required',
        'companies_contacts_id' =>'required',
        'user_id'=>'required',
    ];
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
        $this->LastInvoice = Invoices::latest()->first();
    
        $invoiceId = $this->LastInvoice ? $this->LastInvoice->id : 0;
        $this->code = $this->documentCodeGenerator->generateDocumentCode('invoice', $invoiceId);
        $this->label = $this->code;
    }

    public function render()
    {
        $userSelect = $this->SelectDataService->getUsers();

        // Get the unique IDs of the companies in the DeliveryLines list
        $companyIdsInDeliveryLines = $this->InvoiceDataService->getUniqueCompanyIdsWithOpenInvoiceLines();

        // Filter companies based on unique IDs
        $this->CompanieSelect = $this->SelectDataService->getCompanies($companyIdsInDeliveryLines);
        
        $AddressSelect = $this->SelectDataService->getAddress($this->companies_id);
        $ContactSelect = $this->SelectDataService->getContact($this->companies_id);
        
        //Select delevery line where not Partly invoiced or Invoiced
        $this->InvoicesRequestsLineslist = $this->InvoiceDataService
        ->getInvoiceRequestsLines(
            $this->companies_id ? (int) $this->companies_id : null,
            $this->deliveryDateStart,
            $this->deliveryDateEnd,
            $this->sortField,
            $this->sortAsc
        );

        return view('livewire.invoices-request', [
            'InvoicesRequestsLineslist' => $this->InvoicesRequestsLineslist,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'userSelect' => $userSelect,
        ]);
    }

    public function storeInvoice()
    {
        // Validate the request
        $this->validate();

        // Check if any delivery line exists
        if ($this->hasDeliveryLines()) {
            // Create invoice
            $invoiceCreated = $this->createInvoice();

            // Create invoice note lines
            $this->createInvoiceNoteLines($invoiceCreated);

            // Redirect to the newly created invoice
            return redirect()->route('invoices.show', ['id' => $invoiceCreated->id])
                            ->with('success', 'Successfully created new invoice');
        } else {
            return redirect()->route('invoices-request')->with('error', 'No lines selected');
        }
    }

    public function generateInvoicesForCompany()
    {
        $this->validate([
            'companies_id' => 'required',
            'companies_addresses_id' => 'required',
            'companies_contacts_id' => 'required',
            'user_id' => 'required',
        ]);

        $deliveryLines = $this->InvoiceDataService->getInvoiceRequestsLines(
            (int) $this->companies_id,
            $this->deliveryDateStart,
            $this->deliveryDateEnd,
            $this->sortField,
            $this->sortAsc
        );

        if ($deliveryLines->isEmpty()) {
            session()->flash('error', 'No lines to invoice');
            return;
        }

        $deliveryLines
            ->groupBy(fn($line) => $line->orderLine->orders_id)
            ->each(function ($lines) {
                $this->ordre = 10;
                $invoiceCreated = $this->createInvoiceForCompany();

                foreach ($lines as $deliveryLine) {
                    $this->invoiceLineService->createInvoiceLine(
                        $invoiceCreated,
                        $deliveryLine->order_line_id,
                        $deliveryLine->id,
                        $this->ordre,
                        $deliveryLine->qty,
                        $deliveryLine->OrderLine->accounting_vats_id
                    );

                    $this->updateDeliveryLineStatus($deliveryLine);
                    $this->updateOrderLineInfo($deliveryLine);
                    $this->ordre += 10;
                }
            });

        $this->refreshInvoiceDefaults();
        $this->data = [];

        session()->flash('success', 'Invoices created');
    }

    private function hasDeliveryLines()
    {
        foreach ($this->data as $item) {
            if (array_key_exists('deliveryLine_id', $item) && $item['deliveryLine_id'] !== false) {
                return true;
            }
        }
        return false;
    }

    private function createInvoice()
    {
        return $this->invoiceService->createInvoice($this->code, $this->label, $this->companies_id, $this->companies_addresses_id, $this->companies_contacts_id, $this->user_id);
    }

    private function createInvoiceForCompany()
    {
        $lastInvoice = Invoices::latest()->first();
        $invoiceId = $lastInvoice ? $lastInvoice->id : 0;
        $code = $this->documentCodeGenerator->generateDocumentCode('invoice', $invoiceId);

        return $this->invoiceService->createInvoice(
            $code,
            $code,
            $this->companies_id,
            $this->companies_addresses_id,
            $this->companies_contacts_id,
            $this->user_id
        );
    }

    private function refreshInvoiceDefaults()
    {
        $this->LastInvoice = Invoices::latest()->first();
        $invoiceId = $this->LastInvoice ? $this->LastInvoice->id : 0;
        $this->code = $this->documentCodeGenerator->generateDocumentCode('invoice', $invoiceId);
        $this->label = $this->code;
    }

    private function createInvoiceNoteLines($invoiceCreated)
    {
        foreach ($this->data as $key => $item) {
            if ($this->isDeliveryLineValid($item)) {
                $deliveryLine = DeliveryLines::find($key);

                // Create invoice line
                $this->invoiceLineService->createInvoiceLine($invoiceCreated, $deliveryLine->order_line_id, $deliveryLine->id, $this->ordre, $deliveryLine->qty, $deliveryLine->OrderLine->accounting_vats_id);

                // Update delivery line status
                $this->updateDeliveryLineStatus($deliveryLine);

                // Update order line info
                $this->updateOrderLineInfo($deliveryLine);

                $this->ordre += 10;
            }
        }
    }

    private function isDeliveryLineValid($item)
    {
        return array_key_exists('deliveryLine_id', $item) && $item['deliveryLine_id'] !== false;
    }

    private function updateDeliveryLineStatus($deliveryLine)
    {
        $deliveryLine->invoice_status = 4;
        $deliveryLine->save();
        event(new DeliveryLineUpdated($deliveryLine->id));
    }

    private function updateOrderLineInfo($deliveryLine)
    {
        $orderLine = OrderLines::find($deliveryLine->order_line_id);
        $orderLine->invoiced_qty += $deliveryLine->qty;
        $orderLine->invoiced_remaining_qty -= $deliveryLine->qty;

        if ($orderLine->invoiced_remaining_qty == 0) {
            $orderLine->invoice_status = 3;
        } else {
            $orderLine->invoice_status = 2;
        }

        $orderLine->save();
    }
}
