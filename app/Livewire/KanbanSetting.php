<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Planning\Status;

class KanbanSetting extends Component
{

    public $KanbanSettingViewLines;
    public $idStatu, $title, $order;

    // Validation Rules
    protected $rules = [
        'title'=>'required|unique:statuses',
        'order'=>'required',
    ];

    public function render()
    {
        $KanbanSettingViewLines = $this->KanbanSettingViewLines = Status::orderBy('order', 'ASC')->get();
        return view('livewire.kanban-setting', [
            'KanbanSettingViewLines' => $KanbanSettingViewLines,
        ]);
    }

    public function resetFields(){
        $this->title = '';
        $this->order = $this->order+1;
    }

    public function storeKanbanStatuLine(){
        $this->validate();

        // Create Line
        Status::create([
                'title'=>$this->title,
                'order'=>$this->order,
        ]);
        // Set Flash Message
        session()->flash('success','Line added Successfully');
        // Reset Form Fields After Creating line
        $this->resetFields();
    }

    public function upKanban($idStatu){
        // Update line
        Status::find($idStatu)->increment('order',1);;
        session()->flash('success','Line Updated Successfully');
    }

    public function downKanban($idStatu){
        // Update line
        Status::find($idStatu)->decrement('order',1);;
        session()->flash('success','Line Updated Successfully');
    }

    public function destroyKanban($id){
        try{
            Status::find($id)->delete();
            session()->flash('success',"Line deleted Successfully!!");
        }catch(\Exception $e){
            session()->flash('error',"Something goes wrong while deleting Line");
        }
    }
}
