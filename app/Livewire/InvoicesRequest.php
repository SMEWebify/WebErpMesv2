<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;
use App\Services\InvoiceService;
use App\Models\Workflow\Invoices;
use App\Events\DeliveryLineUpdated;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use Illuminate\Support\Facades\App;
use App\Services\InvoiceLineService;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\DeliveryLines;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;

class InvoicesRequest extends Component
{
    public $sortField = 'id'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $LastInvoice = null;

    public $DeliverysRequestsLineslist;
    public $code, $label, $companies_id, $companies_addresses_id, $companies_contacts_id, $user_id; 
    public $updateLines = false;
    public $CompanieSelect = [];
    public $data = [];
    public $qty = [];
    public $idCompanie = '';
    private $ordre = 10;

    protected $invoiceLineService;
    protected $invoiceService;

    public function __construct()
    {
        // Résoudre le service via le container Laravel
        $this->invoiceLineService = App::make(InvoiceLineService::class);
        $this->invoiceService = App::make(InvoiceService::class);
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
        $this->code = "IN-" . $invoiceId;
        $this->label = $this->code;
    }

    public function render()
    {
        $userSelect = User::select('id', 'name')->get();

        // Get the unique IDs of the companies in the DeliveryLines list
        $companyIdsInDeliveryLines = DeliveryLines::where(function ($query) {
                                                $query->where('delivery_lines.invoice_status', '=', '1')
                                                    ->orWhere('delivery_lines.invoice_status', '=', '2');
                                            })
                                            ->leftJoin('deliverys', 'delivery_lines.deliverys_id', '=', 'deliverys.id')
                                            ->pluck('deliverys.companies_id')
                                            ->unique();

        // Filter companies based on unique IDs
        $this->CompanieSelect = Companies::select('id', 'code','client_type','civility','label','last_name')
                ->whereIn('id', $companyIdsInDeliveryLines)
                ->orderBy('code')
                ->get();

        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->where('companies_id', $this->companies_id)->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->where('companies_id', $this->companies_id)->get();
        
        //Select delevery line where not Partly invoiced or Invoiced
        $InvoicesRequestsLineslist = $this->DeliverysRequestsLineslist = DeliveryLines::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                                                        ->where(
                                                                            function($query) {
                                                                                return $query
                                                                                    ->where('invoice_status', '=', '1')
                                                                                    ->orWhere('invoice_status', '=', '2');
                                                                            })
                                                                        ->whereHas('delivery', function($q){
                                                                            $q->where('companies_id','like', '%'.$this->companies_id.'%');
                                                                        })->get();

        return view('livewire.invoices-request', [
            'InvoicesRequestsLineslist' => $InvoicesRequestsLineslist,
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

    private function createInvoiceNoteLines($invoiceCreated)
    {
        foreach ($this->data as $key => $item) {
            if ($this->isDeliveryLineValid($item)) {
                $deliveryLine = DeliveryLines::find($key);

                // Create invoice line
                $this->invoiceLineService->createInvoiceLine($invoiceCreated, $deliveryLine->order_line_id, $deliveryLine->id, $this->ordre, $deliveryLine->qty);

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
