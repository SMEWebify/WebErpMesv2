<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\ImportCsvService;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Models\Workflow\InvoiceLines;
use App\Models\Accounting\AccountingEntry;
use App\Models\Admin\Factory;
use App\Exports\InvoiceLinesExport;
use App\Exports\AccountingEntryLinesExport;
use Maatwebsite\Excel\Facades\Excel;

class ImportsExportsController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }
    
    /**
     * Display the import/export view.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $ServicesSelect = $this->SelectDataService->getServices();
        $UnitsSelect    = $this->SelectDataService->getUnitsSelect();
        $FamiliesSelect = $this->SelectDataService->getFamilies();

        $factory = app('Factory');
        $fiscal  = $factory ? $factory->getCurrentFiscalYear() : null;

        return view('admin/factory-import-export', [
            'FamiliesSelect' => $FamiliesSelect,
            'UnitsSelect'    => $UnitsSelect,
            'ServicesSelect' => $ServicesSelect,
            'fecStartDate'   => $fiscal ? $fiscal['start']->format('Y-m-d') : now()->startOfYear()->format('Y-m-d'),
            'fecEndDate'     => $fiscal ? $fiscal['end']->format('Y-m-d')   : now()->endOfYear()->format('Y-m-d'),
        ]);
    }

    /**
     * Handle the import of companies from a CSV file.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\ImportCsvService $importCsvService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importCompanies(Request $request, ImportCsvService $importCsvService)
    {   
        $importCsvService->importCompanies($request);
        return redirect()->back();
    }

    /**
     * Handle the import of companies from a CSV file.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\ImportCsvService $importCsvService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importQuotes (Request $request, ImportCsvService $importCsvService)
    {   
        $importCsvService->importQuotes($request);
        return redirect()->back();
    }

    /**
     * Handle the import of companies from a CSV file.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\ImportCsvService $importCsvService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importOrders (Request $request, ImportCsvService $importCsvService)
    {   
        $importCsvService->importOrders($request);
        return redirect()->back();
    }
    
    /**
     * Handle the import of companies from a CSV file.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\ImportCsvService $importCsvService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importProducts (Request $request, ImportCsvService $importCsvService)
    {
        $importCsvService->importProducts($request);
        return redirect()->back();
    }

    public function invoiceExportJsonList()
    {
        $lines = InvoiceLines::with([
            'invoice.companie',
            'orderLine.order',
            'orderLine.Unit',
            'orderLine.VAT',
            // Référentiels portés directement par la ligne (lignes libres)
            'Unit',
            'VAT',
        ])
        ->whereHas('invoice', fn ($q) => $q->where('invoice_type', 1))
        ->where('exported', false)
        ->get();

        return response()->json($lines->map(fn($l) => [
            'id'              => $l->id,
            'invoice_code'    => $l->invoice?->code,
            'order_id'        => $l->orderLine?->order?->id,
            'order_code'      => $l->orderLine?->order?->code,
            'companie_id'     => $l->invoice?->companies_id,
            'companie_label'  => $l->invoice?->companie?->label,
            'line_code'       => $l->display_code,
            'line_label'      => $l->display_label,
            'qty'             => $l->qty,
            'unit'            => $l->display_unit_label,
            'formatted_price' => $l->formatted_selling_price,
            'discount'        => $l->resolved_discount,
            'vat_rate'        => $l->resolved_vat_rate,
        ]));
    }

    public function invoiceExport(Request $request, $ext)
    {
        if (!in_array($ext, ['csv', 'xlsx'])) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $ids = array_filter((array) $request->input('ids', []));

        InvoiceLines::whereIn('id', $ids)->update(['exported' => true]);

        return Excel::download(new InvoiceLinesExport(collect($ids)), "invoiceLines.{$ext}");
    }

    public function fecExportJsonList(Request $request)
    {
        $journalCodes = $request->input('journal_codes', ['ACHAT', 'VENT']);
        $startDate    = $request->input('start_date');
        $endDate      = $request->input('end_date');

        $query = AccountingEntry::where('exported', false);

        if (!empty($journalCodes)) {
            $query->whereIn('journal_code', $journalCodes);
        }
        if ($startDate) {
            $query->where('accounting_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('accounting_date', '<=', $endDate);
        }

        return response()->json($query->get()->map(fn($e) => [
            'id'                        => $e->id,
            'journal_code'              => $e->journal_code,
            'journal_label'             => $e->journal_label,
            'sequence_number'           => $e->sequence_number,
            'accounting_date'           => $e->accounting_date,
            'account_number'            => $e->account_number,
            'account_label'             => $e->account_label,
            'justification_reference'   => $e->justification_reference,
            'justification_date'        => $e->justification_date,
            'auxiliary_account_number'  => $e->auxiliary_account_number,
            'auxiliary_account_label'   => $e->auxiliary_account_label,
            'document_reference'        => $e->document_reference,
            'document_date'             => $e->document_date,
            'entry_label'               => $e->entry_label,
            'formatted_debit'           => $e->formatted_debit_amount,
            'formatted_credit'          => $e->formatted_credit_amount,
            'entry_lettering'           => $e->entry_lettering,
            'currency_code'             => $e->currency_code,
        ]));
    }

    public function fecExport(Request $request, $ext)
    {
        if (!in_array($ext, ['csv', 'xlsx', 'pdf'])) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $ids = array_filter((array) $request->input('ids', []));

        AccountingEntry::whereIn('id', $ids)->update(['exported' => true]);

        return Excel::download(new AccountingEntryLinesExport(collect($ids)), "FecLines.{$ext}");
    }
}
