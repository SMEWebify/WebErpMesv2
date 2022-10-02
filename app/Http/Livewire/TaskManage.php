<?php

namespace App\Http\Livewire;

use stdClass;
use Livewire\Component;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use PhpParser\Node\Expr\Empty_;
use App\Models\Products\Products;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\QuoteLines;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsServices;

class TaskManage extends Component
{
    public $idLine;
    public $idType;
    public $idPage;
    public $statu;
    public $status_id;
    public $ServicesSelect;
    public $TaskType = "TechCut";

    public $taskId;
    public $ordre;
    public $label;
    public $methods_services_id;
    public $component_id;
    public $type;
    public $qty;
    public $seting_time = 0;
    public $unit_time = 0;
    public $unit_cost = 0;
    public $unit_price = 0;
    public $methods_units_id = 0;

    Private $quote_lines_id;
    Private $order_lines_id;
    Private $products_id;

    public $updateLines = false;

    // Validation Rules
    protected $rules = [
        'label'=>'required',
        'ordre' =>'required|numeric|gt:0',
        'methods_services_id' =>'required',
        'unit_cost'=>'required|numeric|gt:0',
        'unit_price'=>'required|numeric|gt:0',
    ];

    public function ChangeTaskType() 
    {
        if($this->TaskType == 'TechCut'){
            $this->ServicesSelect = MethodsServices::select('id', 'code','label', 'type')->where('type', '=', 1)->orWhere('type', '=', 7)->orderBy('ordre')->get();
        }
        elseif($this->TaskType == 'BOM'){
            $this->ServicesSelect = MethodsServices::select('id', 'code','label', 'type')->where('type', '=', 2)
                                                                            ->orWhere('type', '=', 3)
                                                                            ->orWhere('type', '=', 4)
                                                                            ->orWhere('type', '=', 5)
                                                                            ->orWhere('type', '=', 6)
                                                                            ->orWhere('type', '=', 8)
                                                                            ->orderBy('ordre')->get();
        }
        else{
            $this->ServicesSelect = [];
            session()->flash('error', 'Please select on type of document.');
        }
    }

    public function ChangeCodelabel()
    {
        $Service = MethodsServices::select('id', 'label')->where('id', $this->methods_services_id)->get();
        if(count($Service) > 0){
            $this->label =  $Service[0]->label;
        }else{
            $this->label ='';
        }
    }

    public function mount($idType, $idPage, $idLine) 
    {
        $this->idLine = $idLine;
        $this->idType = $idType;
        $this->idPage = $idPage;

        if($this->idType == 'products_id'){
            $this->products_id = $idLine;
        }
        elseif($this->idType == 'quote_lines_id'){
            $this->quote_lines_id = $idLine;
        }
        elseif($this->idType == 'order_lines_id'){
            $this->order_lines_id = $idLine;
        }

        $this->UnitsSelect = MethodsUnits::select('id', 'label', 'code')->orderBy('label')->get();
        $this->Factory = Factory::first();
        $status =  Status::select('id')->orderBy('order')->first();
        $this->status_id = $status->id;
        $this->ProductSelect = Products::select('id', 'code','label', 'methods_services_id')->get();
        $this->ServicesSelect = MethodsServices::select('id', 'code','label', 'type')->where('type', '=', 1)->orWhere('type', '=', 7)->orderBy('ordre')->get();

    }

    public function render()
    {
        if($this->idType == 'products_id'){
            $Line = Products::findOrFail($this->idLine);
            $this->qty = 1 ;
        }
        elseif($this->idType == 'quote_lines_id'){
            $Line = Quotelines::findOrFail($this->idLine);
            $this->qty = $Line->qty ;
        }
        elseif($this->idType == 'order_lines_id'){
            $Line = OrderLines::findOrFail($this->idLine);
            $this->qty = $Line->qty ;
        }
        else{
            $Line = new stdClass();
            $Line->id = null;
            $Line->qty = 0;
        }

        return view('livewire.task-manage',[
            'Line' => $Line,
        ]);
    }

    public function resetFields(){
        $this->ordre = $this->ordre+1;
        $this->methods_services_id = '';
        $this->component_id  = '';
        $this->label  = '';
        $this->seting_time  = '';
        $this->unit_time  = '';
        $this->selling_price  = '';
        $this->unit_cost  = '';
        $this->unit_price  = '';    
        $this->methods_units_id  = '';
    }

