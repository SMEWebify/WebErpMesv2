<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Models\Workflow\Leads;
use App\Models\Companies\Companies;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\Opportunities;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;

class LeadsIndex extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $viewType = 'table'; // Defaults to 'table'

    public $search = '';
    public $sortField = 'statu'; // default sorting field
    public $sortAsc = true; // default sort direction
    
    private $Leadslist;
    
    public $id;
    public $companies_id='';
    public $companies_contacts_id;   
    public $companies_addresses_id; 
    public $user_id;
    public $source;
    public $priority = 3;
    public $campaign;
    public $comment;

    public $idCompanie = '';
    
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

    // Validation Rules
    protected $rules = [
        'companies_id'=>'required',
        'companies_contacts_id'=>'required',
        'companies_addresses_id'=>'required',
        'user_id'=>'required',
        'source'=>'required',
        'priority'=>'required',
    ];

    public function render()
    {
        if(is_numeric($this->idCompanie)){
            $Leadslist = $this->Leadslist = Leads::where('companies_id', $this->idCompanie)
                                                ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                                ->paginate(15);
        }
        else{
            $Leadslist = $this->Leadslist = Leads::orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                                ->paginate(15);
        }
        
        $CompanieSelect = Companies::select('id', 'code','client_type','civility','label','last_name')->where('active', 1)->get();
        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->where('companies_id',  $this->companies_id)->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->where('companies_id', $this->companies_id)->get();
        $UsersSelect = User::all();

        return view('livewire.leads-index', [
            'Leadslist' => $Leadslist,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'UsersSelect' => $UsersSelect,
        ]);

    }

    public function storeLead(){
        $this->validate();

        // Create lead
        Leads::create([
                        'companies_id'=>$this->companies_id,  
                        'companies_contacts_id'=>$this->companies_contacts_id,    
                        'companies_addresses_id'=>$this->companies_addresses_id,   
                        'user_id'=>$this->user_id,   
                        'source'=>$this->source,   
                        'priority'=>$this->priority,   
                        'campaign'=>$this->campaign, 
                        'comment'=>$this->comment, 
        ]);

        
        return redirect()->route('leads')->with('success', 'New lead add successfully');
    }

    public function inProgress($leadId){
        Leads::where('id',$leadId)->update(['statu'=>'3']);
    }

    public function lost($leadId){
        Leads::where('id',$leadId)->update(['statu'=>'5']);
    }

    public function storeOpportunity($leadId){
        
        Leads::where('id',$leadId)->update(['statu'=>'4']);

        $LeadToStore = Leads::find($leadId);

        // Create opportunity
        $OpportunityCreated = Opportunities::create([
                        'uuid'=> Str::uuid(),
                        'companies_id'=>$LeadToStore->companies_id,  
                        'companies_contacts_id'=>$LeadToStore->companies_contacts_id,    
                        'companies_addresses_id'=>$LeadToStore->companies_addresses_id,   
                        'user_id'=>Auth::id(),    
                        'label'=>'#LEAD'. $leadId .'#'. $LeadToStore->source .'#'. $LeadToStore->campaign ,
                        'leads_id'=>$leadId, 
                        'budget'=>0,   
                        'probality'=>50, 
        ]);
        
        return redirect()->route('opportunities.show', ['id' => $OpportunityCreated->id])->with('success', 'Successfully created new opportunity');
    }
}
