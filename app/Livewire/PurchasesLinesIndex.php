<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Admin\Factory;
use App\Models\Planning\Status;
use App\Models\Products\Products;
use App\Models\Purchases\Purchases;
use Illuminate\Support\Facades\App;
use App\Models\Methods\MethodsUnits;
use Illuminate\Support\Facades\Auth;
use App\Services\PurchaseOrderService;
use App\Models\Purchases\PurchaseLines;
use App\Services\DocumentCodeGenerator;
use App\Services\PurchaseReceiptService;
use App\Models\Purchases\PurchaseReceipt;

class PurchasesLinesIndex extends Component
{

    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    public $updateLines = false;
    public $search = '';
    public $sortField = 'label'; // default sorting field
    public $sortAsc = true; // default sort direction

    public $purchase_id;
    public $search_product_id;
    
    public $OrderStatu;
    public $order_Statu;
    public $OrderType;
    public $status_id;
    public $OrderLineslist;
    public $purchase_lines_id, $ordre = 1,$product_id, $methods_units_id, $selling_price, $accounting_vats_id, $delivery_date, $statu;
    public $code='';
    public $label='';
    public $qty= 0;
    public $discount= 0;
    public $ProductsSelect = [];
    public $UnitsSelect = [];
    public $VATSelect = [];
    public $Factory = [];
    public $ProductSelect  = [];
    public $TechServicesSelect = [];
    public $BOMServicesSelect = [];
    public $TechProductList = [];
    public $BOMProductList = [];
    public $data = [];
    protected $purchaseOrderService;
    protected $purchaseReceiptService;    
    protected $documentCodeGenerator;

    public function __construct()
    {
        // Resolve the service via the Laravel container
        $this->purchaseOrderService = App::make(PurchaseOrderService::class);
        $this->purchaseReceiptService = App::make(PurchaseReceiptService::class);
        $this->documentCodeGenerator = App::make(DocumentCodeGenerator::class);
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

    public function mount($purchase_id, $OrderStatu) 
    {
        $this->purchase_id = $purchase_id;
        $this->order_Statu = $OrderStatu;
        $this->Factory = Factory::first();
        $this->status_id = Status::select('id')->orderBy('order')->first();
        $purchase = Purchases::select('companies_id')->find($this->purchase_id);
        $productsQuery = Products::select('id', 'label', 'code');
        if ($purchase && $purchase->companies_id) {
            $productsQuery->whereHas('preferredSuppliers', function ($query) use ($purchase) {
                $query->where('companies_id', $purchase->companies_id);
            });
        }
        $this->ProductsSelect = $productsQuery->orderBy('code')->get();
        $this->UnitsSelect = MethodsUnits::select('id', 'label', 'code')->orderBy('label')->get();
        $this->ProductSelect = Products::select('id', 'code','label', 'methods_services_id')->get();
}

    public function render()
    {
        if(is_numeric($this->search_product_id)){
            $PurchaseLines = PurchaseLines::where('product_id', $this->search_product_id)
                                    ->where('label','like', '%'.$this->search.'%')
                                    ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                    ->get();
        
        }
        else{
            $PurchaseLines = PurchaseLines::where('purchases_id', $this->purchase_id)
                                            ->where('label','like', '%'.$this->search.'%')
                                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->get();
        }
        
        return view('livewire.purchases-lines-index',[
            'PurchasesLineslist' => $PurchaseLines,
        ]);
    }

    public function storeOrderLine(){

        $this->validate([
            'product_id' => 'required',
            'ordre' =>'required|numeric|gt:0',
            'label'=>'required',
            'qty'=>'required|min:1|not_in:0',
            'selling_price'=>'required|numeric|gt:0',
            'discount'=>'required',
            'methods_units_id'=>'required',
        ]);

        $AccountingVat = $this->purchaseOrderService->getAccountingVat();
        if(!$AccountingVat){
            return redirect()->route('purchases.show', ['id' =>  $this->purchase_id])->with('error', 'No default settings');
        }
        

        /* can't use the Task model here,
        because it's not the same qty we use 
        getQualityRequiredAttribute in service 
        
        $task = new Task([
            'id' => '0',
            'component_id' => $this->product_id,
            'label' => $this->label,
            'qty'=>$this->qty,
            'unit_cost'=>$this->selling_price,
            'methods_units_id' => $this->methods_units_id,
        ]);

        $this->purchaseOrderService->createPurchaseOrderLine($this->purchase_id, $task, $accountingVat->id, $this->ordre, $this->discount);*/


        // Create Line
        $NewPurchaseLines = PurchaseLines::create([
            'purchases_id'=>$this->purchase_id,
            'ordre'=>$this->ordre,
            'code'=>$this->code,
            'product_id'=>$this->product_id,
            'label'=>$this->label,
            'qty'=>$this->qty,
            'selling_price'=>$this->selling_price,
            'discount'=>$this->discount,
            'methods_units_id'=>$this->methods_units_id,
            'accounting_vats_id'=>$AccountingVat->id,
        ]);

        // Set Flash Message
        session()->flash('success','Line added Successfully');
        // Reset Form Fields After Creating line
        $this->resetFields();
    }

    public function ChangeCodelabel()
    {
        $product = Products::select('id', 'label', 'code', 'methods_units_id', 'selling_price')->where('id', $this->product_id)->get();
        if(count($product) > 0){
            $this->code = $product[0]->code ;
            $this->label =  $product[0]->label;
            $this->methods_units_id =  $product[0]->methods_units_id;
            $this->selling_price =  $product[0]->selling_price;
        }else{
            $this->code ='';
            $this->label ='';
            $this->methods_units_id ='';
            $this->selling_price ='';
        }
    }

    public function resetFields(){
        $this->ordre = $this->ordre+1;
        $this->code = '';
        $this->product_id = '';
        $this->label = '';
    }

    public function editPurchaseLine($id){
        $Line = PurchaseLines::findOrFail($id);
        $this->purchase_lines_id = $id;
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
    }

    public function updatePurchaseLine(){
        // Validate request
        $this->validate();
        // Update line
        PurchaseLines::find($this->purchase_lines_id)->fill([
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

    public function storeReciep($PurchaseID)
    {
        $Purchase = Purchases::findOrFail($PurchaseID);
        $lastReceipt =  PurchaseReceipt::latest()->first();
        $purchaseReceiptId = $lastReceipt ? $lastReceipt->id : 0;
        $code = $this->documentCodeGenerator->generateDocumentCode('purchase-receipt', $purchaseReceiptId);

        try {
            // Données du reçu d'achat
            $receiptData = [
                'code' => $code ,
                'label' => $code ,
                'companies_id' => $Purchase->companies_id,
                'user_id' => Auth::id(),
            ];

            // Appel au service pour la création du reçu
            $ReceiptCreated = $this->purchaseReceiptService->createPurchaseReceipt($this->data, $receiptData);

            return redirect()->route('purchase.receipts.show', ['id' => $ReceiptCreated->id])
                ->with('success', 'Successfully created new receipt');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
