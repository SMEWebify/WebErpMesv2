<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ImportCsvService;

class ImportsExportsController extends Controller
{
    /**
     * Display the import/export view.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('admin/factory-import-export');
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
}
