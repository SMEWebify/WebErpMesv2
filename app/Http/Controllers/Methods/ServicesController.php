<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Services\SelectDataService;
use App\Models\Methods\MethodsServices;
use App\Http\Requests\Methods\StoreServicesRequest;
use App\Http\Requests\Methods\UpdateServicesRequest;

class ServicesController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }
    
    /**
     * Display a listing of the service.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $MethodsServices = MethodsServices::with('Suppliers')->orderBy('ordre')->get();
        $CompanieSelect = $this->SelectDataService->getSupplier();
        return view('methods/methods-services', [
            'MethodsServices' => $MethodsServices,
            'CompanieSelect' => $CompanieSelect,
        ]);
    }
    
    /**
     * Store a newly created service in storage.
     *
     * @param \App\Http\Requests\Methods\StoreServicesRequest $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreServicesRequest $request)
    {
        $supplierIds = collect($request->input('companies_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $serviceData = $request->only('code', 'ordre', 'label', 'type', 'hourly_rate', 'margin', 'color');
        $serviceData['companies_id'] = $supplierIds->first();
        $Service = MethodsServices::create($serviceData);
        $Service->Suppliers()->sync($supplierIds);
        
        if($request->hasFile('picture')){
            $Service = MethodsServices::findOrFail($Service->id);
            $path = $request->file('picture')->store('images/methods', 'public');
            $Service->update(['picture' => basename($path)]);
            $Service->save();
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }

        return redirect()->route('methods.service')->with('success', 'Successfully created service.');
    }

    /**
     * Display the specified service.
     *
     * @param  int  $id  The ID of the service to display.
     * @return \Illuminate\View\View  The view displaying the service details.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  If no service is found with the given ID.
     */
    public function show($id)
    {
        $factory = app('Factory');  
        $service = MethodsServices::with('Suppliers')->findOrFail($id);
        return view('methods/methods-services-show', [
            'service' => $service,
            'factory' => $factory,
        ]);
    }

    /**
     * Update the specified service in storage.
     *
     * @param \App\Http\Requests\Methods\UpdateServicesRequest $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateServicesRequest $request)
    {
        $service = MethodsServices::findOrFail($request->id);
        $supplierIds = collect($request->input('companies_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $serviceData = $request->only(['ordre', 'label', 'type', 'hourly_rate', 'margin', 'color']);
        $serviceData['companies_id'] = $supplierIds->first();
        $service->update($serviceData);
        $service->Suppliers()->sync($supplierIds);
        return redirect()->route('methods.service')->with('success', 'Successfully updated service.');
    }

    /**
     * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreImage(Request $request)
    {
        $request->validate([
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $Service = MethodsServices::findOrFail($request->id);
            $path = $request->file('picture')->store('images/methods', 'public');
            $Service->update(['picture' => basename($path)]);
            $Service->save();
            return redirect()->route('methods.service')->with('success', 'Successfully updated service.');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }
}
