<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Support\Facades\DB;
use App\Services\SelectDataService;
use App\Models\Quality\QualityAmdec;
use App\Http\Requests\Quality\StoreQualityAmdecRequest;
use App\Http\Requests\Quality\UpdateQualityAmdecRequest;

class QualityAmdecController extends Controller
{

    protected $SelectDataService;
    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }
    
    
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {

        $userSelect = $this->SelectDataService->getUsers();
        $NonConformitysSelect = $this->SelectDataService->getQualityNonConformity();
        $ProductSelect = $this->SelectDataService->getProductsSelect();
        $QualityAmdecs = QualityAmdec::orderBy('id', 'desc')->paginate(10);
        $LastAction =  DB::table('quality_actions')->orderBy('id', 'desc')->first();
        
        return view('quality/quality-amdec', [
            'LastAction' => $LastAction,
            'QualityAmdecs' => $QualityAmdecs,
            'NonConformitysSelect' =>  $NonConformitysSelect,
            'userSelect' => $userSelect,
            'ProductSelect' => $ProductSelect,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreQualityAmdecRequest $request)
    {
        $Amdec = QualityAmdec::create($request->only(
                                                        'product_id',
                                                        'user_id',
                                                        'failure_mode',
                                                        'effect',
                                                        'cause',
                                                        'severity',
                                                        'occurrence',
                                                        'detection',
                                                        'current_control',
                                                        'recommended_action'
                                                    ));
        return redirect()->route('quality.amdec')->with('success', 'Successfully created AMDEC.');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateQualityAmdecRequest $request)
    {
        $QualityAmdec = QualityAmdec::find($request->id);
        $QualityAmdec->failure_mode=$request->failure_mode;
        $QualityAmdec->effect=$request->effect;
        $QualityAmdec->cause=$request->cause;
        $QualityAmdec->severity=$request->severity;
        $QualityAmdec->occurrence=$request->occurrence;
        $QualityAmdec->detection=$request->detection;
        $QualityAmdec->current_control=$request->current_control;
        $QualityAmdec->recommended_action=$request->recommended_action;
        $QualityAmdec->save();
        return redirect()->route('quality.amdec')->with('success', 'Successfully updated AMDEC.');
    }
}
