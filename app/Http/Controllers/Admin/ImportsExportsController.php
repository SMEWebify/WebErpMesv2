<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\ImportCsvService;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Models\Workflow\InvoiceLines;
use App\Exports\InvoiceLinesExport;
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
        $UnitsSelect = $this->SelectDataService->getUnitsSelect();
        $FamiliesSelect = $this->SelectDataService->getFamilies();
        return view('admin/factory-import-export', [
            'FamiliesSelect' => $FamiliesSelect,
            'UnitsSelect' => $UnitsSelect,
            'ServicesSelect' => $ServicesSelect,
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
        ])->where('exported', false)->get();

        return response()->json($lines->map(fn($l) => [
            'id'              => $l->id,
            'invoice_code'    => $l->invoice?->code,
            'order_id'        => $l->orderLine?->order?->id,
            'order_code'      => $l->orderLine?->order?->code,
            'companie_id'     => $l->invoice?->companies_id,
            'companie_label'  => $l->invoice?->companie?->label,
            'line_code'       => $l->orderLine?->code,
            'line_label'      => $l->orderLine?->label,
            'qty'             => $l->qty,
            'unit'            => $l->orderLine?->Unit?->label,
            'formatted_price' => $l->formatted_selling_price,
            'discount'        => $l->orderLine?->discount,
            'vat_rate'        => $l->orderLine?->VAT?->rate,
        ]));
    }

    public function invoiceExport(Request $request, $ext)
    {
        if (!in_array($ext, ['csv', 'xlsx'])) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $ids = array_filter((array) $request->input('ids', []));

        InvoiceLines::whereIn('id', $ids)->update(['exported' => true]);

        return Excel::download(new InvoiceLinesExport(collect($ids)), 'invoiceLines.' . $ext);
    }
}
