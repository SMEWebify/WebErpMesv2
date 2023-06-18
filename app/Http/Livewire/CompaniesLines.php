<?php

namespace App\Http\Livewire;

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
    public $userSelect = [];
    public $code, $label;
    public $website, $fbsite, $twittersite, $lkdsite;
    public $siren, $naf_code, $intra_community_vat;
    public $user_id;
    public $discount;
    public $account_general_customer, $account_auxiliary_customer, $account_general_supplier, $account_auxiliary_supplier, $recept_controle, $comment;

    // Validation Rules
    protected $rules = [
        'code' =>'required|unique:companies',
        'label'=>'required',
        'user_id'=>'required',
        'recept_controle'=>'required',
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
    }

    public function render()
    {
        return view('livewire.companies-lines', [
            'Companieslist' => Companies::where('label','like', '%'.$this->search.'%')->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->paginate(10),
        ]);
    }

    public function storeCompany(){

        $this->validate();
            // Create Line
            $CompaniesCreated = Companies::create([
                'code'=>$this->code, 
                'label'=>$this->label,
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
