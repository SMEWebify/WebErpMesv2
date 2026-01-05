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
use App\Services\DocumentCodeGenerator;
use App\Notifications\CompanieNotification;

class CompaniesLines extends Component
{

    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $sortField = 'label'; // default sorting field
    public $sortAsc = true; // default sort direction
    public $statusFilter = 'all';

    public $Companies;

    public $LastCompanie = null;

    public $userSelect = [];
    public $code, $label;
    public $user_id;
    public $comment;
    public $client_type = 1;
    public $civility, $last_name; 

    protected $notificationService;
    protected $documentCodeGenerator;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'label'],
        'sortAsc' => ['except' => true],
        'statusFilter' => ['as' => 'type', 'except' => 'all'],
    ];

    public function __construct()
    {
        // Resolve the service via the Laravel container
        $this->notificationService = App::make(NotificationService::class);
        $this->documentCodeGenerator = App::make(DocumentCodeGenerator::class);
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

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->user_id = Auth::id();
        $this->userSelect = User::select('id', 'name')->get();
        $this->LastCompanie = Companies::orderBy('id', 'desc')->first();
        $companieId = $this->LastCompanie ? $this->LastCompanie->id : 0;
        $this->code = $this->documentCodeGenerator->generateDocumentCode('company', $companieId);

        $availableFilters = ['all', 'client', 'prospect', 'supplier', 'client_supplier'];
        $requestedFilter = request()->query('type');

        if (in_array($requestedFilter, $availableFilters, true)) {
            $this->statusFilter = $requestedFilter;
        }
    }

    public function render()
    {
        $companiesQuery = Companies::where('label', 'like', '%' . $this->search . '%');

        switch ($this->statusFilter) {
            case 'client':
                $companiesQuery->where('statu_customer', 2)->where('statu_supplier', '!=', 2);
                break;
            case 'prospect':
                $companiesQuery->where('statu_customer', 3);
                break;
            case 'supplier':
                $companiesQuery->where('statu_supplier', 2)->where('statu_customer', '!=', 2);
                break;
            case 'client_supplier':
                $companiesQuery->where('statu_customer', 2)->where('statu_supplier', 2);
                break;
        }

        return view('livewire.companies-lines', [
            'Companieslist' => $companiesQuery->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->paginate(10),
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
