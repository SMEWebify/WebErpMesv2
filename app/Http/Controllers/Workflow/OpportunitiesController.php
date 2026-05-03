<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Support\Str;
use App\Events\QuoteCreated;
use App\Models\Admin\Factory;
use App\Models\User;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Traits\NextPreviousTrait;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\OrderLines;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Opportunities;
use App\Notifications\QuoteNotification;
use App\Services\OpportunitiesKPIService;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Workflow\OpportunitiesEventsLogs;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Workflow\OpportunitiesActivitiesLogs;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Http\Requests\Workflow\UpdateOpportunityRequest;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;

class OpportunitiesController extends Controller
{ 
    use NextPreviousTrait;
    protected $notificationService ;
    protected $SelectDataService;
    protected $opportunitiesKPIService;
    protected $customFieldService;

    public function __construct(
            NotificationService $notificationService,
            SelectDataService $SelectDataService, 
            OpportunitiesKPIService $opportunitiesKPIService,
            CustomFieldService $customFieldService
        ){
        $this->SelectDataService = $SelectDataService;
        $this->opportunitiesKPIService = $opportunitiesKPIService;
        $this->customFieldService = $customFieldService;
        $this->notificationService  = $notificationService ;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $dataRate      = $this->opportunitiesKPIService->getOpportunitiesDataRate();
        $activities    = $this->opportunitiesKPIService->getRecentActivities();
        $byAmount      = $this->opportunitiesKPIService->getOpportunitiesByAmount();
        $byCompany     = $this->opportunitiesKPIService->getOpportunitiesByCompany();
        $count         = $this->opportunitiesKPIService->getOpportunitiesCount();
        $quotesSummary = $this->opportunitiesKPIService->getQuotesSummary();

        return view('workflow/opportunities-index', [
            'kpi' => [
                'count'     => $count,
                'totalWon'  => $quotesSummary['totalQuotesWon'],
                'totalLost' => $quotesSummary['totalQuotesLost'],
            ],
            'chart' => $dataRate->map(fn ($i) => [
                'statu' => $i->statu,
                'count' => $i->OpportunitiesCountRate,
            ])->values()->all(),
            'activities' => $activities->map(fn ($a) => [
                'id'               => $a->id,
                'label'            => $a->label,
                'created_pretty'   => $a->GetPrettyCreatedAttribute(),
                'opportunities_id' => $a->opportunities_id,
                'url'              => route('opportunities.show', ['id' => $a->opportunities_id]),
            ])->values()->all(),
            'byCompany' => $byCompany->map(fn ($o) => [
                'company_label' => $o->companie?->label ?? '—',
                'company_url'   => route('companies.show', ['id' => $o->companies_id]),
                'count'         => $o->count,
            ])->values()->all(),
            'byAmount' => $byAmount->map(fn ($o) => [
                'probality'    => $o->probality,
                'total_amount' => round((float) $o->total_amount, 2),
            ])->values()->all(),
        ]);
    }

    // -------------------------------------------------------------------------
    // JSON endpoints for the React OpportunitiesIndex component
    // -------------------------------------------------------------------------

