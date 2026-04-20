<?php

namespace App\Http\Controllers\Companies;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Events\QuoteCreated;
use Illuminate\Support\Number;
use App\Models\User;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\Leads;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\Invoices;
use App\Models\Purchases\Purchases;
use App\Services\CompanyService;
use App\Services\OrderKPIService;
use App\Services\QuoteKPIService;
use App\Traits\NextPreviousTrait;
use App\Models\Companies\Companies;
use App\Services\InvoiceKPIService;
use App\Services\SelectDataService;
use App\Services\DocumentCodeGenerator;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Session;
use App\Notifications\QuoteNotification;
use App\Notifications\CompanieNotification;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompanyDocumentDefault;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Http\Requests\Companies\UpdateCompanieRequest;
use App\Http\Requests\Companies\UpdateCompanieJsonRequest;
use App\Models\Accounting\AccountingPaymentConditions;
use Carbon\Carbon;
use App\Models\Methods\MethodsServices;

class CompaniesController extends Controller
{
    use NextPreviousTrait;
    protected $notificationService ;
    protected $SelectDataService;
    protected $orderKPIService;
    protected $quoteKPIService;
    protected $invoiceKPIService;
    protected $companyService;
    protected $documentCodeGenerator;

    public function __construct(
        NotificationService $notificationService,
        InvoiceKPIService $invoiceKPIService,
        SelectDataService $SelectDataService,
        OrderKPIService $orderKPIService,
        QuoteKPIService $quoteKPIService,
        CompanyService $companyService,
        DocumentCodeGenerator $documentCodeGenerator,
    )
    {
        $this->notificationService = $notificationService;
        $this->SelectDataService = $SelectDataService;
        $this->orderKPIService = $orderKPIService;
        $this->quoteKPIService = $quoteKPIService;
        $this->invoiceKPIService = $invoiceKPIService;
        $this->companyService = $companyService;
        $this->documentCodeGenerator = $documentCodeGenerator;
    }

    protected function getCompanyCounts() 
    {
        $data['ClientCountRate'] = Companies::where('statu_customer', 2)->where('statu_supplier', '!=', 2)->count();
        $data['ProspectCountRate'] = Companies::where('statu_customer', 3)->count();
        $data['SupplierCountRate'] = Companies::where('statu_supplier', 2)->where('statu_customer', '!=', 2)->count();
        $data['ClientSupplierCountRate'] = Companies::where('statu_customer', 2)->where('statu_supplier', 2)->count();
        return $data;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $data  = $this->getCompanyCounts();
        $total = Companies::count();

        $monthlyNew = Companies::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->get()
            ->mapWithKeys(fn($r) => [$r->month => $r->count]);

        $reactKpi = [
            'total'           => $total,
            'clients'         => $data['ClientCountRate'] + $data['ClientSupplierCountRate'],
            'prospects'       => $data['ProspectCountRate'],
            'suppliers'       => $data['SupplierCountRate'] + $data['ClientSupplierCountRate'],
        ];

        $reactChart = [
            'clients'         => $data['ClientCountRate'],
            'prospects'       => $data['ProspectCountRate'],
            'suppliers'       => $data['SupplierCountRate'],
            'clientSuppliers' => $data['ClientSupplierCountRate'],
            'monthlyNew'      => $monthlyNew,
        ];

        $reactEndpoints = [
            'list'       => route('companies.json.list'),
            'store'      => route('companies.json.store'),
            'selectData' => route('companies.json.select-data'),
        ];

        $reactTrans = [
            'dashboard'        => __('general_content.dashboard_trans_key'),
            'companies_list'   => __('general_content.companies_list_trans_key'),
            'statistiques'     => __('general_content.statistiques_trans_key'),
            'monthly_new'      => 'Nouvelles entreprises',
            'client'           => __('general_content.client_trans_key'),
            'prospect'         => __('general_content.prospect_trans_key'),
            'supplier'         => __('general_content.suppliers_trans_key'),
            'client_supplier'  => __('general_content.suppliers_client_trans_key'),
            'total'            => __('general_content.total_trans_key'),
            'new_company'      => __('general_content.new_companie_trans_key'),
            'search'           => __('general_content.search_trans_key'),
            'external_id'      => __('general_content.external_id_trans_key'),
            'label'            => __('general_content.label_trans_key'),
            'active'           => __('general_content.active_trans_key'),
            'status_client'    => __('general_content.status_client_trans_key'),
            'status_supplier'  => __('general_content.status_supplier_trans_key'),
            'created_at'       => __('general_content.created_at_trans_key'),
            'action'           => __('general_content.action_trans_key'),
            'customer_type'    => __('general_content.customer_type_trans_key'),
            'legal_entity'     => __('general_content.legal_entity_trans_key'),
            'individual'       => __('general_content.individual_trans_key'),
            'user_management'  => __('general_content.user_management_trans_key'),
            'comment'          => __('general_content.comment_trans_key'),
            'civility'         => __('general_content.civility_trans_key'),
            'first_name'       => __('general_content.first_name_trans_key'),
            'contact_name'     => __('general_content.contact_name_trans_key'),
            'name_company'     => __('general_content.name_company_trans_key'),
            'no_results'       => __('general_content.no_results_trans_key'),
            'save'             => __('general_content.save_trans_key'),
            'saving'           => __('general_content.saving_trans_key'),
            'cancel'           => __('general_content.cancel_trans_key'),
            'view_all'         => __('general_content.view_all_trans_key'),
            'code'             => __('general_content.id_trans_key'),
            'jan' => 'Jan', 'feb' => 'Fév', 'mar' => 'Mar',
            'apr' => 'Avr', 'may' => 'Mai', 'jun' => 'Jun',
            'jul' => 'Jul', 'aug' => 'Aoû', 'sep' => 'Sep',
            'oct' => 'Oct', 'nov' => 'Nov', 'dec' => 'Déc',
        ];

        return view('companies/companies-index', compact('reactKpi', 'reactChart', 'reactEndpoints', 'reactTrans'));
    }

