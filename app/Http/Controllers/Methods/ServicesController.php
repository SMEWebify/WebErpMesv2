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
        $MethodsServices = MethodsServices::orderBy('ordre')->get();
        $SupplierSelect = $this->SelectDataService->getSupplier();
        return view('methods/methods-services', [
            'MethodsServices' => $MethodsServices,
            'SupplierSelect' => $SupplierSelect,
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
        $Service =  MethodsServices::create($request->only('code','ordre', 'label','type', 'hourly_rate','margin', 'color', 'compannie_id'));
        
        if($request->hasFile('picture')){
            $Service = MethodsServices::findOrFail($Service->id);
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/methods'), $filename);
            $Service->update(['picture' => $filename]);
            $Service->save();
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }

        return redirect()->route('methods.service')->with('success', 'Successfully created service.');
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
        $service->update($request->only(['ordre', 'label', 'type', 'hourly_rate', 'margin', 'color', 'compannie_id']));
        return redirect()->route('methods.service')->with('success', 'Successfully updated service.');
    }

    /**
     * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreImage(Request $request)
    {
        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $Service = MethodsServices::findOrFail($request->id);
            $file =  $request->file('picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $request->picture->move(public_path('images/methods'), $filename);
            $Service->update(['picture' => $filename]);
            $Service->save();
            return redirect()->route('methods.service')->with('success', 'Successfully updated service.');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }
}