    public function storeTask($idLine  = null){
        $this->validate();

        if($this->idType == 'products_id'){
            $this->products_id = $idLine;
        }
        elseif($this->idType == 'quote_lines_id'){
            $this->quote_lines_id = $idLine;
        }
        elseif($this->idType == 'order_lines_id'){
            $this->order_lines_id = $idLine;
        }
        else{
            $this->products_id = $idLine;
            $this->order_lines_id = $idLine;
            $this->order_lines_id = $idLine;
        }

        $splitMethod = explode("-", $this->methods_services_id);
        $this->methods_services_id =  $splitMethod[0]; 
        $this->type =  $splitMethod[1]; 
        // Create Task
        $Task = Task::create(['label' => $this->label, 
                            'ordre' => $this->ordre, 
                            'quote_lines_id' => $this->quote_lines_id, 
                            'order_lines_id' => $this->order_lines_id, 
                            'products_id' => $this->products_id, 
                            'methods_services_id' => $this->methods_services_id,  
                            'component_id' => $this->component_id,  
                            'seting_time' => $this->seting_time,   
                            'unit_time' => $this->unit_time,   
                            'remaining_time', 
                            'progress', 
                            'status_id' => $this->status_id,   
                            'type' => $this->type,  
                            'delay',
                            'qty' => $this->qty,  
                            'qty_init' => $this->qty,  
                            'qty_aviable',
                            'unit_cost' => $this->unit_cost,  
                            'unit_price' => $this->unit_price,  
                            'methods_units_id' => $this->methods_units_id,  
                            'x_size', 
                            'y_size', 
                            'z_size', 
                            'x_oversize',
                            'y_oversize',
                            'z_oversize',
                            'diameter',
                            'diameter_oversize',
                            'to_schedule',
                            'material', 
                            'thickness', 
                            'weight', 
                            'quality_non_conformities_id',
                            'methods_tools_id']);

        if($this->idType == 'order_lines_id'){
            $OrderLine = OrderLines::find($this->order_lines_id);
            $OrderLine->tasks_status = 2;
            $OrderLine->save();
        }

        // Set Flash Message
        session()->flash('success','Task added Successfully');
        // Reset Form Fields After Creating line
        $this->resetFields();
    }

    public function editTaskLine($id){
        $Line = Task::findOrFail($id);
        $this->taskId = $id;
        $this->ordre = $Line->ordre;
        $this->methods_services_id = $Line->methods_services_id .'-'. $Line->type;
        $this->component_id = $Line->component_id;
        $this->label = $Line->label;
        $this->seting_time = $Line->seting_time;
        $this->unit_time = $Line->unit_time;
        $this->selling_price = $Line->selling_price;
        $this->qty = $Line->qty;
        $this->unit_cost = $Line->unit_cost;
        $this->unit_price = $Line->unit_price;     
        $this->methods_units_id = $Line->methods_units_id;
        $this->updateLines = true;
    }

    public function updateTask(){
        // Validate request
        $this->validate();

        $splitMethod = explode("-", $this->methods_services_id);
        $this->methods_services_id =  $splitMethod[0]; 
        $this->type =  $splitMethod[1]; 

        // Update line
        Task::find($this->taskId)->fill([
            'ordre' => $this->ordre, 
            'methods_services_id' => $this->methods_services_id,  
            'component_id' => $this->component_id,  
            'seting_time' => $this->seting_time,   
            'unit_time' => $this->unit_time,  
            'type' => $this->type, 
            'qty' => $this->qty,  
            'qty_init' => $this->qty,  
            'unit_cost' => $this->unit_cost,  
            'unit_price' => $this->unit_price,  
            'methods_units_id' => $this->methods_units_id, 
        ])->save();
        session()->flash('success','Task Updated Successfully');
    }

    public function duplicateLine($id){
        $Task = Task::findOrFail($id);
        $newTask = $Task->replicate();
        $newTask->ordre = $Task->ordre+1;
        $newTask->label = $Task->label ."#duplicate". $Task->id;
        $newTask->save();
    }
    

    public function destroyTaskLine($id){
        try{
            Task::findOrFail($id)->delete();
            session()->flash('success',"Task #". $id ." deleted Successfully !");
        }catch(\Exception $e){
            session()->flash('error',"Something goes wrong while deleting Task #". $id);
        }
    }
}