    public function listJson(Request $request)
    {
        $search       = $request->get('search', '');
        $statusFilter = $request->get('status', 'all');
        $sortField    = $request->get('sort', 'created_at');
        $sortAsc      = $request->boolean('asc', false);

        $allowed = ['code', 'label', 'created_at', 'active', 'statu_customer', 'statu_supplier'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }

        $dir   = $sortAsc ? 'asc' : 'desc';
        $query = Companies::when($search, fn($q) => $q->where('label', 'like', '%'.$search.'%'));

        switch ($statusFilter) {
            case 'client':
                $query->where('statu_customer', 2)->where('statu_supplier', '!=', 2);
                break;
            case 'prospect':
                $query->where('statu_customer', 3);
                break;
            case 'supplier':
                $query->where('statu_supplier', 2)->where('statu_customer', '!=', 2);
                break;
            case 'client_supplier':
                $query->where('statu_customer', 2)->where('statu_supplier', 2);
                break;
        }

        $companies = $query->orderBy($sortField, $dir)->paginate(15);

        return response()->json([
            'data' => $companies->map(fn($c) => [
                'id'             => $c->id,
                'code'           => $c->code,
                'label'          => $c->label,
                'created_at'     => $c->created_at?->format('d/m/Y'),
                'active'         => $c->active,
                'statu_customer' => $c->statu_customer,
                'statu_supplier' => $c->statu_supplier,
                'url'            => route('companies.show', ['id' => $c->id]),
            ]),
            'meta' => [
                'total'        => $companies->total(),
                'per_page'     => $companies->perPage(),
                'current_page' => $companies->currentPage(),
                'last_page'    => $companies->lastPage(),
            ],
        ]);
    }

