<?php

namespace App\Http\Controllers\Companies;

use Illuminate\Http\Request;
use App\Traits\NextPreviousTrait;
use App\Models\Companies\Companies;
use App\Services\SelectDataService;
use App\Http\Requests\Companies\UpdateCompanieRequest;

class CompaniesController extends Controller
{
    use NextPreviousTrait;
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
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
        //Quote data for chart
        $data = $this->getCompanyCounts();
        //5 lastest Companies add 
        $LastComapnies = Companies::orderBy('id', 'desc')->take(5)->get();
        return view('companies/companies-index', compact('data', 'LastComapnies'));
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Companies $id)
    {
        $userSelect = $this->SelectDataService->getUsers();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Companies(), $id->id);
        $Companie = $id;
        return view('companies/companies-show', compact('Companie', 'userSelect', 'previousUrl', 'nextUrl'));
    }

    /**
     * @param $id
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateCompanieRequest $request)
    {
        $company = Companies::findOrFail($request->id);
        // Update company attributes using mass assignment with validation
        $company->update($request->validated());
        // Handle specific cases outside mass assignment
        $company->active = $request->has('active') ? 1 : 0;
        $company->save();

        return redirect()->route('companies.show', ['id' =>  $company->id])->with('success', 'Successfully updated companie');
    }
}
