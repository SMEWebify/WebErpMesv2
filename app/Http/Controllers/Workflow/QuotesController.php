<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Log;
use App\Models\Workflow\Quotes;
use App\Models\Planning\Task;
use App\Models\Methods\MethodsServices;
use App\Models\Times\TimesBanckHoliday;
use App\Services\QuoteKPIService;
use App\Traits\NextPreviousTrait;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\QuoteCalculatorService;
use App\Models\Workflow\QuoteProjectEstimate;
use Illuminate\Http\Request;
use App\Http\Requests\Workflow\UpdateQuoteRequest;
use App\Http\Requests\Workflow\ProjectEstimateRequest;
use Spatie\Activitylog\Models\Activity;

class QuotesController extends Controller
{
    use NextPreviousTrait;
    protected $SelectDataService;
    protected $quoteKPIService;
    protected $customFieldService;

    public function __construct(
            SelectDataService $SelectDataService, 
            QuoteKPIService $quoteKPIService,
            CustomFieldService $customFieldService
        ){
            $this->SelectDataService = $SelectDataService;
            $this->quoteKPIService = $quoteKPIService;
            $this->customFieldService = $customFieldService;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $factory = app('Factory');

        $CurentYear = Carbon::now()->format('Y');
        //Quote data for chart
        $data['quotesDataRate'] = $this->quoteKPIService->getQuotesDataRate($CurentYear);
        //Quote data for chart
        $data['quoteMonthlyRecap'] = $this->quoteKPIService->getQuoteMonthlyRecap($CurentYear);
        $data['quoteMonthlyRecapPreviousYear'] = $this->quoteKPIService->getQuoteMonthlyRecapPreviousYear($CurentYear);
        $topCustomers = $this->quoteKPIService->getTopCustomersByQuoteVolume(3);
        $quotesCountByUser = $this->quoteKPIService->getQuotesCountByUser();
        $currency = $factory->curency ?? 'EUR';
        $averageAmount =  Number::currency($this->quoteKPIService->getAverageQuoteAmount(), $currency, config('app.locale'));
        $conversionRate =  $this->quoteKPIService->getQuoteConversionRate();
        $responseRate =  $this->quoteKPIService->getQuoteResponseRate();

        return view('workflow/quotes-index', compact('data', 
                                                    'topCustomers', 
                                                    'quotesCountByUser',
                                                    'averageAmount', 
                                                    'conversionRate', 
                                                    'responseRate'));
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Quotes $id)
    {
        $factory = app('Factory'); 

        $CompanieSelect = $this->SelectDataService->getCompanies();
        $AddressSelect = $this->SelectDataService->getAddress($id->companies_id);
        $ContactSelect = $this->SelectDataService->getContact($id->companies_id);
        $AccountingConditionSelect = $this->SelectDataService->getAccountingPaymentConditions();
        $AccountingMethodsSelect = $this->SelectDataService->getAccountingPaymentMethod();
        $AccountingDeleveriesSelect =  $this->SelectDataService->getAccountingDelivery();
        $Reviewers = $this->SelectDataService->getUsers();
        $QuoteCalculatorService = new QuoteCalculatorService($id);
        $currency = $factory->curency ?? 'EUR';
        $totalPrice =  Number::currency($QuoteCalculatorService->getTotalPrice(), $currency, config('app.locale'));
        $subPrice = Number::currency($QuoteCalculatorService->getSubTotal(), $currency, config('app.locale'));
        $vatPrice =  $QuoteCalculatorService->getVatTotal();
        $TotalServiceProductTime = $QuoteCalculatorService->getTotalProductTimeByService();
        $TotalServiceSettingTime = $QuoteCalculatorService->getTotalSettingTimeByService();
        $TotalServiceCost = $QuoteCalculatorService->getTotalCostByService();
        $TotalServicePrice = $QuoteCalculatorService->getTotalPriceByService();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Quotes(), $id->id);
        $CustomFields = $this->customFieldService->getCustomFieldsWithValues('quote', $id->id);
        $projectEstimate = QuoteProjectEstimate::where('quotes_id', $id->id)->first();
        $reviewFields = ['reviewed_by', 'reviewed_at', 'review_decision', 'change_requested_by', 'change_reason', 'change_approved_at'];
        $ReviewTimeline = Activity::query()
            ->where('subject_type', Quotes::class)
            ->where('subject_id', $id->id)
            ->with('causer')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Activity $activity) use ($reviewFields) {
                $properties = $activity->properties?->toArray() ?? [];
                $attributes = array_intersect_key(data_get($properties, 'attributes', []), array_flip($reviewFields));
                $old = array_intersect_key(data_get($properties, 'old', []), array_flip($reviewFields));

                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'causer' => $activity->causer?->name,
                    'created_at' => $activity->created_at,
                    'changes' => collect($attributes)->map(function ($newValue, $field) use ($old) {
                        return [
                            'field' => $field,
                            'old' => $old[$field] ?? null,
                            'new' => $newValue,
                        ];
                    })->values()->all(),
                ];
            })
            ->filter(fn ($entry) => !empty($entry['changes']))
            ->values();
        return view('workflow/quotes-show', [
            'Quote' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'AccountingConditionSelect' => $AccountingConditionSelect,
            'AccountingMethodsSelect' => $AccountingMethodsSelect,
            'AccountingDeleveriesSelect' => $AccountingDeleveriesSelect,
            'Reviewers' => $Reviewers,
            'ReviewTimeline' => $ReviewTimeline,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice,
            'vatPrice' => $vatPrice,
            'TotalServiceProductTime'=> $TotalServiceProductTime,
            'TotalServiceSettingTime'=> $TotalServiceSettingTime,
            'TotalServiceCost'=> $TotalServiceCost,
            'TotalServicePrice'=> $TotalServicePrice,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
            'CustomFields' => $CustomFields,
            'projectEstimate' => $projectEstimate,
        ]);
    }
    
    /**
     * @param UpdateQuoteRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateQuoteRequest $request)
    {
        $validated = $request->validated();

        try {
            $Quote = Quotes::findOrFail($request->id);
            $Quote->fill($validated);
            $Quote->save();

            Log::channel('quotes')->info(__('general_content.quote_updated_log_trans_key'), [
                'user_id' => $request->user()?->id,
                'quote_id' => $Quote->id,
                'parameters' => $validated,
            ]);

            return redirect()->route('quotes.show', ['id' => $Quote->id])->with('success', __('general_content.quote_update_success_trans_key'));
        } catch (\Exception $e) {
            Log::channel('quotes')->error(__('general_content.quote_update_failed_log_trans_key'), [
                'user_id' => $request->user()?->id,
                'quote_id' => $request->id,
                'parameters' => $validated,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function saveProjectEstimate(ProjectEstimateRequest $request, $quoteId)
    {
        // Récupérer les données validées
        $validated = $request->validated();

        // Recherche ou création d'une nouvelle estimation de projet
        $projectEstimate = QuoteProjectEstimate::where('quotes_id', $quoteId)->first();

        // Si l'estimation de projet n'existe pas, on en crée une nouvelle
        if (!$projectEstimate) {
            $projectEstimate = new QuoteProjectEstimate();
            $projectEstimate->quotes_id = $quoteId; // Assigne le quote_id à la nouvelle instance
        }

        // Mises à jour des champs avec les valeurs soumises dans le formulaire
        $projectEstimate->show_client_requirements_on_pdf = $request->has('show_client_requirements_on_pdf') ? 1 : 2;
        $projectEstimate->show_layout_on_pdf = $request->has('show_layout_on_pdf') ? 1 : 2;
        $projectEstimate->show_materials_on_pdf = $request->has('show_materials_on_pdf') ? 1 : 2;
        $projectEstimate->show_logistics_on_pdf = $request->has('show_logistics_on_pdf') ? 1 : 2;
        $projectEstimate->show_contractors_on_pdf = $request->has('show_contractors_on_pdf') ? 1 : 2;
        $projectEstimate->show_waste_on_pdf = $request->has('show_waste_on_pdf') ? 1 : 2;
        $projectEstimate->show_taxes_on_pdf = $request->has('show_taxes_on_pdf') ? 1 : 2;
        $projectEstimate->show_options_on_pdf = $request->has('show_options_on_pdf') ? 1 : 2;
        $projectEstimate->show_insurance_on_pdf = $request->has('show_insurance_on_pdf') ? 1 : 2;

        // Sauvegarde des données validées dans l'estimation de projet
        $projectEstimate->fill($validated); // Mise à jour des champs avec les données validées

        if ($projectEstimate->exists) {
            // Mise à jour de l'estimation existante
            $projectEstimate->save();
        } else {
            // Création d'une nouvelle estimation si elle n'existe pas encore
            $projectEstimate->save();
        }


    // Redirection après l'enregistrement
    return redirect()->route('quotes.show', ['id' => $quoteId])
        ->with('success', __('Estimation de projet enregistrée avec succès.'));
}

    public function simulateDelivery(Request $request, Quotes $id)
    {
        $validated = $request->validate([
            'requested_delivery_date' => 'required|date',
        ]);

        $requestedDate = Carbon::parse($validated['requested_delivery_date'])->startOfDay();
        $startDate = Carbon::now()->startOfDay();

        if ($requestedDate->lt($startDate)) {
            return redirect()
                ->route('quotes.show', ['id' => $id->id])
                ->with('error', __('general_content.simulation_invalid_date_trans_key'));
        }

        $quoteLineIds = $id->QuoteLines()->pluck('id');

        if ($quoteLineIds->isEmpty()) {
            return redirect()
                ->route('quotes.show', ['id' => $id->id])
                ->with('error', __('general_content.simulation_no_tasks_trans_key'));
        }

        $quoteTasks = Task::query()
            ->whereIn('quote_lines_id', $quoteLineIds)
            ->where(function ($query) {
                return $query->where('tasks.type', 1)
                    ->orWhere('tasks.type', 7);
            })
            ->get();

        if ($quoteTasks->isEmpty()) {
            return redirect()
                ->route('quotes.show', ['id' => $id->id])
                ->with('error', __('general_content.simulation_no_tasks_trans_key'));
        }

        $requiredByService = $quoteTasks
            ->filter(fn (Task $task) => !is_null($task->methods_services_id))
            ->groupBy('methods_services_id')
            ->map(fn ($tasks) => round($tasks->sum(fn (Task $task) => $task->TotalTime()), 2))
            ->toArray();

        if ($requiredByService === []) {
            return redirect()
                ->route('quotes.show', ['id' => $id->id])
                ->with('error', __('general_content.simulation_no_tasks_trans_key'));
        }

        $maxSearchDays = 365;
        $simulationEndDate = $startDate->copy()->addDays($maxSearchDays);
        if ($requestedDate->gt($simulationEndDate)) {
            $simulationEndDate = $requestedDate->copy();
        }

        $loadTasks = Task::query()
            ->whereNotNull('order_lines_id')
            ->whereBetween('end_date', [$startDate->toDateString(), $simulationEndDate->toDateString()])
            ->where(function ($query) {
                return $query->where('tasks.type', 1)
                    ->orWhere('tasks.type', 7);
            })
            ->get();

        $loadByServiceDay = [];
        foreach ($loadTasks as $task) {
            if (is_null($task->methods_services_id) || is_null($task->end_date)) {
                continue;
            }
            $day = Carbon::parse($task->end_date)->toDateString();
            $serviceId = $task->methods_services_id;
            $loadByServiceDay[$serviceId][$day] = ($loadByServiceDay[$serviceId][$day] ?? 0) + $task->TotalTime();
        }

        $capacityPerDay = 16;
        $remainingAfterRequested = $this->simulateRemainingHours(
            $requiredByService,
            $startDate,
            $requestedDate,
            $capacityPerDay,
            $loadByServiceDay
        );

        $isPossible = $this->allServicesSatisfied($remainingAfterRequested);
        $earliestDate = $this->calculateEarliestCompletionDate(
            $requiredByService,
            $startDate,
            $simulationEndDate,
            $capacityPerDay,
            $loadByServiceDay
        );

        $missingByService = [];
        if (!$isPossible) {
            foreach ($remainingAfterRequested as $serviceId => $remainingHours) {
                if ($remainingHours > 0) {
                    $missingByService[$serviceId] = round($remainingHours, 2);
                }
            }
        }

        $serviceLabels = MethodsServices::query()
            ->whereIn('id', array_keys($requiredByService))
            ->pluck('label', 'id')
            ->toArray();

        return redirect()
            ->route('quotes.show', ['id' => $id->id])
            ->with('delivery_simulation', [
                'requested_date' => $requestedDate->toDateString(),
                'is_possible' => $isPossible,
                'earliest_date' => $earliestDate?->toDateString(),
                'required_by_service' => $requiredByService,
                'missing_by_service' => $missingByService,
                'service_labels' => $serviceLabels,
                'capacity_per_day' => $capacityPerDay,
            ]);
    }

    private function simulateRemainingHours(
        array $requiredByService,
        Carbon $startDate,
        Carbon $endDate,
        int $capacityPerDay,
        array $loadByServiceDay
    ): array {
        $remaining = $requiredByService;
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            if (!$this->isWorkingDay($cursor)) {
                $cursor->addDay();
                continue;
            }

            $dayKey = $cursor->toDateString();
            foreach ($remaining as $serviceId => $hours) {
                if ($hours <= 0) {
                    continue;
                }
                $loaded = $loadByServiceDay[$serviceId][$dayKey] ?? 0;
                $available = $capacityPerDay - $loaded;
                if ($available <= 0) {
                    continue;
                }
                $remaining[$serviceId] = round($hours - min($available, $hours), 2);
            }

            $cursor->addDay();
        }

        return $remaining;
    }

    private function calculateEarliestCompletionDate(
        array $requiredByService,
        Carbon $startDate,
        Carbon $endDate,
        int $capacityPerDay,
        array $loadByServiceDay
    ): ?Carbon {
        $remaining = $requiredByService;
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            if ($this->isWorkingDay($cursor)) {
                $dayKey = $cursor->toDateString();
                foreach ($remaining as $serviceId => $hours) {
                    if ($hours <= 0) {
                        continue;
                    }
                    $loaded = $loadByServiceDay[$serviceId][$dayKey] ?? 0;
                    $available = $capacityPerDay - $loaded;
                    if ($available <= 0) {
                        continue;
                    }
                    $remaining[$serviceId] = round($hours - min($available, $hours), 2);
                }

                if ($this->allServicesSatisfied($remaining)) {
                    return $cursor->copy();
                }
            }

            $cursor->addDay();
        }

        return null;
    }

    private function allServicesSatisfied(array $remainingByService): bool
    {
        foreach ($remainingByService as $hours) {
            if ($hours > 0) {
                return false;
            }
        }

        return true;
    }

    private function isWorkingDay(Carbon $date): bool
    {
        if ($date->isWeekend()) {
            return false;
        }

        return !TimesBanckHoliday::isBankHoliday($date);
    }
}
