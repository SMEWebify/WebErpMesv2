<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Models\Companies\Companies;
use App\Models\Workflow\Opportunities;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;

class OpportunitiesIndex extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    public $viewType = 'table'; // Defaults to 'table'

    public $search = '';
    public $sortField = 'created_at'; // default sorting field
    public $sortAsc = false; // default sort direction
    public $searchIdStatus = '';
    
    public $userSelect = [];
    
    public $companies_id; 
    public $companies_contacts_id;   
    public $companies_addresses_id;  
    public $leads_id;  
    public $user_id; 
    public $label; 
    public $budget = '0';  
    public $close_date; 
    public $statu = '1'; 
    public $probality = '50'; 
    public $comment;

    public $idCompanie = '';

    // Validation Rules
    protected $rules = [
        'companies_id'=>'required',
        'companies_contacts_id'=>'required',
        'companies_addresses_id'=>'required',
        'label'=>'required',
        'user_id'=>'required',
        'budget'=>'required',
        'probality'=>'required',
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

    public function changeView($view)
    {
        $this->viewType = $view;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        if(is_numeric($this->idCompanie)){
            $Opportunities = Opportunities::where('companies_id', $this->idCompanie)
                                            ->where('statu', 'like', '%'.$this->searchIdStatus.'%')
                                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                            ->paginate(15);
        }
        else{
            $Opportunities = Opportunities::where('label','like', '%'.$this->search.'%')
                                            ->where('statu', 'like', '%'.$this->searchIdStatus.'%')
                                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                            ->paginate(15);
        }

        $CompanieSelect = Companies::select('id', 'code','client_type','civility','label','last_name')->where('active', 1)->get();
        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->where('companies_id', $this->companies_id)->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->where('companies_id', $this->companies_id)->get();
        $UsersSelect = User::all();

        return view('livewire.opportunities-index')->with([
            'Opportunities' => $Opportunities,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'UsersSelect' => $UsersSelect,
        ]);
    }

    public function storeOpportunity(){
        $this->validate();

        // Create opportunity
        $OpportunityCreated = Opportunities::create([
                        'uuid'=> Str::uuid(),
                        'companies_id'=>$this->companies_id,  
                        'companies_contacts_id'=>$this->companies_contacts_id,    
                        'companies_addresses_id'=>$this->companies_addresses_id,   
                        'user_id'=>$this->user_id,   
                        'label'=>$this->label,   
                        'budget'=>$this->budget,   
                        'probality'=>$this->probality, 
                        'comment'=>$this->comment, 
        ]);
        
        return redirect()->route('opportunities.show', ['id' => $OpportunityCreated->id])->with('success', 'Successfully created new opportunity');
    }
}
