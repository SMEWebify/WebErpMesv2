<?php

namespace App\Http\Controllers\Workflow;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Admin\Factory;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Traits\NextPreviousTrait;
use App\Models\Workflow\Deliverys;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Opportunities;
use App\Notifications\QuoteNotification;
use Illuminate\Support\Facades\Notification;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Workflow\OpportunitiesEventsLogs;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Workflow\OpportunitiesActivitiesLogs;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Http\Requests\Workflow\UpdateOpportunityRequest;

class OpportunitiesController extends Controller
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
        //Quote data for chart
        $data['opportunitiesDataRate'] = DB::table('opportunities')
                                    ->select('statu', DB::raw('count(*) as OpportunitiesCountRate'))
                                    ->groupBy('statu')
                                    ->get();

        $recentActivities = OpportunitiesActivitiesLogs::latest()->take(5)->get();


        $opportunitiesByAmount = Opportunities::select('probality', DB::raw('SUM(budget) as total_amount'))
                                            ->groupBy('probality')
                                            ->get();

        $opportunitiesByCloseDate = Opportunities::select('close_date', DB::raw('count(*) as count'))
                                                    ->groupBy('close_date')
                                                    ->get();

        $opportunitiesByCompany = Opportunities::with('companie')
                                ->select('companies_id', DB::raw('count(*) as count'))
                                ->groupBy('companies_id')
                                ->get();

        $opportunitiesCount = Opportunities::count();


        $quotesWon = Quotes::where('statu', 3)->whereNotNull('opportunities_id')->get();
                                $totalQuotesWon = $quotesWon->sum(function ($quote) {
                                    return $quote->getTotalPriceAttribute();
                                });

        $quotesLost = Quotes::where('statu', 4)->whereNotNull('opportunities_id')->get();
                            $totalQuotesLost = $quotesLost->sum(function ($quote) {
                                return $quote->getTotalPriceAttribute();
                            });

        return view('workflow/opportunities-index', compact( 'recentActivities',  
                                                            'opportunitiesByAmount', 
                                                            'opportunitiesByCloseDate', 
                                                            'opportunitiesByCompany', 
                                                            'totalQuotesWon', 
                                                            'totalQuotesLost',
                                                            'opportunitiesCount'
                                                        ))->with('data',$data);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Opportunities $id)
    {  
        $CompanieSelect = $this->SelectDataService->getCompanies();
        $AddressSelect = $this->SelectDataService->getAddress();
        $ContactSelect = $this->SelectDataService->getContact();
        $Activities = OpportunitiesActivitiesLogs::where('opportunities_id', $id->id)->orderBy('id')->get();
        $Events = OpportunitiesEventsLogs::where('opportunities_id', $id->id)->orderBy('id')->get();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Opportunities(), $id->id);
        $Factory = Factory::first();

        // Récupérer l'opportunité avec les relations nécessaires
        $opportunite = $id->load('lead', 'activities', 'events', 'quotes');

        // Organiser les données pour la timeline
        $timelineData = [];

        // Ajouter les leads s'il y en a
        if ($opportunite->lead) {
            $timelineData[] = [
                'date' => $opportunite->lead->created_at->format('d M Y'),
                'icon' => 'fas fa-globe bg-info',
                'content' =>  "Lead " . $opportunite->lead->campaign,
                'details' => $opportunite->lead->GetPrettyCreatedAttribute(),
            ];
        }

        // Ajouter les événements s'il y en a
        foreach ($opportunite->events as $event) {

            if($event->type  == 1) $type = __('general_content.activity_maketing_trans_key') ;
            if($event->type  == 2) $type = __('general_content.internal_meeting_trans_key') ;
            if($event->type  == 3) $type = __('general_content.onsite_visite_trans_key') ;
            if($event->type  == 4) $type = __('general_content.sales_meeting_trans_key') ;

            $timelineData[] = [
                'date' => $event->created_at->format('d M Y'),
                'icon' => 'fas fa-calendar-alt  bg-danger',
                'content' => $event->label ." - " .  $type,
                'details' => $event->GetPrettyCreatedAttribute(),
            ];
        }

        // Ajouter les activités s'il y en a
        foreach ($opportunite->activities as $activity) {
            if($activity->type  == 1) $type = __('general_content.activity_maketing_trans_key');
            if($activity->type  == 2) $type = __('general_content.email_send_trans_key');
            if($activity->type  == 3) $type = __('general_content.pre_sakes_aactivity_trans_key');
            if($activity->type  == 4) $type = __('general_content.sales_activity_trans_key');
            if($activity->type  == 5) $type = __('general_content.sales_telephone_call_trans_key');

            $timelineData[] = [
                'date' => $activity->created_at->format('d M Y'),
                'icon' => 'fas fa-comments bg-warning',
                'content' => $activity->label ." - " .  $type,
                'details' => $activity->GetPrettyCreatedAttribute(),
            ];
        }

        // Ajouter les devis s'il y en a
        foreach ($opportunite->quotes as $quote) {

            // Ajouter les commandes issues des devis
            $orders = Orders::where('quotes_id', $quote->id)->get();

            foreach ($orders as $order) {

                // Ajouter les lignes de commande
                $orderLines = OrderLines::where('orders_id', $order->id)->get();

                // Tableau pour suivre les IDs des livraisons déjà ajoutées à la timeline
                $addedDeliveries = [];

                foreach ($orderLines as $orderLine) {
                    
                    // Ajouter les livraisons associées à la ligne de commande (uniquement si elles n'ont pas déjà été ajoutées)
                    $deliveryLines = DeliveryLines::where('order_line_id', $orderLine->id)->get();

                    foreach ($deliveryLines as $deliveryLine) {
                        $deliveryId = $deliveryLine->deliverys_id;

                        if (!in_array($deliveryId, $addedDeliveries)) {
                            $delivery = Deliverys::find($deliveryId);

                            if ($delivery) {
                                $timelineData[] = [
                                    'date' => $delivery->created_at->format('d M Y'),
                                    'icon' => 'fas fa-truck bg-warning',
                                    'content' => __('general_content.delivery_notes_trans_key') ." - ".  $delivery->label ,
                                    'details' => $delivery->GetPrettyCreatedAttribute(),
                                ];

                                // Ajouter l'ID de la livraison à la liste des livraisons déjà ajoutées
                                $addedDeliveries[] = $deliveryId;
                            }
                        }
                    }
                }

                $timelineData[] = [
                    'date' => $order->created_at->format('d M Y'),
                    'icon' => 'fas fa-shopping-cart bg-secondary',
                    'content' => __('general_content.order_trans_key') ." ". $order->label . " - ". __('general_content.total_price_trans_key') ." : ". $order->getTotalPriceAttribute() . " ". $Factory->curency,
                    'details' => $order->GetPrettyCreatedAttribute(),
                ];
                
               
            }

            $timelineData[] = [
                'date' => $quote->created_at->format('d M Y'),
                'icon' => 'fas fa-calculator  bg-success', 
                'content' => __('general_content.quote_trans_key') ." ". $quote->label . " - ". __('general_content.total_price_trans_key') ." : ". $quote->getTotalPriceAttribute() . " ". $Factory->curency,
                'details' => $quote->GetPrettyCreatedAttribute(),
            ];
        }

        // Ajouter l'opportunité initiale
        $timelineData[] = [
            'date' => $opportunite->created_at->format('d M Y'),
            'icon' => 'fa fa-tags bg-primary',
            'content' => "Opportunité créée",
            'details' => $opportunite->GetPrettyCreatedAttribute(),
        ];

         // Trier le tableau par date
        usort($timelineData, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
 
         // Passer les données à la vue

        return view('workflow/opportunities-show', [
            'Opportunity' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
            'ActivitiesList' =>  $Activities,
            'EventsList' =>  $Events,
            'timelineData' => $timelineData,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
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

        if($accounting_payment_conditions == 0 || $accounting_payment_methods == 0 || $accounting_deliveries == 0){
            return redirect()->route('opportunities.show', ['id' =>  $id->id])->with('error', 'No default settings');
        }

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

        //change opp statu
        Opportunities::where('id', $id->id)->update(['statu'=>2]);

        //change statu companie
        Companies::where('id', $id->companies_id)->update(['statu_customer'=>2]);
        return redirect()->route('quotes.show', ['id' => $QuotesCreated->id])->with('success', 'Successfully created new quote');
    }
}

