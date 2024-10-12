<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Services\ImportCsvService;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;

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
    
    
}
