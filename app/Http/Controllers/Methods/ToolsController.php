<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Services\SelectDataService;
use App\Models\Methods\MethodsTools;
use App\Http\Requests\Methods\StoreToolRequest;
use App\Http\Requests\Methods\UpdateToolRequest;

class ToolsController extends Controller
{
    
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }
    
    /**
     * Display a listing of the tools.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $MethodsTools = MethodsTools::orderBy('code')->get();
        return view('methods/methods-tools', [
            'MethodsTools' => $MethodsTools,
        ]);
    }
    
    /**
     * Store a newly created tool in storage.
     *
     * @param \App\Http\Requests\Methods\StoreToolRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreToolRequest $request)
    {
        $Tool =  MethodsTools::create($request->only('code','label', 'cost', 'end_date','comment', 'qty'));

        if($request->ETAT) $Tool->ETAT=1;
        else $Tool->ETAT = 2;
        $Tool->save();

        if($request->hasFile('picture')){
            $Tool = MethodsTools::findOrFail($Tool->id);
            $path = $request->file('picture')->store('images/tools', 'public');
            $Tool->update(['picture' => basename($path)]);
            $Tool->save();
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }

        return redirect()->route('methods.tool')->with('success', 'Successfully created tool.');
    }

    /**
     * Update the specified tool in storage.
     *
     * @param \App\Http\Requests\Methods\UpdateToolRequest $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateToolRequest $request)
    {
        $tool = MethodsTools::findOrFail($request->id);

        $tool->update([
            'label' => $request->label,
            'ETAT' => $request->etat_update ? 1 : 2,
            'cost' => $request->cost,
            'end_date' => $request->end_date,
            'qty' => $request->qty,
        ]);

        return redirect()->route('methods.tool')->with('success', 'Successfully updated tool.');
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
            $Service = MethodsTools::findOrFail($request->id);
            $path = $request->file('picture')->store('images/tools', 'public');
            $Service->update(['picture' => basename($path)]);
            $Service->save();
            return redirect()->route('methods.tool')->with('success', 'Successfully updated tool.');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }
}
