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
use App\Models\Planning\SubAssembly;

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
    public $subAssemblyId;
    public $ordre = 1;
    public $subAssemblyOrdre = 1;
    public $label;
    public $subAssemblylabel;
    public $methods_services_id;
    public $component_id;
    public $subAssemblyComponentId;
    public $type;
    public $qty;
    public $subAssemblyQty = 0;
    public $seting_time = 0;
    public $unit_time = 0;
    public $unit_cost = 0;
    public $unit_price = 0;
    public $subAssemblyUnit_price = 0;
    public $methods_units_id = 0;

    Private $quote_lines_id;
    Private $order_lines_id;
    Private $products_id;

    public $updateLines = false;

    public $Factory = [];
    public $UnitsSelect = [];
    public $ProductSelect  = [];
    public $ComponentSelect  = [];




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
        $Service = MethodsServices::select('id', 'ordre', 'label')->where('id', $this->methods_services_id)->get();
        if(count($Service) > 0){
            $this->label =  $Service[0]->label;
            $this->ordre =  $Service[0]->ordre;
        }else{
            $this->label = '';
            $this->ordre =10;
        }
    }

    public function ChangeSubAssemblyCodelabel()
    {
        $Product = Products::select('id',  'label', 'selling_price')->where('id', $this->subAssemblyComponentId)->get();
        if(count($Product) > 0){
            $this->subAssemblylabel =  $Product[0]->label;
            $this->subAssemblyUnit_price =  $Product[0]->selling_price;
            if($Product[0]->selling_price == null){
                $this->subAssemblyUnit_price =0;
            }
        }else{
            $this->subAssemblylabel = '';
            $this->subAssemblyUnit_price =0;
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
        $this->ProductSelect = Products::select('id', 'code','label', 'methods_services_id')->with('service')->whereRelation('service', 'type', 2)
                                                                                                            ->OrwhereRelation('service', 'type', 3)
                                                                                                            ->OrwhereRelation('service', 'type', 4)
                                                                                                            ->OrwhereRelation('service', 'type', 5)
                                                                                                            ->OrwhereRelation('service', 'type', 6)
                                                                                                            ->get();
        $this->ComponentSelect = Products::select('id', 'code','label', 'methods_services_id')->with('service')->whereRelation('service', 'type', 8)->get();
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
            $this->qty = 1 ;
        }
        elseif($this->idType == 'order_lines_id'){
            $Line = OrderLines::findOrFail($this->idLine);
            $this->qty = 1 ;
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
        $this->unit_cost  = '';
        $this->unit_price  = '';    
        $this->methods_units_id  = '';
    }

    public function storeTask($idLine  = null){
        $this->validate();
        //custom rule if is BOM for check if service or component is not empty
        if($this->TaskType == 'BOM'&& (is_null($this->component_id) || $this->component_id == __('general_content.select_component_trans_key') || empty($this->component_id))){

            // Set Flash Message
            session()->flash('error', 'Please select Component.');
        }
        elseif($this->methods_services_id == __('general_content.select_service_trans_key') || is_null($this->methods_services_id)){
            // Set Flash Message
            session()->flash('error', 'Please select service.');
        }
        else{

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

    public function storeSubAssembly($idLine  = null){
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
                session()->flash('error',"Something goes wrong ");
            }

            // Validate request
            $validatedData = $this->validate([
                'subAssemblyQty' => 'required',
                'subAssemblyComponentId' => 'required',
            ]);

            // Create Task
            $Task = SubAssembly::create(['ordre' => $this->subAssemblyOrdre, 
                                        'quote_lines_id' => $this->quote_lines_id, 
                                        'order_lines_id' => $this->order_lines_id, 
                                        'products_id' => $this->products_id, 
                                        'child_id' => $this->subAssemblyComponentId, 
                                        'qty' => $this->subAssemblyQty,
                                        'unit_price' => $this->subAssemblyUnit_price]);
            // Set Flash Message
            session()->flash('success','Sub assembly added Successfully');
    }

    public function editSubAssemblyLine($id){
        $Line = SubAssembly::findOrFail($id);
        $this->subAssemblyId = $id;
        $this->subAssemblyOrdre = $Line->ordre;
        $this->subAssemblyComponentId = $Line->child_id;
        $this->subAssemblyQty = $Line->qty;
        $this->subAssemblyUnit_price = $Line->unit_price;
        $this->updateLines = true;
    }

    public function updateSubAssembly(){
        // Validate request
        $validatedData = $this->validate([
            'subAssemblyQty' => 'required',
            'subAssemblyComponentId' => 'required',
        ]);

        // Update line
        SubAssembly::find($this->subAssemblyId)->fill([
            'ordre' => $this->subAssemblyOrdre, 
            'child_id' => $this->subAssemblyComponentId, 
            'qty' => $this->subAssemblyQty,  
            'unit_price' => $this->subAssemblyUnit_price,  
        ])->save();
        session()->flash('success','Sub assembly line updated Successfully');
    }

    public function duplicateSubAssemblyLine($id){
        $SubAssembly = SubAssembly::findOrFail($id);
        $newSubAssembly = $SubAssembly->replicate();
        $newSubAssembly->ordre = $SubAssembly->ordre+1;
        $newSubAssembly->save();
    }
    

    public function destroySubAssemblyLine($id){
        try{
            SubAssembly::findOrFail($id)->delete();
            session()->flash('success',"Sub assembly #". $id ." deleted Successfully !");
        }catch(\Exception $e){
            session()->flash('error',"Something goes wrong while deleting sub assembly #". $id);
        }
    }
}
