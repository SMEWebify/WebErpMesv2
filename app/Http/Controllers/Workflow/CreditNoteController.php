<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use App\Traits\NextPreviousTrait;
use App\Models\Workflow\CreditNotes;
use App\Models\Workflow\OrderLines;
use App\Services\CustomFieldService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Workflow\InvoiceLines;
use App\Services\Invoicing\CreditNoteBuilder;
use App\Services\CreditNoteKPIService;
use App\Services\DocumentCodeGenerator;
use App\Services\AccountingEntryService;
use App\Models\Workflow\CreditNoteLines;
use App\Services\CreditNoteCalculatorService;
use App\Http\Requests\Workflow\UpdateCreditNoteRequest;
use Illuminate\Support\Facades\App;

class CreditNoteController extends Controller
{
    use NextPreviousTrait;
    
    protected $creditNoteKPIService;
    protected $customFieldService;
    protected $documentCodeGenerator;

    public function __construct(
                    CreditNoteKPIService $creditNoteKPIService,
                    CustomFieldService $customFieldService,
                    DocumentCodeGenerator $documentCodeGenerator
            ){
        $this->creditNoteKPIService = $creditNoteKPIService;
        $this->customFieldService = $customFieldService;
        $this->documentCodeGenerator = $documentCodeGenerator;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $CurentYear = Carbon::now()->format('Y');
        $data['creditNotesDataRate'] = $this->creditNoteKPIService->getCreditNotesDataRate();
        $data['creditNoteMonthlyRecap'] = $this->creditNoteKPIService->getCreditNotesMonthlyRecap($CurentYear);

        return view('workflow/credit-notes-index')->with('data',$data);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function CreateCreditNotes(Request $request)
    {
        $selectedInvoiceLineIds = $request->input('selected_invoice_lines');

        if (empty($selectedInvoiceLineIds)) {
            return redirect()->back()->with('error', 'Veuillez sélectionner au moins une ligne de facture.');
        }

        $request->validate([
            'qty'          => 'nullable|array',
            'qty.*'        => 'nullable|numeric|min:0',
            'unit_price'   => 'nullable|array',
            'unit_price.*' => 'nullable|numeric|min:0',
        ]);

        $invoiceLines = InvoiceLines::with(['invoice', 'orderLine'])->whereIn('id', $selectedInvoiceLineIds)->get();

        // Sans le champ (ancien formulaire), les quantités reviennent au reste à facturer
        // comme avant ; décoché, l'avoir n'y touche pas (geste commercial, rebut non refait).
        $restore = $request->has('restore_quantities_sent') ? $request->boolean('restore_quantities') : true;

        try {
            $creditNote = app(CreditNoteBuilder::class)->create(
                $invoiceLines,
                (array) $request->input('qty', []),
                (array) $request->input('unit_price', []),
                $restore,
            );
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('credit.notes.show', ['id' => $creditNote->id])->with('success', 'Successfully created credit note');
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(CreditNotes $id)
    {
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        
        $CreditNoteCalculatorService = new CreditNoteCalculatorService($id);
        $totalPrice = $CreditNoteCalculatorService->getTotalPrice();
        $subPrice = $CreditNoteCalculatorService->getSubTotal();
        $vatPrice = $CreditNoteCalculatorService->getVatTotal();
        $totalPrice = Number::currency($totalPrice, $currency, config('app.locale'));
        $subPrice = Number::currency($subPrice, $currency, config('app.locale'));

        list($previousUrl, $nextUrl) = $this->getNextPrevious(new CreditNotes(), $id->id);
        return view('workflow/credit-notes-show', [
            'CreditNotes' => $id,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice, 
            'vatPrice' => $vatPrice,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
        ]);
    }

        // -------------------------------------------------------------------------
    // JSON endpoint for the React CreditNotesIndex component
    // -------------------------------------------------------------------------

    public function listJson(Request $request)
    {
        $search    = $request->get('search', '');
        $statuses  = array_filter(array_map('intval', (array) $request->get('statuses', [])));
        $sortField = $request->get('sort', 'created_at');
        $sortAsc   = $request->boolean('asc', false);

        $allowed = ['code', 'label', 'created_at', 'statu', 'companies_id', 'lines_count'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }

        $dir = $sortAsc ? 'asc' : 'desc';

        $query = CreditNotes::withCount('creditNotelines as lines_count')
            ->with(['companie:id,label', 'UserManagement:id,name'])
            ->when($search, fn ($q) => $q->where('label', 'like', '%'.$search.'%'))
            ->when($statuses, fn ($q) => $q->whereIn('statu', $statuses));

        match ($sortField) {
            'lines_count'  => $query->orderBy('lines_count', $dir),
            'companies_id' => $query->orderByRaw("(SELECT label FROM companies WHERE companies.id = credit_notes.companies_id) {$dir}"),
            default        => $query->orderBy($sortField, $dir),
        };

        $creditNotes = $query->paginate(15);

        $creditNotes->load(['creditNotelines.orderLine.VAT']);

        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $locale   = config('app.locale');

        return response()->json([
            'data' => $creditNotes->map(fn ($cn) => [
                'id'          => $cn->id,
                'code'        => $cn->code,
                'label'       => $cn->label,
                'statu'       => $cn->statu,
                'lines_count' => $cn->lines_count,
                'total_price' => (new CreditNoteCalculatorService($cn))->getTotalPrice(),
                'companie'    => $cn->companie ? ['id' => $cn->companie->id, 'label' => $cn->companie->label] : null,
                'user'        => $cn->UserManagement ? ['name' => $cn->UserManagement->name] : null,
                'created_at'  => $cn->created_at?->format('d/m/Y'),
                'url'         => route('credit.notes.show', ['id' => $cn->id]),
            ]),
            'meta' => [
                'total'        => $creditNotes->total(),
                'per_page'     => $creditNotes->perPage(),
                'current_page' => $creditNotes->currentPage(),
                'last_page'    => $creditNotes->lastPage(),
            ],
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateCreditNoteRequest $request)
    {
        $CreditNote = CreditNotes::find($request->id);
        $previousStatu = (int) $CreditNote->statu;

        // Refusé, l'avoir reprend les quantités rendues au reste à facturer ; rouvert, il
        // les rend. Refusé si elles ont été refacturées entre-temps.
        if ($request->filled('statu') && (int) $request->statu !== $previousStatu) {
            try {
                DB::transaction(fn () => app(CreditNoteBuilder::class)->syncQuantities($CreditNote, $previousStatu, (int) $request->statu));
            } catch (\DomainException $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        $CreditNote->label              = $request->label;
        $CreditNote->statu              = $request->statu;
        $CreditNote->reason             = $request->reason;
        $CreditNote->customer_reference = $request->customer_reference;
        $CreditNote->save();
        if ($CreditNote->wasChanged('statu')) {
            // Un avoir refusé ne compte pas en comptabilité ; rouvert, il compte de nouveau.
            app(AccountingEntryService::class)->syncCreditNoteEntries($CreditNote);
        }

        return redirect()->route('credit.notes.show', ['id' =>  $CreditNote->id])->with('success', 'Successfully updated credit note');
    }
}
