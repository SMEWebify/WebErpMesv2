<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Services\StockService;
use App\Models\Planning\Status;
use App\Models\Workflow\Orders;
use App\Events\OrderLineUpdated;
use App\Services\InvoiceService;
use App\Models\Products\Products;
use App\Models\Products\CustomerPriceList;
use App\Models\Workflow\Invoices;
use App\Services\DeliveryService;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\OrderLines;
use Illuminate\Support\Facades\App;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\SubAssembly;
use App\Services\InvoiceLineService;
use Illuminate\Support\Facades\Auth;
use App\Services\DeliveryLineService;
use App\Services\NotificationService;
use App\Services\SerialNumberService;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Services\DocumentCodeGenerator;
use App\Models\Accounting\AccountingVat;
use App\Models\Workflow\OrderLineDetails;
use Illuminate\Support\Number;
use App\Services\QualityNonConformityService;
use App\Models\Products\StockLocationProducts;

class OrderLine extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'ordre'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $OrderId;
    public $OrderStatu;
    public $order_Statu;
    public $OrderType;
    public $status_id;
    public $OrderLineslist;
    public $order_lines_id, $orders_id, $ordre = 1, $product_id, $methods_units_id, $selling_price, $accounting_vats_id, $delivery_date, $statu, $use_calculated_price;
    public $code='';
    public $label='';
    public $qty= 0;
    public $discount= 0;
    public $updateLines = false;
    public $ProductsSelect = [];
    public $UnitsSelect = [];
    public $VATSelect = [];
    public $Factory;
    public $ProductSelect  = [];

    public $customerId;
    public $customerType;
    public $customerDiscount = 0;
    public $customerPriceList = [];
    public $usePriceList = true;
    public $appliedPriceListId = null;
    public $priceSource = null;
    public $priceListToggleKey;

    protected $updatingPriceFromList = false;

    public $data = [];
    public $customRequirements = [];
    public $RemoveFromStock = false;
    public $CreateSerialNumber = false;
    public $selectAllLines = false;
    
    private $deleveryOrdre = 10;
    private $invoiceOrdre = 10;
    protected $deliveryService;
    protected $deliveryLineService;
    protected $invoiceService;
    protected $invoiceLineService;
    protected $notificationService;
    protected $qualityNonConformityService;
    protected $documentCodeGenerator;
    protected $stockService;
    protected $serialNumberService;

    public function __construct()
    {
        // Resolve the service via the Laravel container
        $this->notificationService = App::make(NotificationService::class);
        $this->deliveryService = App::make(DeliveryService::class);
        $this->deliveryLineService = App::make(DeliveryLineService::class);
        $this->invoiceService = App::make(InvoiceService::class);
        $this->invoiceLineService = App::make(InvoiceLineService::class);
        $this->qualityNonConformityService = App::make(QualityNonConformityService::class);
        $this->documentCodeGenerator = App::make(DocumentCodeGenerator::class);
        $this->stockService = App::make(StockService::class);
        $this->serialNumberService = App::make(SerialNumberService::class); 
    }

    // Validation Rules
    protected $rules = [
        'ordre' =>'required|numeric|gt:0',
        'label'=>'required',
        'qty'=>'required|min:1|not_in:0',
        'selling_price'=>'required|numeric|gt:0',
        'discount'=>'required',
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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function ChangeCodelabel()
    {
        $product = Products::select('id', 'label', 'code', 'methods_units_id', 'selling_price')
            ->find($this->product_id);

        if ($product) {
            $this->code = $product->code;
            $this->label = $product->label;
            $this->methods_units_id = $product->methods_units_id;
            $this->selling_price = $product->selling_price;
            $this->resetPricingState();
            $this->loadCustomerPriceList();
            $this->applyCustomerPriceIfEnabled($product);
        } else {
            $this->resetProductSelection();
        }
    }

    
    public function mount($OrderId, $OrderStatu, $OrderDelay, $OrderType)
    {
        $order = Orders::with('companie')->findOrFail($OrderId);

        $this->orders_id = $order->id;
        $this->order_Statu = $OrderStatu;
        $this->delivery_date = $OrderDelay;
        $this->OrderType = $OrderType;
        $this->Factory = Factory::first();
        $this->status_id = Status::select('id')->orderBy('order')->first();
        $this->priceListToggleKey = 'order-' . $order->id;

        if ($order->companie) {
            $this->customerId = $order->companie->id;
            $this->customerType = $order->companie->client_type !== null ? (int) $order->companie->client_type : null;
            $this->customerDiscount = $order->companie->discount ?? 0;
            $this->discount = $this->customerDiscount;
        }
        $this->ProductsSelect = Products::select('id', 'label', 'code')->orderBy('code')->get();
        $this->VATSelect = AccountingVat::select('id', 'label')->orderBy('rate')->get();
        $this->UnitsSelect = MethodsUnits::select('id', 'label', 'code')->orderBy('label')->get();
        $this->ProductSelect = Products::select('id', 'code','label', 'methods_services_id')->get();
        $this->initializeCustomRequirements();
}

    public function render()
    {
        $OrderLineslist = $this->OrderLineslist = Orderlines::with('OrderLineDetails')->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->where('orders_id', '=', $this->orders_id)->where('label','like', '%'.$this->search.'%')->get();

        $this->syncSelectAllState($this->getSelectableLineIds($OrderLineslist));

        foreach ($OrderLineslist as $line) {
            $detail = $line->OrderLineDetails;
            if ($detail && !array_key_exists($detail->id, $this->customRequirements)) {
                $this->customRequirements[$detail->id] = $this->normalizeCustomRequirements($detail->custom_requirements);
            }
        }

        return view('livewire.order-lines', [
            'OrderLineslist' => $OrderLineslist,
        ]);
    }

    public function toggleSelectAllLines(): void
    {
        $shouldSelect = ! $this->selectAllLines;
        $lineIds = $this->getSelectableLineIds();

        if ($shouldSelect) {
            foreach ($lineIds as $lineId) {
                $this->data[$lineId]['order_line_id'] = true;
            }
        } else {
            foreach ($lineIds as $lineId) {
                unset($this->data[$lineId]);
            }
        }

        $this->selectAllLines = $shouldSelect;
    }

    private function syncSelectAllState(array $lineIds): void
    {
        if (empty($lineIds)) {
            $this->selectAllLines = false;
            return;
        }

        $this->selectAllLines = collect($lineIds)->every(function ($lineId) {
            return $this->isLineSelected((int) $lineId);
        });
    }

    private function isLineSelected(int $lineId): bool
    {
        return !empty($this->data[$lineId]['order_line_id']);
    }

    private function getSelectableLineIds($lines = null): array
    {
        if ($this->OrderStatu == 6 || $this->OrderType == 2) {
            return [];
        }

        if ($lines) {
            return $lines->filter(function ($line) {
                return $this->isLineSelectable($line);
            })->pluck('id')->all();
        }

        return OrderLines::where('orders_id', $this->orders_id)
            ->whereNotIn('delivery_status', [3, 4])
            ->pluck('id')
            ->all();
    }

    private function isLineSelectable(OrderLines $line): bool
    {
        if ($this->OrderStatu == 6 || $this->OrderType == 2) {
            return false;
        }

        return !in_array($line->delivery_status, [3, 4], true);
    }

    private function initializeCustomRequirements(): void
    {
        $lineIds = OrderLines::where('orders_id', $this->orders_id)->pluck('id');

        if ($lineIds->isEmpty()) {
            $this->customRequirements = [];
            return;
        }

        OrderLineDetails::whereIn('order_lines_id', $lineIds)
            ->get()
            ->each(function ($detail) {
                $this->customRequirements[$detail->id] = $this->normalizeCustomRequirements($detail->custom_requirements);
            });
    }

    private function normalizeCustomRequirements($requirements): array
    {
        if (!is_array($requirements)) {
            return [];
        }

        return array_values(array_map(function ($requirement) {
            return [
                'label' => $requirement['label'] ?? '',
                'value' => $requirement['value'] ?? '',
            ];
        }, $requirements));
    }

    public function addCustomRequirement($detailId): void
    {
        if (!isset($this->customRequirements[$detailId])) {
            $this->customRequirements[$detailId] = [];
        }

        $this->customRequirements[$detailId][] = ['label' => '', 'value' => ''];
    }

    public function removeCustomRequirement($detailId, $index): void
    {
        if (!isset($this->customRequirements[$detailId][$index])) {
            return;
        }

        unset($this->customRequirements[$detailId][$index]);
        $this->customRequirements[$detailId] = array_values($this->customRequirements[$detailId]);
    }

    public function resetFields(){
        $this->ordre = $this->ordre+1;
        $this->product_id = '';
        $this->qty = 0;
        $this->discount = $this->customerDiscount;
        $this->resetProductSelection();
    }

    public function storeOrderLine(){
        if($this->OrderType == 2){
            $this->validate([
                'product_id' => 'required',
                'ordre' =>'required|numeric|gt:0',
                'label'=>'required',
                'qty'=>'required|min:1|not_in:0',
                'selling_price'=>'required|numeric|gt:0',
                'discount'=>'required',
            ]);

        }
        else{
            $this->validate();
        }

        $AccountingVat = AccountingVat::getDefault(); 
        $MethodsUnits = MethodsUnits::getDefault(); 
        $AccountingVat = ($AccountingVat->id  ?? 0);
        $MethodsUnits = ($MethodsUnits->id  ?? 0);

        if($AccountingVat == 0|| $MethodsUnits == 0 ){
            return redirect()->route('orders.show', ['id' =>  $this->orders_id])->with('error', 'No default settings');
        }

        $date = date_create($this->delivery_date);
        $internalDelay = date_format(date_sub($date , date_interval_create_from_date_string($this->Factory->add_delivery_delay_order. " days")), 'Y-m-d');
        
        // Create Line
        $NewOrderLine = Orderlines::create([
            'orders_id'=>$this->orders_id,
            'ordre'=>$this->ordre,
            'code'=>$this->code,
            'product_id'=>$this->product_id,
            'label'=>$this->label,
            'qty'=>$this->qty,
            'delivered_remaining_qty'=>$this->qty,
            'invoiced_remaining_qty'=>$this->qty,
            'methods_units_id'=>$MethodsUnits,
            'selling_price'=>$this->selling_price,
            'discount'=>$this->discount,
            'accounting_vats_id'=>$AccountingVat,
            'internal_delay'=>$internalDelay,
            'delivery_date'=>$this->delivery_date,
        ]);

        //add line detail
        $detailData = ['order_lines_id' => $NewOrderLine->id];
        if ($this->product_id) {
            $product = Products::find($this->product_id);
            $detailData = array_merge($detailData, $this->buildLineDetailDataFromProduct($product));
        }
        $orderLineDetails = OrderLineDetails::create($detailData);
        $this->customRequirements[$orderLineDetails->id] = [];

        // Set Flash Message
        session()->flash('success','Line added Successfully');
        // Reset Form Fields After Creating line
        $this->resetFields();
    }

    public function edit($id){
        
        $Line = Orderlines::findOrFail($id);
        $this->order_lines_id = $id;
        $this->ordre = $Line->ordre;
        $this->code = $Line->code;
        $this->product_id = $Line->product_id;
        $this->label = $Line->label;
        $this->qty = $Line->qty;
        $this->methods_units_id = $Line->methods_units_id;
        $this->selling_price = $Line->selling_price;
        $this->discount = $Line->discount;
        $this->accounting_vats_id = $Line->accounting_vats_id;
        $this->delivery_date = $Line->delivery_date;
        $this->statu = $Line->statu;
        $this->updateLines = true;
        $this->usePriceList = false;
        $this->priceSource = __('general_content.custom_trans_key');
        $this->loadCustomerPriceList(false);
        $this->appliedPriceListId = $this->detectPriceListEntryForCurrentPrice();
        $this->refreshCustomerPriceListState();
    }

    public function enableCalculatedPrice($idline)
    {
        OrderLines::find($idline)->update(['use_calculated_price' => 1]);
        session()->flash('success','Line Updated Successfully');
    }

    public function disableCalculatedPrice($idline)
    {
        OrderLines::find($idline)->update(['use_calculated_price' => 0]);
        session()->flash('success','Line Updated Successfully');
    }

    public function updatedQty($value)
    {
        $this->refreshCustomerPriceListState();
        if ($this->usePriceList) {
            $this->applyCustomerPriceIfEnabled();
        }
    }

    public function updatedUsePriceList($value)
    {
        if ($value) {
            $this->applyCustomerPriceIfEnabled();
        } else {
            $this->priceSource = __('general_content.custom_trans_key');
            $this->refreshCustomerPriceListState();
        }
    }

    public function updatedSellingPrice($value)
    {
        if ($this->updatingPriceFromList) {
            return;
        }

        $this->usePriceList = false;
        $this->appliedPriceListId = null;
        $this->priceSource = __('general_content.custom_trans_key');
        $this->refreshCustomerPriceListState();
    }

    public function applyPriceFromList(int $priceListId)
    {
        $entry = $this->getPriceListEntry($priceListId);

        if (!$entry) {
            return;
        }

        $this->usePriceList = false;
        $this->setSellingPrice((float) $entry['price'], __('general_content.custom_trans_key') . ' - ' . $entry['scope_label'], $priceListId);
    }

    public function duplicateLine($id)
    {
        // Duplicate the order line
        $newOrderline = $this->duplicateOrderLine($id);

        // Duplicate the order line details
        $this->duplicateOrderLineDetails($id, $newOrderline->id);
    
        // Duplicate the tasks
        $this->duplicateTasks($id, $newOrderline->id);
    
        // Duplicate the sub-assemblies
        $this->duplicateSubAssemblies($id, $newOrderline->id);
    }
    
    private function duplicateOrderLine($id)
    {
        $orderLine = Orderlines::findOrFail($id);
        $newOrderLine = $orderLine->replicate();
        $newOrderLine->ordre = $orderLine->ordre + 1;
        $newOrderLine->code = $orderLine->code . "#duplicate" . $orderLine->id;
        $newOrderLine->label = $orderLine->label . "#duplicate" . $orderLine->id;
        $newOrderLine->save();
    
        return $newOrderLine;
    }
    
    private function duplicateOrderLineDetails($oldOrderLineId, $newOrderLineId)
    {
        $orderLineDetails = OrderLineDetails::where('order_lines_id', $oldOrderLineId)->first();
        if (!$orderLineDetails) {
            $newOrderLineDetails = OrderLineDetails::create([
                'order_lines_id' => $newOrderLineId,
            ]);
        } else {
            $newOrderLineDetails = $orderLineDetails->replicate();
            $newOrderLineDetails->order_lines_id = $newOrderLineId;
            $newOrderLineDetails->save();
        }

        $this->customRequirements[$newOrderLineDetails->id] = $this->normalizeCustomRequirements($newOrderLineDetails->custom_requirements);
    }
    
    private function duplicateTasks($oldOrderLineId, $newOrderLineId)
    {
        $tasks = Task::where('order_lines_id', $oldOrderLineId)->get();
        foreach ($tasks as $task) {
            $newTask = $task->replicate();
            $newTask->order_lines_id = $newOrderLineId;
            $newTask->origin = "5";
            $newTask->save();
        }
    }
    
    private function duplicateSubAssemblies($oldOrderLineId, $newOrderLineId)
    {
        $subAssemblies = SubAssembly::where('order_lines_id', $oldOrderLineId)->get();
        foreach ($subAssemblies as $subAssembly) {
            $newSubAssembly = $subAssembly->replicate();
            $newSubAssembly->order_lines_id = $newOrderLineId;
            $newSubAssembly->save();
        }
    }

    protected function resetProductSelection(): void
    {
        $this->resetPricingState();
        $this->code = '';
        $this->label = '';
        $this->methods_units_id = '';
        $this->selling_price = 0;
    }

    protected function resetPricingState(bool $keepToggle = false): void
    {
        if (!$keepToggle) {
            $this->usePriceList = true;
        }

        $this->customerPriceList = [];
        $this->appliedPriceListId = null;
        $this->priceSource = null;
    }

    protected function loadCustomerPriceList(bool $resetSelection = true): void
    {
        if ($resetSelection) {
            $this->appliedPriceListId = null;
            $this->priceSource = null;
        }

        if (!$this->product_id) {
            $this->customerPriceList = [];
            return;
        }

        $currency = $this->Factory->curency ?? 'EUR';

        $priceList = CustomerPriceList::with('company')
            ->where('products_id', $this->product_id)
            ->get()
            ->filter(function (CustomerPriceList $price) {
                if ($price->companies_id && $this->customerId && (int) $price->companies_id === (int) $this->customerId) {
                    return true;
                }

                if ($price->companies_id) {
                    return false;
                }

                if ($price->customer_type !== null && $this->customerType !== null) {
                    return (int) $price->customer_type === (int) $this->customerType;
                }

                return $price->companies_id === null && $price->customer_type === null;
            })
            ->values();

        $this->customerPriceList = $priceList->map(function (CustomerPriceList $price) use ($currency) {
            $minQty = (int) $price->min_qty;
            $maxQty = $price->max_qty !== null ? (int) $price->max_qty : null;
            $priority = $price->companies_id ? 1 : ($price->customer_type !== null ? 2 : 3);

            return [
                'id' => $price->id,
                'min_qty' => $minQty,
                'max_qty' => $maxQty,
                'price' => (float) $price->price,
                'formatted_price' => Number::currency($price->price, $currency, config('app.locale')),
                'scope' => $price->companies_id ? 'company' : ($price->customer_type !== null ? 'segment' : 'general'),
                'scope_label' => $this->getScopeLabel($price),
                'customer_type' => $price->customer_type !== null ? (int) $price->customer_type : null,
                'priority' => $priority,
                'matches' => false,
                'selected' => false,
            ];
        })->sort(function ($a, $b) {
            $priorityCompare = $a['priority'] <=> $b['priority'];
            if ($priorityCompare !== 0) {
                return $priorityCompare;
            }

            $minCompare = $a['min_qty'] <=> $b['min_qty'];
            if ($minCompare !== 0) {
                return $minCompare;
            }

            return $a['id'] <=> $b['id'];
        })->values()->toArray();

        $this->refreshCustomerPriceListState();
    }

    protected function applyCustomerPriceIfEnabled($product = null): void
    {
        if (!$this->product_id) {
            return;
        }

        if (empty($this->customerPriceList)) {
            $this->loadCustomerPriceList(false);
        }

        if (!$this->usePriceList) {
            $this->refreshCustomerPriceListState();
            return;
        }

        $qty = $this->getEffectiveQty();
        $bestPrice = $this->findBestPriceEntry($qty);

        if ($bestPrice) {
            $this->setSellingPriceFromList($bestPrice, true);
        } else {
            $this->applyDefaultProductPrice($product);
        }
    }

    protected function setSellingPriceFromList(array $entry, bool $automatic = false): void
    {
        $label = $entry['scope_label'];
        $source = $automatic
            ? __('general_content.customer_trans_key') . ' - ' . $label
            : __('general_content.custom_trans_key') . ' - ' . $label;

        $this->setSellingPrice((float) $entry['price'], $source, $entry['id']);
    }

    protected function setSellingPrice(float $price, string $source, ?int $priceListId = null): void
    {
        $this->updatingPriceFromList = true;
        $this->selling_price = $price;
        $this->updatingPriceFromList = false;
        $this->appliedPriceListId = $priceListId;
        $this->priceSource = $source;
        $this->refreshCustomerPriceListState($priceListId);
    }

    protected function applyDefaultProductPrice($product = null): void
    {
        if (!$product && $this->product_id) {
            $product = Products::select('selling_price')->find($this->product_id);
        }

        $price = $product->selling_price ?? 0;
        $this->setSellingPrice((float) $price, __('general_content.product_trans_key'));
    }

    protected function refreshCustomerPriceListState(?int $selectedId = null): void
    {
        if (empty($this->customerPriceList)) {
            return;
        }

        $qty = $this->getEffectiveQty();
        $selectedId = $selectedId ?? $this->appliedPriceListId;

        $this->customerPriceList = collect($this->customerPriceList)->map(function ($item) use ($qty, $selectedId) {
            $maxQty = $item['max_qty'] !== null ? (int) $item['max_qty'] : null;
            $item['matches'] = $this->quantityMatches($qty, (int) $item['min_qty'], $maxQty);
            $item['selected'] = $selectedId !== null && (int) $item['id'] === (int) $selectedId;
            return $item;
        })->values()->toArray();
    }

    protected function findBestPriceEntry(int $qty): ?array
    {
        if (empty($this->customerPriceList)) {
            return null;
        }

        $matches = collect($this->customerPriceList)
            ->filter(function ($entry) use ($qty) {
                $maxQty = $entry['max_qty'] !== null ? (int) $entry['max_qty'] : null;
                return $this->quantityMatches($qty, (int) $entry['min_qty'], $maxQty);
            })
            ->sort(function ($a, $b) {
                $priorityCompare = $a['priority'] <=> $b['priority'];
                if ($priorityCompare !== 0) {
                    return $priorityCompare;
                }

                $minCompare = $b['min_qty'] <=> $a['min_qty'];
                if ($minCompare !== 0) {
                    return $minCompare;
                }

                return $a['id'] <=> $b['id'];
            })
            ->values();

        return $matches->first();
    }

    protected function quantityMatches(int $qty, int $minQty, ?int $maxQty): bool
    {
        if ($qty < $minQty) {
            return false;
        }

        if ($maxQty !== null && $qty > $maxQty) {
            return false;
        }

        return true;
    }

    protected function getEffectiveQty(): int
    {
        $qty = (int) $this->qty;
        return $qty > 0 ? $qty : 1;
    }

    protected function getClientTypeLabel(?int $type): string
    {
        return match ((int) $type) {
            1 => __('general_content.legal_entity_trans_key'),
            2 => __('general_content.individual_trans_key'),
            default => __('general_content.customer_type_trans_key'),
        };
    }

    protected function getScopeLabel(CustomerPriceList $price): string
    {
        if ($price->companies_id) {
            $companyName = $price->company->label ?? ('#' . $price->companies_id);
            return __('general_content.companie_trans_key') . ' - ' . $companyName;
        }

        if ($price->customer_type !== null) {
            return __('general_content.customer_type_trans_key') . ' - ' . $this->getClientTypeLabel((int) $price->customer_type);
        }

        return __('general_content.customer_trans_key');
    }

    protected function getPriceListEntry(int $priceListId): ?array
    {
        foreach ($this->customerPriceList as $entry) {
            if ((int) $entry['id'] === (int) $priceListId) {
                return $entry;
            }
        }

        return null;
    }

    protected function detectPriceListEntryForCurrentPrice(): ?int
    {
        if (empty($this->customerPriceList)) {
            return null;
        }

        $qty = $this->getEffectiveQty();
        $currentPrice = (float) $this->selling_price;

        foreach ($this->customerPriceList as $entry) {
            $maxQty = $entry['max_qty'] !== null ? (int) $entry['max_qty'] : null;
            if ($this->quantityMatches($qty, (int) $entry['min_qty'], $maxQty) && (float) $entry['price'] === $currentPrice) {
                return (int) $entry['id'];
            }
        }

        return null;
    }

    public function createProduct($id)
    {
        $serviceComponent = MethodsServices::where('type', 8)->first();

        if ($serviceComponent) {
            $familyComponent = MethodsFamilies::where('methods_services_id', $serviceComponent->id)->first();
    
            if ($familyComponent) {
                // Get data to duplicate for new order
                $orderLineData = Orderlines::findOrFail($id);
                $newProduct = $this->createNewProduct($orderLineData, $serviceComponent->id, $familyComponent->id);
    
                // Update info that order line has task
                $orderLineData->product_id = $newProduct->id;
                $orderLineData->save();
    
                // Add line detail
                $this->addProductDetails($newProduct->id, $id);
    
                // Duplicate tasks
                $this->duplicateProductTasks($id, $newProduct->id);
    
                // Duplicate sub-assemblies
                $this->duplicateProductSubAssemblies($id, $newProduct->id);
    
                session()->flash('success', 'Product created successfully');
            } else {
                session()->flash('error', 'No component family');
            }
        } else {
            session()->flash('error', 'No component service');
        }
    }
    
    private function createNewProduct($orderLineData, $serviceComponentId, $familyComponentId)
    {
        return Products::create([
            'code' => $orderLineData->code,
            'label' => $orderLineData->label,
            'methods_services_id' => $serviceComponentId,
            'methods_families_id' => $familyComponentId,
            'purchased' => 2,
            'purchased_price' => 1,
            'sold' => 1,
            'selling_price' => $orderLineData->selling_price,
            'methods_units_id' => $orderLineData->methods_units_id,
            'tracability_type' => 1,
        ]);
    }
    
    private function addProductDetails($newProductId, $orderLineId)
    {
        $orderLineDetailData = OrderLineDetails::where('order_lines_id', $orderLineId)->firstOrFail();
        $newProductDetail = Products::findOrFail($newProductId);
    
        $newProductDetail->material = $orderLineDetailData->material;
        $newProductDetail->thickness = $orderLineDetailData->thickness;
        $newProductDetail->finishing = $orderLineDetailData->finishing;
        $newProductDetail->weight = $orderLineDetailData->weight;
        $newProductDetail->bend_count = $orderLineDetailData->bend_count;
        $newProductDetail->x_size = $orderLineDetailData->x_size;
        $newProductDetail->y_size = $orderLineDetailData->y_size;
        $newProductDetail->z_size = $orderLineDetailData->z_size;
        $newProductDetail->x_oversize = $orderLineDetailData->x_oversize;
        $newProductDetail->y_oversize = $orderLineDetailData->y_oversize;
        $newProductDetail->z_oversize = $orderLineDetailData->z_oversize;
        $newProductDetail->diameter = $orderLineDetailData->diameter;
        $newProductDetail->diameter_oversize = $orderLineDetailData->diameter_oversize;
        $newProductDetail->cad_file_path = $orderLineDetailData->cad_file_path;
        $newProductDetail->cam_file_path = $orderLineDetailData->cam_file_path;
        $newProductDetail->save();
    }

    private function buildLineDetailDataFromProduct(?Products $product): array
    {
        if (!$product) {
            return [];
        }

        return [
            'x_size' => $product->x_size,
            'y_size' => $product->y_size,
            'z_size' => $product->z_size,
            'x_oversize' => $product->x_oversize,
            'y_oversize' => $product->y_oversize,
            'z_oversize' => $product->z_oversize,
            'diameter' => $product->diameter,
            'diameter_oversize' => $product->diameter_oversize,
            'material' => $product->material,
            'thickness' => $product->thickness,
            'finishing' => $product->finishing,
            'weight' => $product->weight,
            'bend_count' => $product->bend_count,
            'cad_file_path' => $product->cad_file_path,
            'cam_file_path' => $product->cam_file_path,
        ];
    }
    
    private function duplicateProductTasks($orderLineId, $newProductId)
    {
        $tasks = Task::where('order_lines_id', $orderLineId)->get();
        foreach ($tasks as $task) {
            $newTask = $task->replicate();
            $newTask->products_id = $newProductId;
            $newTask->order_lines_id = null;
            $newTask->origin = "5";
            $newTask->save();
        }
    }
    
    private function duplicateProductSubAssemblies($orderLineId, $newProductId)
    {
        $subAssemblies = SubAssembly::where('order_lines_id', $orderLineId)->get();
        foreach ($subAssemblies as $subAssembly) {
            $newSubAssembly = $subAssembly->replicate();
            $newSubAssembly->products_id = $newProductId;
            $newSubAssembly->order_lines_id = null;
            $newSubAssembly->save();
        }
    }

    public function breakDown($id){
        $OrderLine = OrderLines::findOrFail($id);
        $TaskLine = Task::where('products_id', $OrderLine->product_id)->get();
        foreach ($TaskLine as $Task) 
        {
            $newTask = $Task->replicate();
            $newTask->order_lines_id = $id;
            $newTask->products_id = null;
            $newTask->status_id = $this->status_id['id'];
            $newTask->origin = "3";
            $newTask->save();
        }
        $SubAssemblyLine = SubAssembly::where('products_id', $OrderLine->product_id)->get();
        foreach ($SubAssemblyLine as $SubAssembly) 
        {
            $newSubAssembly = $SubAssembly->replicate();
            $newSubAssembly->order_lines_id = $id;
            $newSubAssembly->products_id = null;
            $newSubAssembly->save();
        }

        $OrderLine->tasks_status = 2;
        $OrderLine->save();
    }

    public function cancel()
    {
        $this->updateLines = false;
        $this->resetFields();
    }

    public function up($idStatu){
        // Update line
        Orderlines::find($idStatu)->increment('ordre',1);;
        session()->flash('success','Line Updated Successfully');
    }

    public function down($idStatu){
        // Update line
        Orderlines::find($idStatu)->decrement('ordre',1);;
        session()->flash('success','Line Updated Successfully');
    }

    public function update(){
        // Validate request
        if($this->OrderType == 2){
            $this->validate([
                'product_id' => 'required',
                'ordre' =>'required|numeric|gt:0',
                'label'=>'required',
                'qty'=>'required|min:1|not_in:0',
                'methods_units_id'=>'required',
                'selling_price'=>'required|numeric|gt:0',
                'discount'=>'required',
                'accounting_vats_id'=>'required',
            ]);
        }
        else{
            $this->validate();
        }
        // Update line
        $OderLineToUpdate = Orderlines::find($this->order_lines_id);

        //if we have already part delivered and new qty no match whit old qty
        if($OderLineToUpdate->delivered_qty > $this->qty && $OderLineToUpdate->delivered_qty != 0){
            session()->flash('error','Cant update if delivered remaining qty is superior to new quantity');
        }
        else{
            //if new qty change statu because were have new part produce
            // in future update task statu if they are finihed ?
            if( $OderLineToUpdate->delivery_status > 1 && $OderLineToUpdate->qty != $this->qty && $OderLineToUpdate->delivered_qty != 0 ){
                $OderLineToUpdate->delivery_status = 2;
            }
            /*
            this is avaible only if we check if delevery not exist
            if($OderLineToUpdate->delivered_qty == $this->qty ){
                $OderLineToUpdate->delivery_status = 3;
            }*/
            $date = date_create($this->delivery_date);
            $internalDelay = date_format(date_sub($date , date_interval_create_from_date_string($this->Factory->add_delivery_delay_order. " days")), 'Y-m-d');
            
            $OderLineToUpdate->ordre = $this->ordre;
            $OderLineToUpdate->code = $this->code;
            $OderLineToUpdate->product_id = $this->product_id;
            $OderLineToUpdate->label = $this->label;
            $OderLineToUpdate->qty = $this->qty;
            $OderLineToUpdate->delivered_remaining_qty = $this->qty;
            $OderLineToUpdate->invoiced_remaining_qty = $this->qty;
            $OderLineToUpdate->methods_units_id = $this->methods_units_id;
            $OderLineToUpdate->selling_price = $this->selling_price;
            $OderLineToUpdate->discount = $this->discount;
            $OderLineToUpdate->accounting_vats_id = $this->accounting_vats_id;
            $OderLineToUpdate->internal_delay = $internalDelay;
            $OderLineToUpdate->delivery_date = $this->delivery_date;
            $OderLineToUpdate->save();
    
            session()->flash('success','Line Updated Successfully');
        }
    }

    public function destroy($id){
        try{
            Orderlines::find($id)->delete();
            Task::where('order_lines_id',$id)->delete();
            session()->flash('success',"Line deleted Successfully!!");
        }catch(\Exception $e){
            session()->flash('error',"Something goes wrong while deleting Line");
        }
    }

    public function createNC($id, $companie_id){
        // Create non-conformity via service
        $this->qualityNonConformityService->createNC($id, $companie_id);
        
        return redirect()->route('quality.nonConformitie')->with('success', 'Successfully created non conformitie.');
    }
    
    // Helper method to check if lines exist
    private function linesExist()
    {
        $i = 0;
        foreach ($this->data as $key => $item) {
            if (array_key_exists("order_line_id", $this->data[$key]) && $this->data[$key]['order_line_id'] != false) {
                $i++;
            }
        }
        return $i > 0;
    }

    // Helper method to create serial numbers
    private function createSerialNumbers($OrderLineData, $quantity, $batchId = null)
    {
        $productId = $OrderLineData->product_id ?? null;
        for ($i = 0; $i < $quantity; $i++) {
            $this->serialNumberService->createSerialNumber($productId, $OrderLineData->id, 2, $batchId);
        }
    }

    // Helper method to update order line info
    private function updateOrderLineInfoFromDelevery($OrderLineData)
    {
        $OrderLineData->delivered_qty += $OrderLineData->delivered_remaining_qty;
        $OrderLineData->delivered_remaining_qty = 0;
        $OrderLineData->delivery_status = $OrderLineData->delivered_remaining_qty == 0 ? 3 : 2;
        $OrderLineData->save();
        event(new OrderLineUpdated($OrderLineData->id));
    }

    // Helper method to update order line info
    private function updateOrderLineInfoFromInvoice($OrderLineData)
    {
        $OrderLineData->invoiced_qty  += $OrderLineData->invoiced_remaining_qty;
        $OrderLineData->invoiced_remaining_qty = 0;
        $OrderLineData->invoice_status  = $OrderLineData->invoiced_remaining_qty == 0 ? 3 : 2;
        $OrderLineData->save();
        event(new OrderLineUpdated($OrderLineData->id));
    }
    

    // Helper method to remove from stock
    private function removeFromStock($OrderLineData)
    {
        $quantityRemaining = $OrderLineData->qty;
        $StockLocationProduct = StockLocationProducts::where('products_id', $OrderLineData->product_id)->get();
        foreach ($StockLocationProduct as $stock) {
            $quantityToWithdraw = min($stock->getCurrentStockMove(), $quantityRemaining);
            if ($quantityToWithdraw != 0) {
                $data = [
                    'user_id' => Auth::id(),
                    'qty' => $this->quantityToWithdraw,
                    'stock_location_products_id' => $this->stock->id,
                    'order_line_id' => $this->OrderLineData->id,
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

    public function storeDelevery($orderId)
    {
        if (!$this->linesExist()) {
            $errors = $this->getErrorBag();
            $errors->add('errors', 'no lines selected');
            return;
        }

        $OrderData = Orders::find($orderId);
        if (! $OrderData || $OrderData->statu == 6) {
            $errors = $this->getErrorBag();
            $errors->add('errors', __('general_content.order_canceled_no_document_trans_key'));
            return;
        }
        $LastDelivery = Deliverys::orderBy('id', 'desc')->first();
        $deliveryId = $LastDelivery ? $LastDelivery->id : 0;
        $deliveryCode = $this->documentCodeGenerator->generateDocumentCode('delivery', $deliveryId);

        $user = Auth::user();
        $DeliveryCreated = $this->deliveryService->createDelivery($deliveryCode, $deliveryCode, $OrderData->companies_id, $OrderData->companies_addresses_id, $OrderData->companies_contacts_id, $user->id);

        if ($DeliveryCreated) {
            foreach ($this->data as $key => $item) {
                $OrderLineData = OrderLines::find($key);
                $this->deliveryLineService->createDeliveryLine($DeliveryCreated, $key, $this->deleveryOrdre, $OrderLineData->delivered_remaining_qty);

                if ($this->CreateSerialNumber) {
                    $this->createSerialNumbers($OrderLineData, $OrderLineData->delivered_remaining_qty);
                }

                $this->updateOrderLineInfoFromDelevery($OrderLineData);

                if ($this->RemoveFromStock && $OrderLineData->product_id && $OrderLineData->Task()->get()->isEmpty()) {
                    $this->removeFromStock($OrderLineData);
                }

                $this->deleveryOrdre += 10;
            }
            return redirect()->route('deliverys.show', ['id' => $DeliveryCreated->id])->with('success', 'Successfully created new delivery note');
        } else {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }

    public function storeInvoice($orderId)
    {
        if (!$this->linesExist()) {
            $errors = $this->getErrorBag();
            $errors->add('errors', 'no lines selected');
            return;
        }

        $OrderData = Orders::find($orderId);
        if (! $OrderData || $OrderData->statu == 6) {
            $errors = $this->getErrorBag();
            $errors->add('errors', __('general_content.order_canceled_no_document_trans_key'));
            return;
        }
        $LastInvoice = Invoices::orderBy('id', 'desc')->first();
        $invoiceId = $LastInvoice ? $LastInvoice->id : 0;
        $invoiceCode = $this->documentCodeGenerator->generateDocumentCode('invoice', $invoiceId);

        $user = Auth::user();
        $InvoiceCreated = $this->invoiceService->createInvoice($invoiceCode, $invoiceCode, $OrderData->companies_id, $OrderData->companies_addresses_id, $OrderData->companies_contacts_id, $user->id);

        if ($InvoiceCreated) {
            foreach ($this->data as $key => $item) {
                $OrderLineData = OrderLines::find($key);
                $this->invoiceLineService->createInvoiceLine($InvoiceCreated, $key, null, $this->invoiceOrdre, $OrderLineData->invoiced_remaining_qty, $OrderLineData->accounting_vats_id);

                if ($this->CreateSerialNumber) {
                    $this->createSerialNumbers($OrderLineData, $OrderLineData->invoiced_remaining_qty);
                }

                $this->updateOrderLineInfoFromDelevery($OrderLineData);

                $this->updateOrderLineInfoFromInvoice($OrderLineData);

                if ($this->RemoveFromStock && $OrderLineData->product_id && $OrderLineData->Task()->get()->isEmpty()) {
                    $this->removeFromStock($OrderLineData);
                }

                $this->invoiceOrdre += 10;
            }
            return redirect()->route('invoices.show', ['id' => $InvoiceCreated->id])->with('success', 'Successfully created new invoice');
        } else {
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }
}