    public function listJson(Request $request)
    {
        $search    = $request->get('search', '');
        $statuses  = array_filter(array_map('intval', (array) $request->get('statuses', [])));
        $sortField = $request->get('sort', 'created_at');
        $sortAsc   = $request->boolean('asc', false);
        $companyId = $request->get('company_id');

        $allowed = ['label', 'budget', 'probality', 'statu', 'created_at', 'companie'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }

        $dir   = $sortAsc ? 'asc' : 'desc';
        $query = Opportunities::with(['companie:id,label,code', 'UserManagement:id,name'])
            ->when($search, fn ($q) => $q->where('label', 'like', '%'.$search.'%'))
            ->when($statuses, fn ($q) => $q->whereIn('statu', $statuses))
            ->when($companyId, fn ($q) => $q->where('companies_id', $companyId));

        match ($sortField) {
            'companie' => $query->orderByRaw("(SELECT label FROM companies WHERE companies.id = opportunities.companies_id) {$dir}"),
            default    => $query->orderBy($sortField, $dir),
        };

        $items = $query->paginate(15);

        return response()->json([
            'data' => $items->map(fn ($o) => [
                'id'         => $o->id,
                'label'      => $o->label,
                'budget'     => round((float) $o->budget, 2),
                'probality'  => $o->probality,
                'statu'      => $o->statu,
                'close_date' => $o->close_date,
                'created_at' => $o->created_at?->format('d/m/Y'),
                'companie'   => $o->companie   ? ['id' => $o->companie->id,   'label' => $o->companie->label] : null,
                'user'       => $o->UserManagement ? ['id' => $o->UserManagement->id, 'name' => $o->UserManagement->name] : null,
                'url'        => route('opportunities.show', ['id' => $o->id]),
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
        $items = Opportunities::with(['companie:id,label', 'UserManagement:id,name'])->get();

        return response()->json($items->map(fn ($o) => [
            'id'        => $o->id,
            'label'     => $o->label,
            'budget'    => round((float) $o->budget, 2),
            'probality' => $o->probality,
            'statu'     => $o->statu,
            'companie'  => $o->companie   ? ['id' => $o->companie->id,   'label' => $o->companie->label] : null,
            'user'      => $o->UserManagement ? ['id' => $o->UserManagement->id, 'name' => $o->UserManagement->name] : null,
            'url'       => route('opportunities.show', ['id' => $o->id]),
        ]));
    }

    public function kanbanMoveJson(Request $request, int $id)
    {
        abort_unless(auth()->check(), 403);
        $validated   = $request->validate(['statu' => 'required|integer|between:1,6']);
        $opportunity = Opportunities::findOrFail($id);
        $opportunity->update(['statu' => $validated['statu']]);
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
            'label'                  => 'required|string|max:255',
            'budget'                 => 'required|numeric|min:0',
            'probality'              => 'required|integer|min:0|max:100',
            'comment'                => 'nullable|string',
        ]);

        $opportunity = Opportunities::create(array_merge($validated, ['uuid' => Str::uuid()]));

        return response()->json([
            'redirect' => route('opportunities.show', ['id' => $opportunity->id]),
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
     * Load the relations for the given opportunity.
     *
     * @param Opportunities $opportunity
     * @return Opportunities
     */
    private function loadOpportunityRelations(Opportunities $opportunity)
    {
        return $opportunity->load('lead', 'activities', 'events', 'quotes');
    }

    /**
     * Get the activities logs for the given opportunity.
     *
     * @param int $opportunityId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getActivities($opportunityId)
    {
        return OpportunitiesActivitiesLogs::where('opportunities_id', $opportunityId)->orderBy('id')->get();
    }

    /**
     * Get the events logs for the given opportunity.
     *
     * @param int $opportunityId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getEvents($opportunityId)
    {
        return OpportunitiesEventsLogs::where('opportunities_id', $opportunityId)->orderBy('id')->get();
    }

    /**
     * Get the first factory record.
     *
     * @return Factory
     */
    private function getFactory()
    {
        return app('Factory');
    }
    
    /**
     * Organize timeline data for the given opportunity.
     *
     * @param Opportunities $opportunity
     * @param $factory
     * @return array
     */
    private function organizeTimelineData(Opportunities $opportunity, $factory)
    {
        $timelineData = [];

        if ($opportunity->lead) {
            $timelineData[] = [
                'date' => $opportunity->lead->created_at->format('d M Y'),
                'icon' => 'fas fa-globe bg-info',
                'content' => "Lead " . $opportunity->lead->campaign,
                'details' => $opportunity->lead->GetPrettyCreatedAttribute(),
            ];
        }

        // Ajouter les événements s'il y en a
        foreach ($opportunity->events as $event) {

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
        foreach ($opportunity->activities as $activity) {
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
        foreach ($opportunity->quotes as $quote) {

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
                    'content' => __('general_content.order_trans_key') ." ". $order->label . " - ". __('general_content.total_price_trans_key') ." : ". $order->formatted_total_price ,
                    'details' => $order->GetPrettyCreatedAttribute(),
                ];
            }

            $timelineData[] = [
                'date' => $quote->created_at->format('d M Y'),
                'icon' => 'fas fa-calculator  bg-success', 
                'content' => __('general_content.quote_trans_key') ." ". $quote->label . " - ". __('general_content.total_price_trans_key') ." : ". $quote->formatted_total_price,
                'details' => $quote->GetPrettyCreatedAttribute(),
            ];
        }

        // Ajouter l'opportunité initiale
        $timelineData[] = [
            'date' => $opportunity->created_at->format('d M Y'),
            'icon' => 'fa fa-tags bg-primary',
            'content' => "Opportunité créée",
            'details' => $opportunity->GetPrettyCreatedAttribute(),
        ];

         // Trier le tableau par date
        usort($timelineData, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $timelineData;
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Opportunities $id)
    {  
        $CompanieSelect = $this->SelectDataService->getCompanies();
        $AddressSelect = $this->SelectDataService->getAddress($id->companies_id);
        $ContactSelect = $this->SelectDataService->getContact($id->companies_id);
        $Activities = $this->getActivities($id->id);
        $Events = $this->getEvents($id->id);
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Opportunities(), $id->id);
        $factory = $this->getFactory();
        $opportunity = $this->loadOpportunityRelations($id);
        $timelineData = $this->organizeTimelineData($opportunity, $factory);

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
     * @param \App\Http\Requests\Workflow\UpdateOpportunityRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateOpportunityRequest $request)
    {
        $opportunity = Opportunities::findOrFail($request->id);
        $opportunity->update($request->validated());

        return redirect()->route('opportunities.show', ['id' => $opportunity->id])
                        ->with('success', 'Successfully updated opportunity');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeQuote(Opportunities $id)
    {
        $lastQuote = Quotes::latest('id')->first();
        $quoteId = $lastQuote ? $lastQuote->id + 1 : 0;
        $code = "QT-$quoteId";
        $label = "QT-$quoteId";

        $defaultSettings = [
            'payment_conditions' => AccountingPaymentConditions::getDefault(),
            'payment_methods' => AccountingPaymentMethod::getDefault(),
            'deliveries' => AccountingDelivery::getDefault()
        ];

        foreach ($defaultSettings as $key => $setting) {
            if (is_null($setting)) {
                return redirect()->route('opportunities.show', ['id' => $id->id])
                                ->with('error', 'No default settings for ' . str_replace('_', ' ', $key));
            }
        }

        $quotesCreated = Quotes::create([
            'uuid' => Str::uuid(),
            'code' => $code,
            'label' => $label,
            'companies_id' => $id->companies_id,
            'companies_contacts_id' => $id->companies_contacts_id,
            'companies_addresses_id' => $id->companies_addresses_id,
            'user_id' => Auth::id(),
            'opportunities_id' => $id->id,
            'accounting_payment_conditions_id' => $defaultSettings['payment_conditions']->id,
            'accounting_payment_methods_id' => $defaultSettings['payment_methods']->id,
            'accounting_deliveries_id' => $defaultSettings['deliveries']->id,
        ]);

        $this->notificationService->sendNotification(QuoteNotification::class, $quotesCreated, 'quotes_notification');

        $id->update(['statu' => 2]);

        event(new QuoteCreated($quotesCreated));
        return redirect()->route('quotes.show', ['id' => $quotesCreated->id])
                        ->with('success', 'Successfully created new quote');
    }

    public function changeStatusJson(Request $request, $id)
    {
        try {
            Opportunities::where('id', $id)->update(['statu' => (int) $request->input('statu')]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Update failed'], 500);
        }
    }
}

