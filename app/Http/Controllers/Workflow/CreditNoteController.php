<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\NextPreviousTrait;
use Illuminate\Support\Facades\DB;
use App\Models\Workflow\CreditNotes;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\CreditNoteLines;
use App\Services\CreditNoteCalculatorService;
use App\Http\Requests\Workflow\UpdateCreditNoteRequest;

class CreditNoteController extends Controller
{
    use NextPreviousTrait;
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $CurentYear = Carbon::now()->format('Y');
        //Delivery data for chart
        $data['creditNotesDataRate'] = DB::table('credit_notes')
                                    ->select('statu', DB::raw('count(*) as CreditNotesCountRate'))
                                    ->groupBy('statu')
                                    ->get();
        //Delivery data for chart
        $data['creditNoteMonthlyRecap'] = DB::table('credit_note_lines')
                                    ->join('order_lines', 'credit_note_lines.order_line_id', '=', 'order_lines.id')
                                    ->selectRaw('
                                        MONTH(credit_note_lines.created_at) AS month,
                                        SUM((order_lines.selling_price * credit_note_lines.qty)-(order_lines.selling_price * credit_note_lines.qty)*(order_lines.discount/100)) AS orderSum
                                    ')
                                    ->whereYear('credit_note_lines.created_at', $CurentYear)
                                    ->groupByRaw('MONTH(credit_note_lines.created_at) ')
                                    ->get();

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
        // Créer un nouvel avoir
        $creditNote = CreditNotes::create([
            'code' => 'CN-' . time(), 
            'label' => 'CN-' . time(),
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
        
        $CreditNoteCalculatorService = new CreditNoteCalculatorService($id);
        $totalPrice = $CreditNoteCalculatorService->getTotalPrice();
        $subPrice = $CreditNoteCalculatorService->getSubTotal();
        $vatPrice = $CreditNoteCalculatorService->getVatTotal();
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
