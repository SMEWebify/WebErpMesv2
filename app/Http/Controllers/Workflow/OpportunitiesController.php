<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Http\Controllers\Controller;
use App\Models\Workflow\Opportunities;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Http\Requests\Workflow\UpdateOpportunityRequest;

class OpportunitiesController extends Controller
{
    /**
     * @return View
     */
    public function index()
    {    
        return view('workflow/opportunities-index');
    }

    /**
     * @param $id
     * @return View
     */
    public function show(Opportunities $id)
    {  
        $CompanieSelect = Companies::select('id', 'code','label')->where('active', 1)->get();
        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->get();
        $previousUrl = route('opportunities.show', ['id' => $id->id-1]);
        $nextUrl = route('opportunities.show', ['id' => $id->id+1]);

        //DB information mustn't be empty.
        $Factory = Factory::first();
        if(!$Factory){
            return redirect()->route('admin.factory')->with('error', 'Please check factory information');
        }

        return view('workflow/opportunities-show', [
            'Opportunity' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'Factory' => $Factory,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
        ]);
    }

    /**
     * @param Request $request
     * @return View
     */
    public function update(UpdateOpportunityRequest $request)
    {
        $Opportunity = Opportunities::findOrFail($request->id);
        $Opportunity->label=$request->label;
        $Opportunity->companies_id=$request->companies_id;
        $Opportunity->companies_contacts_id=$request->companies_contacts_id;
        $Opportunity->companies_addresses_id=$request->companies_addresses_id;
        $Opportunity->budget=$request->budget;
        $Opportunity->probality=$request->probality;
        $Opportunity->comment=$request->comment;
        $Opportunity->save();
        
        return redirect()->route('opportunities.show', ['id' =>  $Opportunity->id])->with('success', 'Successfully updated opportunity');
    }
}