    public function storeJson(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'code'        => 'required|unique:companies',
            'label'       => 'required|string|max:255',
            'client_type' => 'required|integer|in:1,2',
            'civility'    => 'nullable|string|max:50|required_if:client_type,2',
            'last_name'   => 'nullable|string|max:255|required_if:client_type,2',
            'user_id'     => 'required|exists:users,id',
            'comment'     => 'nullable|string',
        ]);

        $company = Companies::create(array_merge($validated, ['uuid' => Str::uuid()]));

        $this->notificationService->sendNotification(CompanieNotification::class, $company, 'companies_notification');

        return response()->json(['redirect' => route('companies.show', ['id' => $company->id])], 201);
    }

    public function selectDataJson()
    {
        $lastCompany = Companies::orderBy('id', 'desc')->first();
        $nextCode    = $this->documentCodeGenerator->generateDocumentCode('company', $lastCompany?->id ?? 0);

        return response()->json([
            'next_code' => $nextCode,
            'users'     => User::select('id', 'name')->get(),
        ]);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Companies $id)
    {
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $CurentYear = now()->year;
        $fiscal      = $factory->getCurrentFiscalYear();
        $fiscalStart = $fiscal['start'];
        $fiscalEnd   = $fiscal['end'];
        $userSelect = $this->SelectDataService->getUsers();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Companies(), $id->id);
        $remainingInvoiceOrder =  $this->orderKPIService->getOrderMonthlyRemainingToInvoice($id->id);
        $pendingOrdersCount = $this->orderKPIService->getPendingOrdersCountForCompany($id->id);
        $paidInvoices = $this->invoiceKPIService->getPaidInvoicesCount($id->id);
        $unpaidInvoices = $this->invoiceKPIService->getUnpaidInvoicesCount($id->id);
        $serviceRate = $this->orderKPIService->getServiceRate($id->id);
        $data['quotesDataRate'] = $this->quoteKPIService->getQuotesDataRate($CurentYear, $id->id);
        $data['orderMonthlyRecap'] = $this->orderKPIService->getOrderMonthlyRecap($CurentYear, $id->id, $fiscalStart, $fiscalEnd);
        $data['orderAverage'] = $this->orderKPIService->getAverageOrderPriceAttribute($id->id);
        $data['orderAverage'] = Number::currency($data['orderAverage'], $currency, config('app.locale'));
        $remainingInvoiceOrder = Number::currency($remainingInvoiceOrder->orderSum ?? 0, $currency, config('app.locale'));

        $customerServiceId = MethodsServices::where('label', 'Customer Service')->value('id');

        if ($customerServiceId === null || filter_var($customerServiceId, FILTER_VALIDATE_INT) === false) {
            $customerProcessingCost = 0;
        } else {
            $startOfYear = Carbon::now()->startOfYear();
            $endOfYear = Carbon::now()->endOfYear();
            $customerProcessingCost = $this->orderKPIService->getCustomerProcessingCost(
                $id->id,
                $startOfYear,
                $endOfYear,
                (int) $customerServiceId
            );
        }

        $customerProcessingCost = Number::currency($customerProcessingCost, $currency, config('app.locale'));

        $Companie = $id;

        $evaluationHistory = $Companie->rating()->orderByDesc('created_at')->get();
        $latestEvaluation = $evaluationHistory->first();
        $compositeScores = $evaluationHistory
            ->pluck('composite_score')
            ->filter();

        $averageCompositeScore = $compositeScores->isNotEmpty()
            ? round($compositeScores->avg(), 1)
            : null;

        $daysUntilNextReview = null;
        $needsRequalification = false;
        $nextReviewSoon = false;

        if ($latestEvaluation && $latestEvaluation->next_review_at) {
            $daysUntilNextReview = Carbon::now()->startOfDay()->diffInDays($latestEvaluation->next_review_at, false);
            $needsRequalification = $daysUntilNextReview < 0;
            $nextReviewSoon = $daysUntilNextReview <= 30;
        }

        $purchasesForEvaluation = $Companie->Purchases()
            ->with('Rating')
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        $documentDefaults = CompanyDocumentDefault::forCompany($id->id);

        return view('companies/companies-show', compact('Companie',
                                                        'userSelect',
                                                        'previousUrl',
                                                        'nextUrl',
                                                        'remainingInvoiceOrder',
                                                        'pendingOrdersCount',
                                                        'customerProcessingCost',
                                                        'paidInvoices',
                                                        'unpaidInvoices',
                                                        'serviceRate',
                                                        'data',
                                                        'evaluationHistory',
                                                        'latestEvaluation',
                                                        'averageCompositeScore',
                                                        'needsRequalification',
                                                        'nextReviewSoon',
                                                        'daysUntilNextReview',
                                                        'purchasesForEvaluation',
                                                        'documentDefaults',));
    }

    /**
     * AJAX update — returns JSON, no redirect.
     */
    public function updateJson(UpdateCompanieJsonRequest $request, Companies $company)
    {
        $vatNumber = $request->input('intra_community_vat');
        $vatWarning = null;

        if ($vatNumber) {
            $countryCode = substr($vatNumber, 0, 2);
            $vatCode     = substr($vatNumber, 2);
            try {
                $isValid = $this->companyService->validateVatNumber($countryCode, $vatCode);
                if (!$isValid) {
                    $vatWarning = 'Le numéro de TVA est invalide, mais les autres informations ont été mises à jour.';
                }
            } catch (\Exception $e) {
                $vatWarning = 'Le service de validation de la TVA est indisponible. La fiche a été mise à jour sans validation du numéro de TVA.';
            }
        }

        $company->update($request->validated());
        $company->active               = $request->boolean('active');
        $company->quoted_delivery_note = $request->boolean('quoted_delivery_note');
        $company->save();

        return response()->json([
            'success' => true,
            'warning' => $vatWarning,
        ]);
    }

    /**
     * @param $id
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateCompanieRequest $request)
    {
        $company = Companies::findOrFail($request->id);
        // Attempt to validate VAT number via SOAP service
        $vatNumber = $request->input('intra_community_vat');
        if ($vatNumber) {
            $countryCode = substr($vatNumber, 0, 2);
            $vatCode = substr($vatNumber, 2);
            try {
                $isValid = $this->companyService->validateVatNumber($countryCode, $vatCode);

                if (!$isValid) {
                   // Validation failed, but we continue the update
                    Session::flash('warning', 'Le numéro de TVA est invalide, mais les autres informations ont été mises à jour.');
                }
            } catch (\Exception $e) {
                // Catch the exception from the SOAP service
                Session::flash('error', 'Le service de validation de la TVA est indisponible. La fiche client a été mise à jour, mais sans validation du numéro de TVA.');
            }
        }

        // Update the customer record with the other fields
        $company->update($request->validated());
        // Handle specific cases outside mass assignment
        $company->active = $request->has('active') ? 1 : 0;
        $company->quoted_delivery_note = $request->has(key: 'quoted_delivery_note') ? 1 : 0;
        $company->save();

        return redirect()->route('companies.show', ['id' =>  $company->id])->with('success', 'Successfully updated companie');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeQuote(Companies $id)
    {
        $lastQuote = Quotes::latest('id')->first();
        $quoteId = $lastQuote ? $lastQuote->id : 0;
        $code = "QT-$quoteId";
        $label = "QT-$quoteId";

        $defaultSettings = [
            'accounting_payment_conditions' => AccountingPaymentConditions::getDefault(),
            'accounting_payment_methods' => AccountingPaymentMethod::getDefault(),
            'accounting_deliveries' => AccountingDelivery::getDefault(),
            'defaultAddress' => CompaniesAddresses::getDefault(['companies_id' => $id->id]),
            'defaultContact' => CompaniesContacts::getDefault(['companies_id' => $id->id]),
        ];

        foreach ($defaultSettings as $key => $setting) {
            if (is_null($setting)) {
                return redirect()->route('companies.show', ['id' => $id->id])->with('error', 'No default settings for ' . str_replace('_', ' ', $key));
            }
            $defaultSettings[$key] = $setting->id;
        }

        $quotesCreated = Quotes::create([
            'uuid' => Str::uuid(),
            'code' => $code,
            'label' => $label,
            'companies_id' => $id->id,
            'companies_contacts_id' => $defaultSettings['defaultContact'],
            'companies_addresses_id' => $defaultSettings['defaultAddress'],
            'user_id' => Auth::id(),
            'accounting_payment_conditions_id' => $defaultSettings['accounting_payment_conditions'],
            'accounting_payment_methods_id' => $defaultSettings['accounting_payment_methods'],
            'accounting_deliveries_id' => $defaultSettings['accounting_deliveries'],
        ]);

        // Notification for all users in the database
        $this->notificationService->sendNotification(QuoteNotification::class, $quotesCreated, 'quotes_notification');

        // Trigger the event
        event(new QuoteCreated($quotesCreated));

        return redirect()->route('quotes.show', ['id' => $quotesCreated->id])->with('success', 'Successfully created new quote');
    }

    /**
     * Returns a unified chronological timeline of all documents linked to a company.
     */
    public function timelineJson(Companies $id): \Illuminate\Http\JsonResponse
    {
        $companyId = $id->id;
        $items = collect();

        if (auth()->user()->can('leads-menu')) {
            Leads::where('companies_id', $companyId)
                ->select('id', 'statu', 'created_at', 'source', 'priority')
                ->get()
                ->each(function ($lead) use (&$items) {
                    $items->push([
                        'type'   => 'lead',
                        'id'     => $lead->id,
                        'code'   => null,
                        'label'  => $lead->source ?? '—',
                        'statu'  => $lead->statu,
                        'amount' => null,
                        'date'   => $lead->created_at?->toDateString(),
                        'url'    => route('leads.show', $lead->id),
                    ]);
                });
        }

        if (auth()->user()->can('quotes-menu')) {
            Quotes::where('companies_id', $companyId)
                ->select('id', 'code', 'label', 'statu', 'created_at')
                ->get()
                ->each(function ($quote) use (&$items) {
                    $items->push([
                        'type'   => 'quote',
                        'id'     => $quote->id,
                        'code'   => $quote->code,
                        'label'  => $quote->label,
                        'statu'  => $quote->statu,
                        'amount' => null,
                        'date'   => $quote->created_at?->toDateString(),
                        'url'    => route('quotes.show', $quote->id),
                    ]);
                });
        }

        if (auth()->user()->can('orders-menu')) {
            Orders::where('companies_id', $companyId)
                ->select('id', 'code', 'label', 'statu', 'created_at')
                ->get()
                ->each(function ($order) use (&$items) {
                    $items->push([
                        'type'   => 'order',
                        'id'     => $order->id,
                        'code'   => $order->code,
                        'label'  => $order->label,
                        'statu'  => $order->statu,
                        'amount' => null,
                        'date'   => $order->created_at?->toDateString(),
                        'url'    => route('orders.show', $order->id),
                    ]);
                });
        }

        if (auth()->user()->can('deliverys-menu')) {
            Deliverys::where('companies_id', $companyId)
                ->select('id', 'code', 'label', 'statu', 'created_at')
                ->get()
                ->each(function ($delivery) use (&$items) {
                    $items->push([
                        'type'   => 'delivery',
                        'id'     => $delivery->id,
                        'code'   => $delivery->code,
                        'label'  => $delivery->label,
                        'statu'  => $delivery->statu,
                        'amount' => null,
                        'date'   => $delivery->created_at?->toDateString(),
                        'url'    => route('deliverys.show', $delivery->id),
                    ]);
                });
        }

        if (auth()->user()->can('invoices-menu')) {
            Invoices::where('companies_id', $companyId)
                ->select('id', 'code', 'label', 'statu', 'created_at')
                ->get()
                ->each(function ($invoice) use (&$items) {
                    $items->push([
                        'type'   => 'invoice',
                        'id'     => $invoice->id,
                        'code'   => $invoice->code,
                        'label'  => $invoice->label,
                        'statu'  => $invoice->statu,
                        'amount' => null,
                        'date'   => $invoice->created_at?->toDateString(),
                        'url'    => route('invoices.show', $invoice->id),
                    ]);
                });
        }

        if (auth()->user()->can('purchases-menu')) {
            Purchases::where('companies_id', $companyId)
                ->select('id', 'code', 'label', 'statu', 'created_at')
                ->get()
                ->each(function ($purchase) use (&$items) {
                    $items->push([
                        'type'   => 'purchase',
                        'id'     => $purchase->id,
                        'code'   => $purchase->code,
                        'label'  => $purchase->label,
                        'statu'  => $purchase->statu,
                        'amount' => null,
                        'date'   => $purchase->created_at?->toDateString(),
                        'url'    => route('purchases.show', $purchase->id),
                    ]);
                });
        }

        return response()->json(
            $items->sortByDesc('date')->values()
        );
    }
}
