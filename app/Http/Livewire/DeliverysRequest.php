<?php

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Models\Workflow\Deliverys;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;

class DeliverysRequest extends Component
{

    //use WithPagination;
    //protected $paginationTheme = 'bootstrap';

    public $companies_id = '';
    public $sortField = 'LABEL'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $LastDelivery = '1';

    public $DeliverysRequestsLineslist;
    public $CODE, $LABEL, $user_id; 
    public $updateLines = false;
    public $CompaniesSelect = [];
    public $order_line_id = [];
    public $scumQty = [];
    public $qty = [];


    // Validation Rules
    protected $rules = [
        'CODE' =>'required|unique:deliverys',
        'companies_id'=>'required',
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
        $this->LastDelivery =  Deliverys::latest()->first();
        if($this->LastDelivery == Null){
            $this->CODE = "DN-1";
            $this->LABEL = "DN-1";
        }
        else{
            $this->CODE = "DN-". $this->LastDelivery->id;
            $this->LABEL = "DN-". $this->LastDelivery->id;
        }

        $this->CompaniesSelect = Companies::select('id', 'LABEL', 'CODE')->orderBy('CODE')->get();
    }

    public function render()
    {
        $userSelect = User::select('id', 'name')->get();
        $DeliverysRequestsLineslist = $this->DeliverysRequestsLineslist = OrderLines::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                                                        ->where('statu', '=', '4')
                                                                        ->whereHas('order', function($q){
                                                                            $q->where('companies_id','like', '%'.$this->companies_id.'%');
                                                                        })->get();

        return view('livewire.deliverys-request', [
            'DeliverysRequestsLineslist' => $DeliverysRequestsLineslist,
            'userSelect' => $userSelect,
        ]);
    }

    public function storeDeliveryNote(){
        $this->validate();
            // Create Line
            $DeliveryCreated = Deliverys::create([
                                            'CODE'=>$this->CODE,  
                                            'LABEL'=>$this->LABEL, 
                                            'companies_id'=>$this->companies_id,   
                                            'user_id'=>$this->user_id, 
            ]);
            // Reset Form Fields After Creating line
        return redirect()->route('delivery.show', ['id' => $DeliveryCreated->id])->with('success', 'Successfully created new delivery note');
    }
}
