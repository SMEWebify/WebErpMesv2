<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Admin\Factory;
use App\Models\Products\Products;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;

class ProductsIndex extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    
    public $search = '';
    public $sortField = 'LABEL'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    public $userSelect = [];

    public $CODE;
    public $LABEL;  
    public $IND; 
    public $methods_services_id;  
    public $methods_families_id; 
    public $purchased; 
    public $purchased_price; 
    public $sold;
    public $selling_price;  
    public $methods_units_id; 
    public $material; 
    public $thickness;  
    public $weight;  
    public $x_size;  
    public $y_size;  
    public $z_size; 
    public $x_oversize; 
    public $y_oversize; 
    public $z_oversize; 
    public $comment; 
    public $tracability_type;
    public $qty_eco_min;
    public $qty_eco_max; 
    public $diameter; 
    public $diameter_oversize; 
    public $section_size; 
    
     // Validation Rules
    protected $rules = [
        'CODE' =>'required|unique:products',
        'LABEL'=>'required',
        'methods_services_id'=>'required',
        'methods_families_id'=>'required',
        'methods_units_id'=>'required',
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

    public function mount() 
    {
        $this->userSelect = User::select('id', 'name')->get();
    }

    public function render()
    {
        $Products = Products::where('LABEL','like', '%'.$this->search.'%')->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->paginate(15);
        $userSelect = User::select('id', 'name')->get();
        $ServicesSelect = MethodsServices::select('id', 'LABEL')->orderBy('ORDRE')->get();
        $UnitsSelect = MethodsUnits::select('id', 'LABEL', 'TYPE')->orderBy('LABEL')->get();
        $FamiliesSelect = MethodsFamilies::select('id', 'LABEL')->orderBy('LABEL')->get();
        $Factory = Factory::first();
        if(!$Factory){
            return redirect()->route('admin.factory')->with('danger', 'Please check factory information');
        }

        return view('livewire.products-index', [
            'Products' => $Products,
            'userSelect' => $userSelect,
            'ServicesSelect' => $ServicesSelect,
            'ServicesSelect' => $ServicesSelect,
            'UnitsSelect' => $UnitsSelect,
            'FamiliesSelect' => $FamiliesSelect,
            'Factory' => $Factory,
        ]);
    }

    public function storeProduct(){
        $this->validate();
            // Create Line
            $ProductsCreated = Products::create([
                                                    'CODE'=>$this->CODE, 
                                                    'LABEL'=>$this->LABEL,  
                                                    'IND'=>$this->IND, 
                                                    'methods_services_id'=>$this->methods_services_id,  
                                                    'methods_families_id'=>$this->methods_families_id,  
                                                    'purchased'=>$this->purchased,  
                                                    'purchased_price'=>$this->purchased_price,  
                                                    'sold'=>$this->sold,  
                                                    'selling_price'=>$this->selling_price,  
                                                    'methods_units_id'=>$this->methods_units_id,  
                                                    'material'=>$this->material,  
                                                    'thickness'=>$this->thickness,  
                                                    'weight'=>$this->weight,  
                                                    'x_size'=>$this->x_size,  
                                                    'y_size'=>$this->y_size,  
                                                    'z_size'=>$this->z_size,  
                                                    'x_oversize'=>$this->x_oversize, 
                                                    'y_oversize'=>$this->y_oversize, 
                                                    'z_oversize'=>$this->z_oversize, 
                                                    'comment'=>$this->comment, 
                                                    'tracability_type'=>$this->tracability_type, 
                                                    'qty_eco_min'=>$this->qty_eco_min, 
                                                    'qty_eco_max'=>$this->qty_eco_max, 
                                                    'diameter'=>$this->diameter, 
                                                    'diameter_oversize'=>$this->diameter_oversize, 
                                                    'section_size'=>$this->section_size, 
            ]);
            // Reset Form Fields After Creating line
            return redirect()->route('products.show', ['id' => $ProductsCreated->id])->with('success', 'Successfully created new product');
    }
}
