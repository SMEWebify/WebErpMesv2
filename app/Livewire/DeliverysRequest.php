<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\StockService;
use App\Events\OrderLineUpdated;
use App\Services\DeliveryService;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\OrderLines;
use App\Services\SelectDataService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Services\DeliveryDataService;
use App\Services\DeliveryLineService;
use App\Services\SerialNumberService;
use App\Services\DocumentCodeGenerator;
use App\Models\Products\StockLocationProducts;

class DeliverysRequest extends Component
{
    protected $deliveryService;
    protected $deliveryLineService;
    protected $documentCodeGenerator;
    protected $stockService;
    protected $SerialNumberService;
    protected $SelectDataService;
    protected $DeliveryDataService;
    
    public function __construct()
    {
        // Resolve the service via the Laravel container
        $this->deliveryService = App::make(DeliveryService::class);
        $this->deliveryLineService = App::make(DeliveryLineService::class);
        $this->documentCodeGenerator = App::make(DocumentCodeGenerator::class);
        $this->stockService = App::make(StockService::class);
        $this->SerialNumberService = App::make(SerialNumberService::class); 
        $this->SelectDataService = App::make(SelectDataService::class);
        $this->DeliveryDataService = App::make(DeliveryDataService::class);
    }

    //use WithPagination;
    //protected $paginationTheme = 'bootstrap';

    public $sortField = 'label'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $LastDelivery = null;

    public $DeliverysRequestsLineslist;
    public $code, $label, $companies_id, $companies_addresses_id, $companies_contacts_id, $user_id; 
    public $updateLines = false;
    public $RemoveFromStock = false;
    public $CreateSerialNumber = false;
    public $CompanieSelect = [];
    public $data = [];
    public $qty = [];
    public $selectAll = false;

    private $ordre = 10;

    // Validation Rules
    protected $rules = [
        'code' =>'required|unique:deliverys',
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
        $this->LastDelivery = Deliverys::latest()->first();
        $deliveryId = $this->LastDelivery ? $this->LastDelivery->id : 0;
        $this->code = $this->documentCodeGenerator->generateDocumentCode('delivery', $deliveryId);
        $this->label = $this->code;
    }

    public function updatedCompaniesId()
    {
        $this->selectAll = false;
        $this->data = [];
    }

    public function updatedSelectAll($value)
    {
        $lines = $this->DeliveryDataService->getDeliveryRequestsLines(
            $this->companies_id,
            $this->sortField,
            $this->sortAsc
        );

        foreach ($lines as $line) {
            $this->data[$line->id]['order_line_id'] = $value ? $line->id : false;
        }
    }

    public function render()
    {
        $userSelect = $this->SelectDataService->getUsers();

        // Get the unique IDs of the companies in the order list
        $companyIdsInOrderLines = $this->DeliveryDataService->getUniqueCompanyIdsWithOpenOrderLines();
                                
        // Filter companies based on unique IDs
        $this->CompanieSelect = $this->SelectDataService->getCompanies($companyIdsInOrderLines);
        
        $AddressSelect = $this->SelectDataService->getAddress($this->companies_id);
        $ContactSelect = $this->SelectDataService->getContact($this->companies_id);
        
        //Select order line where not delivered or partialy delivered
        $this->DeliverysRequestsLineslist = $this->DeliveryDataService
        ->getDeliveryRequestsLines($this->companies_id, $this->sortField, $this->sortAsc);
    
        return view('livewire.deliverys-request', [
            'DeliverysRequestsLineslist' => $this->DeliverysRequestsLineslist,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'userSelect' => $userSelect,
        ]);
    }

    public function storeDeliveryNote()
    {
        // Validate the request
        $this->validate();
    
        // Check if any order line exists
        $orderLineExists = $this->checkOrderLineExists();
    
        if ($orderLineExists) {
            // Create delivery note
            $deliveryCreated = $this->createDeliveryNote();
    
            // Create delivery note lines
            $this->createDeliveryNoteLines($deliveryCreated);
    
            // Redirect to the newly created delivery note
            return redirect()->route('deliverys.show', ['id' => $deliveryCreated->id])
                            ->with('success', 'Successfully created new delivery note');
        } else {
            $errors = $this->getErrorBag();
            $errors->add('errors', 'No lines selected');
        }
    }
    
    private function checkOrderLineExists()
    {
        foreach ($this->data as $item) {
            if ($this->isOrderLineValid($item)) {
                return true;
            }
        }
        return false;
    }
    
    private function createDeliveryNote()
    {
        return $this->deliveryService->createDelivery($this->code, $this->label, $this->companies_id, $this->companies_addresses_id, $this->companies_contacts_id, $this->user_id);
    }
    
    private function createDeliveryNoteLines($deliveryCreated)
    {
        foreach ($this->data as $key => $item) {
            if ($this->isOrderLineValid($item)) {
                $this->deliveryLineService->createDeliveryLine($deliveryCreated, $key, $this->ordre, $item['scumQty']);
                $this->updateOrderLine($key, $item['scumQty']);
                $this->handleStockMovement($key, $item['scumQty']);
                $this->ordre += 10;
            }
        }
    }
    
    private function isOrderLineValid($item)
    {
        return array_key_exists('order_line_id', $item) && $item['order_line_id'] !== false && !empty($item['scumQty']);
    }
    
    private function updateOrderLine($orderLineId, $scumQty)
    {
        $orderLine = OrderLines::find($orderLineId);
    
        if ($this->CreateSerialNumber) {
            $this->generateSerialNumbers($orderLine->product_id, $orderLineId, $scumQty);
        }
    
        $orderLine->delivered_qty += $scumQty;
        $orderLine->delivered_remaining_qty -= $scumQty;
    
        if ($orderLine->delivered_remaining_qty == 0) {
            $orderLine->delivery_status = 3;
        } else {
            $orderLine->delivery_status = 2;
        }
    
        $orderLine->save();
        event(new OrderLineUpdated($orderLine->id));
    }
    
    private function generateSerialNumbers($productId, $orderLineId, $scumQty, $batchId = null)
    {
        for ($i = 0; $i < $scumQty; $i++) {
            $this->serialNumberService->createSerialNumber($productId, $orderLineId, 2, $batchId);
        }
    }
    
    private function handleStockMovement($orderLineId, $scumQty)
    {
        $orderLine = OrderLines::find($orderLineId);
        $taskRelation = $orderLine->Task()->get();
    
        if ($this->RemoveFromStock && $orderLine->product_id && $taskRelation->isEmpty()) {
            $quantityRemaining = $scumQty;
            $stockLocationProducts = StockLocationProducts::where('products_id', $orderLine->product_id)->get();
    
            foreach ($stockLocationProducts as $stock) {
                $quantityToWithdraw = min($stock->getCurrentStockMove(), $quantityRemaining);
    
                if ($quantityToWithdraw != 0) {
                    $data = [
                        'user_id' => Auth::id(),
                        'qty' => $this->quantityToWithdraw,
                        'stock_location_products_id' => $this->stock->id,
                        'order_line_id' => $this->orderLine->id,
                        'typ_move' => 9,
                    ];
        
                    $this->stockService->createStockMove($data);
                }
    
                $quantityRemaining -= $quantityToWithdraw;
    
                if ($quantityRemaining <= 0) {
                    break;
                }
            }
        }
    }
}
