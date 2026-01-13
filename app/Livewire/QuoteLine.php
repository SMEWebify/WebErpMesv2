<?php

namespace App\Livewire;

use Livewire\Component;
use App\Events\OrderCreated;
use Livewire\WithPagination;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Services\OrderService;
use App\Models\Planning\Status;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Products\Products;
use App\Models\Products\CustomerPriceList;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use Illuminate\Support\Facades\App;
use App\Services\CustomFieldService;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\SubAssembly;
use Illuminate\Support\Facades\Auth;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Accounting\AccountingVat;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\QuoteLineDetails;
use Illuminate\Support\Number;

class QuoteLine extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'ordre'; // default sorting field
    public $sortAsc = true; // default sort direction
    protected $customFieldService;
    
    public $QuoteId;
    public $QuoteStatu;
    public $quote_Statu;
    public $status_id;

    public $QuoteLineslist;
    public $quote_lines_id, $quotes_id, $ordre = 1, $product_id, $methods_units_id, $selling_price, $accounting_vats_id, $delivery_date, $statu, $use_calculated_price;
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
    public $productCustomFields = [];

    protected $updatingPriceFromList = false;

    public $data = [];
    public $customRequirements = [];
    public $selectAllLines = false;

    protected $orderService;

    public function __construct()
    {
        // Resolve the service via the Laravel container
        $this->orderService = App::make(OrderService::class);
        $this->customFieldService = App::make(CustomFieldService::class);
    }

    // Validation Rules
    protected $rules = [
        'ordre' =>'required|numeric|min:0|not_in:0',
        'label'=>'required',
        'qty'=>'required|min:1|not_in:0',
        'selling_price'=>'required|numeric|min:0|not_in:0',
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

    public function mount($QuoteId, $QuoteStatu, $QuoteDelay)
    {
        $quote = Quotes::with('companie')->findOrFail($QuoteId);

        $this->quotes_id = $quote->id;
        $this->quote_Statu = $QuoteStatu;
        $this->delivery_date = $QuoteDelay;
        $this->status_id = Status::select('id')->orderBy('order')->first();
        $this->Factory = Factory::first();
        $this->priceListToggleKey = 'quote-' . $quote->id;

        if ($quote->companie) {
            $this->customerId = $quote->companie->id;
            $this->customerType = $quote->companie->client_type !== null ? (int) $quote->companie->client_type : null;
            $this->customerDiscount = $quote->companie->discount ?? 0;
            $this->discount = $this->customerDiscount;
        }
        $this->ProductsSelect = Products::select('id', 'label', 'code')->orderBy('code')->get();
        $this->VATSelect = AccountingVat::select('id', 'label', 'default')->orderBy('rate')->get();
        $this->UnitsSelect = MethodsUnits::select('id', 'label', 'code', 'default')->orderBy('label')->get();
        $this->ProductSelect = Products::select('id', 'code','label', 'methods_services_id')->get();
        $this->initializeCustomRequirements();
    }

    public function render()
    {
        $QuoteLineslist = $this->QuoteLineslist = Quotelines::with('QuoteLineDetails')
                                                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                                            ->where('quotes_id', '=', $this->quotes_id)
                                                            ->where('label','like', '%'.$this->search.'%')->get();

        $this->loadProductCustomFields($QuoteLineslist);
        $this->syncSelectAllState($QuoteLineslist->pluck('id'));

        foreach ($QuoteLineslist as $line) {
            $detail = $line->QuoteLineDetails;
            if ($detail && !array_key_exists($detail->id, $this->customRequirements)) {
                $this->customRequirements[$detail->id] = $this->normalizeCustomRequirements($detail->custom_requirements);
            }
        }

        return view('livewire.quote-lines', [
            'QuoteLineslist' => $QuoteLineslist,
        ]);
    }

    public function toggleSelectAllLines(): void
    {
        $shouldSelect = ! $this->selectAllLines;
        $lineIds = $this->getSelectableLineIds();

        if ($shouldSelect) {
            foreach ($lineIds as $lineId) {
                $this->data[$lineId]['quote_line_id'] = true;
            }
        } else {
            foreach ($lineIds as $lineId) {
                unset($this->data[$lineId]);
            }
        }

        $this->selectAllLines = $shouldSelect;
    }

    private function syncSelectAllState($lineIds): void
    {
        if ($lineIds->isEmpty()) {
            $this->selectAllLines = false;
            return;
        }

        $this->selectAllLines = $lineIds->every(function ($lineId) {
            return $this->isLineSelected((int) $lineId);
        });
    }

    private function isLineSelected(int $lineId): bool
    {
        return !empty($this->data[$lineId]['quote_line_id']);
    }

    private function getSelectableLineIds(): array
    {
        if ($this->QuoteLineslist) {
            return $this->QuoteLineslist->pluck('id')->all();
        }

        return QuoteLines::where('quotes_id', '=', $this->quotes_id)
            ->where('label', 'like', '%' . $this->search . '%')
            ->pluck('id')
            ->all();
    }

    private function initializeCustomRequirements(): void
    {
        $lineIds = QuoteLines::where('quotes_id', $this->quotes_id)->pluck('id');

        if ($lineIds->isEmpty()) {
            $this->customRequirements = [];
            return;
        }

        QuoteLineDetails::whereIn('quote_lines_id', $lineIds)
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

    private function loadProductCustomFields($quoteLines): void
    {
        foreach ($quoteLines as $line) {
            $this->productCustomFields[$line->id] = $this->customFieldService
                ->getProductCustomFieldsForQuoteLine($line->product_id, $line->id);
        }
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

    public function storeQuoteLine(){
        $this->validate();
        // Create Line
        
        $AccountingVat = AccountingVat::getDefault(); 
        $MethodsUnits = MethodsUnits::getDefault(); 
        $AccountingVat = ($AccountingVat->id  ?? 0);
        $MethodsUnits = ($MethodsUnits->id  ?? 0);

        if($AccountingVat == 0|| $MethodsUnits == 0 ){
            return redirect()->route('quotes.show', ['id' =>  $this->quotes_id])->with('error', 'No VAT or Unit default settings');
        }

        $NewQuoteLine = Quotelines::create([
            'quotes_id'=>$this->quotes_id,
            'ordre'=>$this->ordre,
            'code'=>$this->code,
            'product_id'=>$this->product_id,
            'label'=>$this->label,
            'qty'=>$this->qty,
            'methods_units_id'=>$MethodsUnits,
            'selling_price'=>$this->selling_price,
            'discount'=>$this->discount,
            'accounting_vats_id'=>$AccountingVat,
            'delivery_date'=>$this->delivery_date,
        ]);
        
        //add line detail
        $detailData = ['quote_lines_id' => $NewQuoteLine->id];
        if ($this->product_id) {
            $product = Products::find($this->product_id);
            $detailData = array_merge($detailData, $this->buildLineDetailDataFromProduct($product));
        }
        $quoteLineDetails = QuoteLineDetails::create($detailData);
        $this->customRequirements[$quoteLineDetails->id] = [];
        
        // Set Flash Message
        session()->flash('success','Line added Successfully');
        // Reset Form Fields After Creating line
        $this->resetFields();
    }

    public function editQuoteLine($id){
        $Line = Quotelines::findOrFail($id);
        $this->quote_lines_id = $id;
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

    public function updateQuoteLine(){
        // Validate request
        $this->validate();
        // Update line
        Quotelines::find($this->quote_lines_id)->fill([
            'ordre'=>$this->ordre,
            'code'=>$this->code,
            'product_id'=>$this->product_id,
            'label'=>$this->label,
            'qty'=>$this->qty,
            'methods_units_id'=>$this->methods_units_id,
            'selling_price'=>$this->selling_price,
            'discount'=>$this->discount,
            'accounting_vats_id'=>$this->accounting_vats_id,
            'delivery_date'=>$this->delivery_date,
            'statu'=>$this->statu,
        ])->save();
        session()->flash('success','Line Updated Successfully');
    }

    public function enableCalculatedPrice($idline)
    {
        Quotelines::find($idline)->update(['use_calculated_price' => 1]);
        session()->flash('success','Line Updated Successfully');
    }

    public function disableCalculatedPrice($idline)
    {
        Quotelines::find($idline)->update(['use_calculated_price' => 0]);
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
        // Duplicate the quote line
        $newQuoteLine = $this->duplicateQuoteLine($id);
    
        // Duplicate the quote line details
        $this->duplicateQuoteLineDetails($id, $newQuoteLine->id);
    
        // Duplicate the tasks
        $this->duplicateTasks($id, $newQuoteLine->id);
    
        // Duplicate the sub-assemblies
        $this->duplicateSubAssemblies($id, $newQuoteLine->id);
    }
    
    private function duplicateQuoteLine($id)
    {
        $quoteLine = Quotelines::findOrFail($id);
        $newQuoteLine = $quoteLine->replicate();
        $newQuoteLine->ordre = $quoteLine->ordre + 1;
        $newQuoteLine->code = $quoteLine->code . "#duplicate" . $quoteLine->id;
        $newQuoteLine->label = $quoteLine->label . "#duplicate" . $quoteLine->id;
        $newQuoteLine->save();
    
        return $newQuoteLine;
    }
    
    private function duplicateQuoteLineDetails($oldQuoteLineId, $newQuoteLineId)
    {
        $quoteLineDetails = QuoteLineDetails::where('quote_lines_id', $oldQuoteLineId)->first();
        if (!$quoteLineDetails) {
            $newQuoteLineDetails = QuoteLineDetails::create([
                'quote_lines_id' => $newQuoteLineId,
            ]);
        } else {
            $newQuoteLineDetails = $quoteLineDetails->replicate();
            $newQuoteLineDetails->quote_lines_id = $newQuoteLineId;
            $newQuoteLineDetails->save();
        }

        $this->customRequirements[$newQuoteLineDetails->id] = $this->normalizeCustomRequirements($newQuoteLineDetails->custom_requirements);
    }
    
    private function duplicateTasks($oldQuoteLineId, $newQuoteLineId)
    {
        $tasks = Task::where('quote_lines_id', $oldQuoteLineId)->get();
        foreach ($tasks as $task) {
            $newTask = $task->replicate();
            $newTask->quote_lines_id = $newQuoteLineId;
            $newTask->origin = "5";
            $newTask->save();
        }
    }
    
    private function duplicateSubAssemblies($oldQuoteLineId, $newQuoteLineId)
    {
        $subAssemblies = SubAssembly::where('quote_lines_id', $oldQuoteLineId)->get();
        foreach ($subAssemblies as $subAssembly) {
            $newSubAssembly = $subAssembly->replicate();
            $newSubAssembly->quote_lines_id = $newQuoteLineId;
            $newSubAssembly->save();
        }
    }
    
    public function createProduct($id)
    {
        $serviceComponent = MethodsServices::where('type', 8)->first();
        $familyComponent = MethodsFamilies::where('methods_services_id', $serviceComponent->id)->first();
    
        if ($serviceComponent && $familyComponent) {
            // Get data to duplicate for new order
            $quoteLineData = Quotelines::findOrFail($id);
            $newProduct = $this->createNewProduct($quoteLineData, $serviceComponent->id, $familyComponent->id);
    
            // Update info that order line has task
            $quoteLineData->product_id = $newProduct->id;
            $quoteLineData->save();
    
            // Add line detail
            $this->addProductDetails($newProduct->id, $id);
    
            // Duplicate tasks
            $this->duplicateProductTasks($id, $newProduct->id);
    
            // Duplicate sub-assemblies
            $this->duplicateProductSubAssemblies($id, $newProduct->id);
    
            session()->flash('success', 'Product created successfully');
        } else {
            session()->flash('error', 'No component service or family');
        }
    }
    
    private function createNewProduct($quoteLineData, $serviceComponentId, $familyComponentId)
    {
        return Products::create([
            'code' => $quoteLineData->code,
            'label' => $quoteLineData->label,
            'methods_services_id' => $serviceComponentId,
            'methods_families_id' => $familyComponentId,
            'purchased' => 2,
            'purchased_price' => 1,
            'sold' => 1,
            'selling_price' => $quoteLineData->selling_price,
            'methods_units_id' => $quoteLineData->methods_units_id,
            'tracability_type' => 1,
        ]);
    }
    
    private function addProductDetails($newProductId, $quoteLineId)
    {
        $quoteLineDetailData = QuoteLineDetails::where('quote_lines_id', $quoteLineId)->firstOrFail();
        $newProductDetail = Products::findOrFail($newProductId);
    
        $newProductDetail->material = $quoteLineDetailData->material;
        $newProductDetail->thickness = $quoteLineDetailData->thickness;
        $newProductDetail->finishing = $quoteLineDetailData->finishing;
        $newProductDetail->weight = $quoteLineDetailData->weight;
        $newProductDetail->bend_count = $quoteLineDetailData->bend_count;
        $newProductDetail->x_size = $quoteLineDetailData->x_size;
        $newProductDetail->y_size = $quoteLineDetailData->y_size;
        $newProductDetail->z_size = $quoteLineDetailData->z_size;
        $newProductDetail->x_oversize = $quoteLineDetailData->x_oversize;
        $newProductDetail->y_oversize = $quoteLineDetailData->y_oversize;
        $newProductDetail->z_oversize = $quoteLineDetailData->z_oversize;
        $newProductDetail->diameter = $quoteLineDetailData->diameter;
        $newProductDetail->diameter_oversize = $quoteLineDetailData->diameter_oversize;
        $newProductDetail->cad_file_path = $quoteLineDetailData->cad_file_path;
        $newProductDetail->cam_file_path = $quoteLineDetailData->cam_file_path;
        $newProductDetail->save();
    }
    
    private function duplicateProductTasks($quoteLineId, $newProductId)
    {
        $tasks = Task::where('quote_lines_id', $quoteLineId)->get();
        foreach ($tasks as $task) {
            $newTask = $task->replicate();
            $newTask->products_id = $newProductId;
            $newTask->quote_lines_id = null;
            $newTask->origin = "5";
            $newTask->save();
        }
    }
    
    private function duplicateProductSubAssemblies($quoteLineId, $newProductId)
    {
        $subAssemblies = SubAssembly::where('quote_lines_id', $quoteLineId)->get();
        foreach ($subAssemblies as $subAssembly) {
            $newSubAssembly = $subAssembly->replicate();
            $newSubAssembly->products_id = $newProductId;
            $newSubAssembly->quote_lines_id = null;
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
    
    public function breakDown($id){
        $Quoteline = Quotelines::findOrFail($id);
        $TaskLine = Task::where('products_id', $Quoteline->product_id)->get();
        foreach ($TaskLine as $Task) 
        {
            $newTask = $Task->replicate();
            $newTask->quote_lines_id = $id;
            $newTask->products_id = null;
            $newTask->status_id = $this->status_id['id'];
            $newTask->origin = "3";
            $newTask->save();
        }
        $SubAssemblyLine = SubAssembly::where('products_id', $Quoteline->product_id)->get();
        foreach ($SubAssemblyLine as $SubAssembly) 
        {
            $newSubAssembly = $SubAssembly->replicate();
            $newSubAssembly->quote_lines_id = $id;
            $newSubAssembly->products_id = null;
            $newSubAssembly->save();
        }
    }

    public function cancel(){
        $this->updateLines = false;
        $this->resetFields();
    }

    public function upQuoteLine($idStatu){
        // Update line
        Quotelines::find($idStatu)->increment('ordre',1);
        session()->flash('success','Line Updated Successfully');
    }

    public function downQuoteLine($idStatu){
        // Update line
        Quotelines::find($idStatu)->decrement('ordre',1);
        session()->flash('success','Line Updated Successfully');
    }

    public function destroyQuoteLine($id){
        try{
            Quotelines::find($id)->delete();
            Task::where('quote_lines_id',$id)->delete();
            session()->flash('success',"Line deleted Successfully!!");
        }catch(\Exception $e){
            session()->flash('error',"Something goes wrong while deleting Line");
        }
    }

    public function storeOrder($quoteId){

        //check if line exist
        $i = 0;
        foreach ($this->data as $key => $item) {
            if(array_key_exists("quote_line_id",$this->data[$key])){
                if($this->data[$key]['quote_line_id'] != false ){
                    $i++;
                }
            }
        }

        if($i>0){

            //get data to dulicate for new order
            $QuoteData = Quotes::find($quoteId);

            // Generate new order code
            $lastOrder = Orders::latest('id')->first();
            $orderCode = $lastOrder ? 'OR-' . ($lastOrder->id + 1) : 'OR-1';
            

             // Create order
            $user = Auth::user();
            $OrdersCreated = $this->orderService->createOrder(
                $orderCode,
                $QuoteData->label,
                $QuoteData->customer_reference,
                $QuoteData->companies_id,
                $QuoteData->companies_contacts_id,
                $QuoteData->companies_addresses_id,
                $QuoteData->validity_date,
                1,
                $user->id,
                $QuoteData->accounting_payment_conditions_id,
                $QuoteData->accounting_payment_methods_id,
                $QuoteData->accounting_deliveries_id,
                $QuoteData->comment,
                1,
                $QuoteData->id,
                null
            );

           // Trigger the event
            event(new OrderCreated($OrdersCreated));

            if($OrdersCreated){
                // Create lines
                foreach ($this->data as $key => $item) {

                    //get data to dulicate for new order
                    $QuoteLineData = Quotelines::find($key);

                    $date = date_create($QuoteLineData->delivery_date);
                    $internalDelay = date_format(date_sub($date , date_interval_create_from_date_string($this->Factory->add_delivery_delay_order. " days")), 'Y-m-d');
                    
                    $newOrderline = Orderlines::create([
                        'orders_id'=>$OrdersCreated->id,
                        'ordre'=>$QuoteLineData->ordre,
                        'code'=>$QuoteLineData->code,
                        'product_id'=>$QuoteLineData->product_id,
                        'label'=>$QuoteLineData->label,
                        'qty'=>$QuoteLineData->qty,
                        'delivered_remaining_qty'=>$QuoteLineData->qty,
                        'invoiced_remaining_qty'=>$QuoteLineData->qty,
                        'methods_units_id'=>$QuoteLineData->methods_units_id,
                        'selling_price'=>$QuoteLineData->selling_price,
                        'discount'=>$QuoteLineData->discount,
                        'accounting_vats_id'=>$QuoteLineData->accounting_vats_id,
                        'internal_delay'=>$internalDelay,
                        'delivery_date'=>$QuoteLineData->delivery_date,
                    ]);

                    //add line detail
                    $QuoteLineDetailData = QuoteLineDetails::where('quote_lines_id', $key)->first();
                    $newOrderLineDetail = OrderLineDetails::create([
                        'order_lines_id'=>$newOrderline->id,
                        'x_size'=>$QuoteLineDetailData->x_size,
                        'y_size'=>$QuoteLineDetailData->y_size,
                        'z_size'=>$QuoteLineDetailData->z_size,
                        'x_oversize'=>$QuoteLineDetailData->x_oversize,
                        'y_oversize'=>$QuoteLineDetailData->y_oversize,
                        'z_oversize'=>$QuoteLineDetailData->z_oversize,
                        'diameter'=>$QuoteLineDetailData->diameter,
                        'diameter_oversize'=>$QuoteLineDetailData->diameter_oversize,
                        'material'=>$QuoteLineDetailData->material,
                        'thickness'=>$QuoteLineDetailData->thickness,
                        'finishing'=>$QuoteLineDetailData->finishing,
                        'weight'=>$QuoteLineDetailData->weight,
                        'bend_count'=>$QuoteLineDetailData->bend_count,
                        'material_loss_rate'=>$QuoteLineDetailData->material_loss_rate,
                        'cad_file'=>$QuoteLineDetailData->cad_file,
                        'cam_file'=>$QuoteLineDetailData->cam_file,
                        'cad_file_path'=>$QuoteLineDetailData->cad_file_path,
                        'cam_file_path'=>$QuoteLineDetailData->cam_file_path,
                        'internal_comment'=>$QuoteLineDetailData->internal_comment,
                        'external_comment'=>$QuoteLineDetailData->external_comment,
                    ]);

                    $Tasks = Task::where('quote_lines_id', $key)->get();
                    foreach ($Tasks as $Task) 
                    {
                        $newTask = $Task->replicate();
                        $newTask->order_lines_id = $newOrderline->id;
                        $newTask->quote_lines_id = null;
                        $newTask->origin = "6";
                        $newTask->save();

                        //update info that order line as task
                        $OrderLine = OrderLines::find($newOrderline->id);
                        $OrderLine->tasks_status = 2;
                        $OrderLine->save();
                        
                    }
                    
                    $SubAssemblyLine = SubAssembly::where('quote_lines_id', $key)->get();
                    foreach ($SubAssemblyLine as $SubAssembly) 
                    {
                        $newSubAssembly = $SubAssembly->replicate();
                        $newSubAssembly->order_lines_id = $newOrderline->id;
                        $newSubAssembly->quote_lines_id = null;
                        $newSubAssembly->save();
                    }

                    //update quote lines statu
                    Quotelines::where('id',$QuoteLineData->id)->update(['statu'=>3]);
                }
                //update quote statu
                Quotes::where('id',$quoteId)->update(['statu'=>3]);
                
            }
            else{
                return redirect()->back()->with('error', 'Something went wrong');
            }

            // Reset Form Fields After Creating line
            return redirect()->route('orders.show', ['id' => $OrdersCreated->id])->with('success', 'Successfully created new order');

        }
        else{
            $errors = $this->getErrorBag();
            $errors->add('errors', 'no lines selected');
        }
    }
}
