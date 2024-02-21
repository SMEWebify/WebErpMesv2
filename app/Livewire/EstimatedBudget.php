<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use App\Models\Admin\EstimatedBudgets;

class EstimatedBudget extends Component
{

    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'year'; // default sorting field
    public $sortAsc = true; // default sort direction
    public $updateLines = false;

    public $year;
    public $idEstimatedBudget, $amount1, $amount2, $amount3, $amount4, $amount5, $amount6, $amount7, $amount8, $amount9, $amount10, $amount11, $amount12; 
    
    public $Factory;

    // Validation Rules
  /*  protected $rules = [
        'year' =>'required|unique:estimated_budgets',
        'amount1'=>'required',
        'amount2'=>'required',
        'amount3'=>'required',
        'amount4'=>'required', 
        'amount5'=>'required', 
        'amount6'=>'required', 
        'amount7'=>'required', 
        'amount8'=>'required', 
        'amount9'=>'required', 
        'amount10'=>'required', 
        'amount11'=>'required',
        'amount12'=>'required',
    ];*/

    public function rules()
    {
        return [
            'year' =>['required',Rule::unique('estimated_budgets')->ignore($this->year, 'year')],
            'amount1'=>'required',
            'amount2'=>'required',
            'amount3'=>'required',
            'amount4'=>'required', 
            'amount5'=>'required', 
            'amount6'=>'required', 
            'amount7'=>'required', 
            'amount8'=>'required', 
            'amount9'=>'required', 
            'amount10'=>'required', 
            'amount11'=>'required',
            'amount12'=>'required',
        ];
    }

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


    

    public function render()
    {
        return view('livewire.estimated-budget', [
            'EstimatedBudgetlist' => EstimatedBudgets::where('year','like', '%'.$this->search.'%')->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->paginate(10),]);
    }

    public function resetFields(){
        $this->year = '';
        $this->amount1 = '';
        $this->amount2 = '';
        $this->amount3 = '';
        $this->amount4 = '';
        $this->amount5 = '';
        $this->amount6 = '';
        $this->amount7 = '';
        $this->amount8 = '';
        $this->amount9 = '';
        $this->amount10 = '';
        $this->amount11 = '';
        $this->amount12 = '';
    }

    public function storeEstimatedBudget(){

        $this->validate();
            // Create Line
            $EstimatedBudgetCreated = EstimatedBudgets::create([
                'year'=>$this->year, 
                'amount1'=>$this->amount1,
                'amount2'=>$this->amount2,
                'amount3'=>$this->amount3,
                'amount4'=>$this->amount4, 
                'amount5'=>$this->amount5, 
                'amount6'=>$this->amount6, 
                'amount7'=>$this->amount7, 
                'amount8'=>$this->amount8, 
                'amount9'=>$this->amount9, 
                'amount10'=>$this->amount10, 
                'amount11'=>$this->amount11,
                'amount12'=>$this->amount12,
            ]);
            // Reset Form Fields After Creating line
            session()->flash('success', 'Successfully created new task');
    }

    public function editEstimatedBudget($id){
        $Line = EstimatedBudgets::findOrFail($id);
        $this->idEstimatedBudget = $id;
        $this->year = $Line->year;
        $this->amount1 = $Line->amount1;
        $this->amount2 = $Line->amount2;
        $this->amount3 = $Line->amount3;
        $this->amount4 = $Line->amount4;
        $this->amount5 = $Line->amount5;
        $this->amount6 = $Line->amount6;
        $this->amount7 = $Line->amount7;
        $this->amount8 = $Line->amount8;
        $this->amount9 = $Line->amount9;
        $this->amount10 = $Line->amount10;
        $this->amount11 = $Line->amount11;
        $this->amount12 = $Line->amount12;
        $this->updateLines = true;
    }

    public function updateEstimatedBudget(){
        // Validate request
        $this->validate();
        // Update line
        EstimatedBudgets::find($this->idEstimatedBudget)->fill([
            'amount1'=>$this->amount1,
            'amount2'=>$this->amount2,
            'amount3'=>$this->amount3,
            'amount4'=>$this->amount4, 
            'amount5'=>$this->amount5, 
            'amount6'=>$this->amount6, 
            'amount7'=>$this->amount7, 
            'amount8'=>$this->amount8, 
            'amount9'=>$this->amount9, 
            'amount10'=>$this->amount10, 
            'amount11'=>$this->amount11,
            'amount12'=>$this->amount12,
        ])->save();
        session()->flash('success','Line Updated Successfully');
    }

    public function cancel()
    {
        $this->updateLines = false;
        $this->resetFields();
    }

    public function destroyEstimatedBudget($id){
        try{
            EstimatedBudgets::find($id)->delete();
            session()->flash('success',"Line deleted Successfully!!");
        }catch(\Exception $e){
            session()->flash('error',"Something goes wrong while deleting Line");
        }
    }
}
