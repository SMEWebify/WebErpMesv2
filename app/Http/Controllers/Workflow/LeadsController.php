<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Workflow\Leads;
use App\Services\LeadsKPIService;
use App\Traits\NextPreviousTrait;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\Opportunities;
use App\Http\Requests\Workflow\UpdateLeadRequest;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;

class LeadsController extends Controller
{
    use NextPreviousTrait;
    
    protected $SelectDataService;
    protected $leadsKPIService;

    public function __construct(SelectDataService $SelectDataService, 
                                LeadsKPIService $LeadsKPIService)
    {
        $this->SelectDataService = $SelectDataService; 
        $this->leadsKPIService = $LeadsKPIService;
    }
    
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $leadsCount         = $this->leadsKPIService->getLeadsCount();
        $dataRate           = $this->leadsKPIService->getLeadsDataRate();
        $leadByCompany      = $this->leadsKPIService->getLeadsByCompany();
        $leadsCountByUser   = $this->leadsKPIService->getLeadsCountByUser();
        $leadsCountByPriority = $this->leadsKPIService->getLeadsCountByPriority();

        return view('workflow/leads-index', [
            'kpi' => ['count' => $leadsCount],
            'chart' => $dataRate->map(fn ($r) => [
                'statu' => $r->statu,
                'count' => $r->leadsCountRate,
            ])->values()->all(),
            'byCompany' => $leadByCompany->map(fn ($o) => [
                'company_label' => $o->companie?->label ?? '—',
                'company_url'   => route('companies.show', ['id' => $o->companies_id]),
                'count'         => $o->count,
            ])->values()->all(),
            'byPriority' => $leadsCountByPriority->map(fn ($r) => [
                'priority' => $r->priority,
                'count'    => $r->leadsCount,
            ])->values()->all(),
            'byUser' => $leadsCountByUser->map(fn ($leads) => [
                'user_name' => $leads->first()->UserManagement?->name ?? '—',
                'counts'    => $leads->pluck('total', 'statu')->all(),
            ])->values()->all(),
        ]);
    }

    // -------------------------------------------------------------------------
    // JSON endpoints for the React LeadsIndex component
    // -------------------------------------------------------------------------

    public function listJson(Request $request)
    {
        $search     = $request->get('search', '');
        $statuses   = array_filter(array_map('intval', (array) $request->get('statuses', [])));
        $priorities = array_filter(array_map('intval', (array) $request->get('priorities', [])));
        $sortField  = $request->get('sort', 'created_at');
        $sortAsc    = $request->boolean('asc', false);
        $companyId  = $request->get('company_id');

        $allowed = ['source', 'campaign', 'priority', 'statu', 'created_at', 'companie'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }

        $dir   = $sortAsc ? 'asc' : 'desc';
        $query = Leads::with(['companie:id,label,code', 'contact:id,first_name,name', 'UserManagement:id,name'])
            ->when($search, fn ($q) => $q->where(fn ($q2) =>
                $q2->where('source', 'like', '%'.$search.'%')
                   ->orWhere('campaign', 'like', '%'.$search.'%')
            ))
            ->when($statuses,   fn ($q) => $q->whereIn('statu', $statuses))
            ->when($priorities, fn ($q) => $q->whereIn('priority', $priorities))
            ->when($companyId,  fn ($q) => $q->where('companies_id', $companyId));

        match ($sortField) {
            'companie' => $query->orderByRaw("(SELECT label FROM companies WHERE companies.id = leads.companies_id) {$dir}"),
            default    => $query->orderBy($sortField, $dir),
        };

        $items = $query->paginate(15);

        return response()->json([
            'data' => $items->map(fn ($l) => [
                'id'         => $l->id,
                'source'     => $l->source,
                'campaign'   => $l->campaign,
                'priority'   => $l->priority,
                'statu'      => $l->statu,
                'created_at' => $l->created_at?->format('d/m/Y'),
                'companie'   => $l->companie        ? ['id' => $l->companie->id,        'label' => $l->companie->label] : null,
                'contact'    => $l->contact         ? ['id' => $l->contact->id,         'name'  => trim($l->contact->first_name.' '.$l->contact->name)] : null,
                'user'       => $l->UserManagement  ? ['id' => $l->UserManagement->id,  'name'  => $l->UserManagement->name] : null,
                'url'        => route('leads.show', ['id' => $l->id]),
            ]),
            'meta' => [
                'total'        => $items->total(),
                'per_page'     => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
            ],
        ]);
    }

    public function kanbanJson()
    {
        $items = Leads::with(['companie:id,label', 'contact:id,first_name,name', 'UserManagement:id,name'])->get();

        return response()->json($items->map(fn ($l) => [
            'id'       => $l->id,
            'source'   => $l->source,
            'campaign' => $l->campaign,
            'priority' => $l->priority,
            'statu'    => $l->statu,
            'companie' => $l->companie       ? ['id' => $l->companie->id,       'label' => $l->companie->label] : null,
            'contact'  => $l->contact        ? ['id' => $l->contact->id,        'name'  => trim($l->contact->first_name.' '.$l->contact->name)] : null,
            'user'     => $l->UserManagement ? ['id' => $l->UserManagement->id, 'name'  => $l->UserManagement->name] : null,
            'url'      => route('leads.show', ['id' => $l->id]),
        ]));
    }

    public function kanbanMoveJson(Request $request, int $id)
    {
        abort_unless(auth()->check(), 403);
        $validated = $request->validate(['statu' => 'required|integer|between:1,5']);
        $lead = Leads::findOrFail($id);
        $lead->update(['statu' => $validated['statu']]);
        return response()->json(['ok' => true]);
    }

    public function storeJson(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'companies_id'           => 'required|integer',
            'companies_contacts_id'  => 'required|integer',
            'companies_addresses_id' => 'required|integer',
            'user_id'                => 'required|integer',
            'source'                 => 'required|string|max:255',
            'priority'               => 'required|integer|between:1,4',
            'campaign'               => 'nullable|string|max:255',
            'comment'                => 'nullable|string',
        ]);

        $lead = Leads::create($validated);

        return response()->json([
            'redirect' => route('leads.show', ['id' => $lead->id]),
        ], 201);
    }

    public function selectDataJson()
    {
        return response()->json([
            'companies' => $this->SelectDataService->getCompanies()->map(fn ($c) => [
                'id'    => $c->id,
                'label' => $c->label ?? $c->last_name,
                'code'  => $c->code,
            ]),
            'users' => User::select('id', 'name')->get(),
        ]);
    }

    public function addressesJson(int $companyId)
    {
        return response()->json(
            CompaniesAddresses::select('id', 'label', 'adress')
                ->where('companies_id', $companyId)
                ->get()
        );
    }

    public function contactsJson(int $companyId)
    {
        return response()->json(
            CompaniesContacts::select('id', 'first_name', 'name')
                ->where('companies_id', $companyId)
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'name' => trim($c->first_name.' '.$c->name)])
        );
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Leads $id)
    {  
        $CompanieSelect = $this->SelectDataService->getCompanies();
        $AddressSelect = $this->SelectDataService->getAddress($id->companies_id);
        $ContactSelect = $this->SelectDataService->getContact($id->companies_id);
        $userSelect = $this->SelectDataService->getUsers();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Leads(), $id->id);

        return view('workflow/leads-show', [
            'Lead' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
            'userSelect' =>  $userSelect,
        ]);
    }

    /**
     * Update the specified lead in storage.
     *
     * @param \App\Http\Requests\Workflow\UpdateLeadRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateLeadRequest $request)
    {
        $lead = Leads::findOrFail($request->id);
        $lead->update($request->validated()); 
        $lead->save();
        
        return redirect()->route('leads.show', ['id' =>  $lead->id])
                            ->with('success', 'Successfully updated lead');
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeOpportunity($id){
        
        // Update lead status
        Leads::where('id', $id)->update(['statu' => '4']);

        // Retrieve the lead
        $LeadToStore = Leads::findOrFail($id);

        // Create opportunity
        $OpportunityCreated = Opportunities::create([
                        'uuid'=> Str::uuid(),
                        'companies_id'=>$LeadToStore->companies_id,  
                        'companies_contacts_id'=>$LeadToStore->companies_contacts_id,    
                        'companies_addresses_id'=>$LeadToStore->companies_addresses_id,   
                        'user_id'=>Auth::id(),    
                        'label'=>'#LEAD'. $id .'#'. $LeadToStore->source .'#'. $LeadToStore->campaign ,
                        'leads_id'=>$id, 
                        'budget'=>0,   
                        'probality'=>50, 
        ]);
        
        return redirect()->route('opportunities.show', ['id' => $OpportunityCreated->id])->with('success', 'Successfully created new opportunity');
    }
}
