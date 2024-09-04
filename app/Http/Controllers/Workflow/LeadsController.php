<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Support\Str;
use App\Models\Workflow\Leads;
use App\Traits\NextPreviousTrait;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\Opportunities;
use App\Http\Requests\Workflow\UpdateLeadRequest;

class LeadsController extends Controller
{
    use NextPreviousTrait;
    
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }
    
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {    
        return view('workflow/leads-index');
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
     * @return \Illuminate\Contracts\View\View
     */
    public function storeOpportunity($id){
        
        Leads::where('id',$id)->update(['statu'=>'4']);

        $LeadToStore = Leads::findorfail($id);
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
