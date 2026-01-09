<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use App\Traits\NextPreviousTrait;
use App\Models\Workflow\CreditNotes;
use App\Services\CustomFieldService;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\InvoiceLines;
use App\Services\CreditNoteKPIService;
use App\Services\DocumentCodeGenerator;
use App\Models\Workflow\CreditNoteLines;
use App\Services\CreditNoteCalculatorService;
use App\Http\Requests\Workflow\UpdateCreditNoteRequest;

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
        // Récupérer les IDs des lignes de factures sélectionnées
        $selectedInvoiceLineIds = $request->input('selected_invoice_lines');
        
        // Vérifier qu'au moins une ligne a été sélectionnée
        if (empty($selectedInvoiceLineIds)) {
            return redirect()->back()->with('error', 'Veuillez sélectionner au moins une ligne de facture.');
        }
        
        // Récupérer les lignes de factures sélectionnées
        $invoiceLines = InvoiceLines::whereIn('id', $selectedInvoiceLineIds)->get();
        $LastCreditNote = CreditNotes::orderBy('id', 'desc')->first();
        $codeCreditNote = $LastCreditNote ? $LastCreditNote->id : 0;
        $codeCreditNote = $this->documentCodeGenerator->generateDocumentCode('credit-note', $codeCreditNote);

        // Créer un nouvel avoir
        $creditNote = CreditNotes::create([
            'code' => $codeCreditNote, 
            'label' => $codeCreditNote,
            'invoices_id' => $invoiceLines->first()->invoices_id, 
            'companies_id' => $invoiceLines->first()->invoice->companies_id, 
            'companies_contacts_id' => $invoiceLines->first()->invoice->companies_contacts_id, 
            'companies_addresses_id' => $invoiceLines->first()->invoice->companies_addresses_id, 
            'statu' => '1',
            'user_id' => Auth::id(), 
            'reason' => '', 
            'validated_by' => null, 
            'validated_at' => null, 
        ]);
        
        // Créer les lignes d'avoir
        foreach ($invoiceLines as $invoiceLine) {
            CreditNoteLines::create([
                'credit_note_id' => $creditNote->id,
                'order_line_id' => $invoiceLine->order_line_id,
                'invoice_line_id' => $invoiceLine->id,
                'product_id' => $invoiceLine->orderLine->product_id,
                'qty' => $invoiceLine->qty,
                'unit_price' => $invoiceLine->orderLine->selling_price,
            ]);
        }
        
        return redirect()->route('credit.notes.show', ['id' =>  $creditNote->id])->with('success', 'Successfully created credit note');
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

        /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateCreditNoteRequest $request)
    {
        $CreditNote = CreditNotes::find($request->id);
        $CreditNote->label=$request->label;
        $CreditNote->statu=$request->statu;
        $CreditNote->reason=$request->reason;
        $CreditNote->save();

        return redirect()->route('credit.notes.show', ['id' =>  $CreditNote->id])->with('success', 'Successfully updated credit note');
    }
}
