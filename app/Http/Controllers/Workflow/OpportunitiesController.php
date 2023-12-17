<?php

namespace App\Http\Controllers\Workflow;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Workflow\Quotes;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\Opportunities;
use App\Notifications\QuoteNotification;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Support\Facades\Notification;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Http\Requests\Workflow\UpdateOpportunityRequest;

class OpportunitiesController extends Controller
{
    /**
     * @return View
     */
    public function index()
    {   
        //Quote data for chart
        $data['opportunitiesDataRate'] = DB::table('opportunities')
                                    ->select('statu', DB::raw('count(*) as OpportunitiesCountRate'))
                                    ->groupBy('statu')
                                    ->get();


        return view('workflow/opportunities-index')->with('data',$data);
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

    /**
     * @param Request $request
     * @return View
     */
    public function storeQuote(Opportunities $id)
    {
        $LastQuote =  Quotes::orderBy('id', 'desc')->first();
        if($LastQuote == Null){
            $code = "QT-0";
            $label = "QT-0";
        }
        else{
            $code = "QT-". $LastQuote->id;
            $label = "QT-". $LastQuote->id;
        }

        $accounting_payment_conditions = AccountingPaymentConditions::select('id')->where( 'default', 1)->first(); 
        $accounting_payment_methods = AccountingPaymentMethod::select('id')->where( 'default', 1)->first(); 
        $accounting_deliveries = AccountingDelivery::select('id')->where( 'default', 1)->first(); 

        $accounting_payment_conditions = ($accounting_payment_conditions->id ?? 0); 
        $accounting_payment_methods = ($accounting_payment_methods->id  ?? 0);  
        $accounting_deliveries = ($accounting_deliveries->id  ?? 0);

        // Create Line
        $QuotesCreated = Quotes::create([
                                        'uuid'=> Str::uuid(),
                                        'code'=>$code,  
                                        'label'=>$label,  
                                        'companies_id'=>$id->companies_id,  
                                        'companies_contacts_id'=>$id->companies_contacts_id,    
                                        'companies_addresses_id'=>$id->companies_addresses_id,    
                                        'user_id'=>Auth::id(),     
                                        'opportunities_id'=>$id->id, 
                                        'accounting_payment_conditions_id'=>$accounting_payment_conditions,   
                                        'accounting_payment_methods_id'=>$accounting_payment_methods,   
                                        'accounting_deliveries_id'=>$accounting_deliveries,   
        ]);

        // notification for all user in database
        $users = User::where('quotes_notification', 1)->get();
        Notification::send($users, new QuoteNotification($QuotesCreated));

        //change statu companie
        Companies::where('id', $id->companies_id)->update(['statu_customer'=>2]);
        return redirect()->route('quotes.show', ['id' => $QuotesCreated->id])->with('success', 'Successfully created new quote');
    }
}

