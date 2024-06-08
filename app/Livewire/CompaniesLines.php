<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Companies\Companies;
use Illuminate\Support\Facades\Auth;
use App\Notifications\CompanieNotification;
use Illuminate\Support\Facades\Notification;

class CompaniesLines extends Component
{

    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'label'; // default sorting field
    public $sortAsc = true; // default sort direction

    public $Companies;

    public $LastCompanie = '1';

    public $userSelect = [];
    public $code, $label;
    public $website, $fbsite, $twittersite, $lkdsite;
    public $siren, $naf_code, $intra_community_vat;
    public $user_id;
    public $discount;
    public $account_general_customer, $account_auxiliary_customer, $account_general_supplier, $account_auxiliary_supplier, $recept_controle, $comment;

    public $client_type = 1;
    public $civility, $last_name; 

    // Validation Rules
    protected $rules = [
        'code' =>'required|unique:companies',
        'client_type' => 'required',
        'label'=>'required',
        'user_id'=>'required',
        'recept_controle'=>'required',
        'civility' => 'nullable|required_if:client_type,2',
        'last_name' => 'nullable|required_if:client_type,2',
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
        $this->user_id = Auth::id();
        $this->userSelect = User::select('id', 'name')->get();

        $this->LastCompanie =  Companies::orderBy('id', 'desc')->first();

        if($this->LastCompanie == Null){
            $this->code = "COMP-0";
        }
        else{
            $this->code = "COMP-". $this->LastCompanie->id;
        }
    }

    public function render()
    {
        
        return view('livewire.companies-lines', [
            'Companieslist' => Companies::where('label','like', '%'.$this->search.'%')->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->paginate(10),
        ]);
    }

    public function toggleClientType()
    {
        if($this->client_type == 1){
            $this->client_type = 1;
            $this->civility = null;
            $this->last_name = null;
        }
        else{
            $this->client_type = 2;
        }
    }

    public function storeCompany(){

        $this->validate();
            // Create Line
            $CompaniesCreated = Companies::create([
                'code'=>$this->code, 
                'client_type' => $this->client_type,
                'civility' => $this->civility,
                'label'=>$this->label,
                'last_name' => $this->last_name,
                'website'=>$this->website,
                'fbsite'=>$this->fbsite,
                'twittersite'=>$this->twittersite, 
                'lkdsite'=>$this->lkdsite, 
                'siren'=>$this->siren, 
                'naf_code'=>$this->naf_code, 
                'intra_community_vat'=>$this->intra_community_vat, 
                'discount'=>$this->discount,
                'user_id'=>$this->user_id,
                'account_general_customer'=>$this->account_general_customer,
                'account_auxiliary_customer'=>$this->account_auxiliary_customer,
                'account_general_supplier'=>$this->account_general_supplier,
                'account_auxiliary_supplier'=>$this->account_auxiliary_supplier,
                'recept_controle'=>$this->recept_controle,
                'comment'=>$this->comment,
            ]);

            // notification for all user in database
            $users = User::where('companies_notification', 1)->get();
            Notification::send($users, new CompanieNotification($CompaniesCreated));

            return redirect()->route('companies.show', ['id' => $CompaniesCreated->id])->with('success', 'Successfully created new company');
    }
}
