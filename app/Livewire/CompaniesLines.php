<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Models\Companies\Companies;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use App\Notifications\CompanieNotification;

class CompaniesLines extends Component
{

    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'label'; // default sorting field
    public $sortAsc = true; // default sort direction

    public $Companies;

    public $LastCompanie = null;

    public $userSelect = [];
    public $code, $label;
    public $user_id;
    public $comment;
    public $client_type = 1;
    public $civility, $last_name; 

    protected $notificationService;

    public function __construct()
    {
        // Résoudre le service via le container Laravel
        $this->notificationService = App::make(NotificationService::class);
    }

    // Validation Rules
    protected $rules = [
        'code' =>'required|unique:companies',
        'client_type' => 'required',
        'label'=>'required',
        'user_id'=>'required',
        'civility' => 'nullable|required_if:client_type,2',
        'last_name' => 'nullable|required_if:client_type,2',
    ];

    public function sortBy($field)
    {
        $this->sortAsc = $this->sortField === $field ? !$this->sortAsc : true;
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
        $this->LastCompanie = Companies::orderBy('id', 'desc')->first();
        $this->code = $this->generateCompanyCode($this->LastCompanie);
    }

    /**
     * Generate company code based on the last company.
     *
     * @param \App\Models\Companies\Companies|null $lastCompany
     * @return string
     */
    private function generateCompanyCode($lastCompany)
    {
        return $lastCompany === null ? "COMP-0" : "COMP-" . $lastCompany->id;
    }

    public function render()
    {
        
        return view('livewire.companies-lines', [
            'Companieslist' => Companies::where('label','like', '%'.$this->search.'%')->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->paginate(10),
        ]);
    }

    public function toggleClientType()
    {
        if ($this->client_type == 1) {
            $this->civility = null;
            $this->last_name = null;
        }
        $this->client_type = $this->client_type == 1 ? 1 : 2;
    }

    public function storeCompany(){

        $this->validate();
            // Create Line
        $CompaniesCreated = Companies::create([
            'uuid'=> Str::uuid(),
            'code'=>$this->code, 
            'client_type' => $this->client_type,
            'civility' => $this->civility,
            'label'=>$this->label,
            'last_name' => $this->last_name,
            'user_id'=>$this->user_id,
            'comment'=>$this->comment,
        ]);

        // notification
        $this->notificationService->sendNotification(CompanieNotification::class, $CompaniesCreated, 'companies_notification');


        return redirect()->route('companies.show', ['id' => $CompaniesCreated->id])->with('success', 'Successfully created new company');
    }
}
