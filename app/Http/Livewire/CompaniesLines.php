<?php

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Models\Companies\Companies;
use Livewire\WithPagination;

class CompaniesLines extends Component
{

    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'LABEL'; // default sorting field
    public $sortAsc = true; // default sort direction

    public $Companies;
    public $userSelect = [];
    public $CODE, $LABEL;
    public $WEBSITE, $FBSITE, $TWITTERSITE, $LKDSITE;
    public $SIREN, $APE, $TVA_INTRA, $TVA_ID;
    public $statu_CLIENT;
    public $statu_FOUR;
    public $user_id;
    public $DISCOUNT;
    public $COMPTE_GEN_CLIENT, $COMPTE_AUX_CLIENT, $COMPTE_GEN_FOUR, $COMPTE_AUX_FOUR, $RECEPT_CONTROLE, $COMMENT;

    // Validation Rules
    protected $rules = [
        'CODE' =>'required|unique:companies',
        'LABEL'=>'required',
        'statu_CLIENT'=>'required',
        'statu_FOUR'=>'required',
        'user_id'=>'required',
        'RECEPT_CONTROLE'=>'required',
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
        
        return view('livewire.companies-lines', [
            'Companieslist' => Companies::where('LABEL','like', '%'.$this->search.'%')->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->paginate(10),
        ]);
    }

    public function resetFields(){
        $this->CODE = '';
        $this->product_id = '';
        $this->LABEL = '';
        $this->qty = '';
        $this->selling_price = '';
    }

    public function storeCompany(){

        $this->validate();
            // Create Line
            $CompaniesCreated = Companies::create([
                'CODE'=>$this->CODE, 
                'LABEL'=>$this->LABEL,
                'WEBSITE'=>$this->WEBSITE,
                'FBSITE'=>$this->FBSITE,
                'TWITTERSITE'=>$this->TWITTERSITE, 
                'LKDSITE'=>$this->LKDSITE, 
                'SIREN'=>$this->SIREN, 
                'APE'=>$this->APE, 
                'TVA_INTRA'=>$this->TVA_INTRA, 
                'TVA_ID'=>$this->TVA_ID, 
                'statu_CLIENT'=>$this->statu_CLIENT,
                'DISCOUNT'=>$this->DISCOUNT,
                'user_id'=>$this->user_id,
                'COMPTE_GEN_CLIENT'=>$this->COMPTE_GEN_CLIENT,
                'COMPTE_AUX_CLIENT'=>$this->COMPTE_AUX_CLIENT,
                'statu_FOUR'=>$this->statu_FOUR,
                'COMPTE_GEN_FOUR'=>$this->COMPTE_GEN_FOUR,
                'COMPTE_AUX_FOUR'=>$this->COMPTE_AUX_FOUR,
                'RECEPT_CONTROLE'=>$this->RECEPT_CONTROLE,
                'COMMENT'=>$this->COMMENT,
            ]);
            // Reset Form Fields After Creating line
            return redirect()->route('companies.show', ['id' => $CompaniesCreated->id])->with('success', 'Successfully created new company');
    }
}
