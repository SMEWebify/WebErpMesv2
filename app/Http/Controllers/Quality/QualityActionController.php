<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Support\Facades\DB;
use App\Services\SelectDataService;
use App\Models\Quality\QualityAction;
use App\Http\Requests\Quality\StoreQualityActionRequest;
use App\Http\Requests\Quality\UpdateQualityActionRequest;

class QualityActionController extends Controller
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
        $QualityActions = QualityAction::orderBy('id')->paginate(10);
        $LastAction =  DB::table('quality_actions')->orderBy('id', 'desc')->first();
        
        return view('quality/quality-actions', [
            'LastAction' => $LastAction,
            'QualityActions' => $QualityActions,
            'NonConformitysSelect' =>  $NonConformitysSelect,
            'userSelect' => $userSelect,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreQualityActionRequest $request)
    {
        $Action = QualityAction::create($request->only('code',
                                                        'label', 
                                                        'statu',
                                                        'type', 
                                                        'user_id',
                                                        'pb_descp',  
                                                        'cause',  
                                                        'action', 
                                                        'color', 
                                                        'quality_non_conformitie_id'));
        return redirect()->route('quality.action')->with('success', 'Successfully created action.');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateQualityActionRequest $request)
    {
        $QualityAction = QualityAction::find($request->id);
        $QualityAction->label=$request->label;
        $QualityAction->statu=$request->statu;
        $QualityAction->type=$request->type;
        $QualityAction->user_id=$request->user_id;
        $QualityAction->pb_descp=$request->pb_descp;
        $QualityAction->cause=$request->cause;
        $QualityAction->action=$request->action;
        $QualityAction->color=$request->color;
        $QualityAction->quality_non_conformitie_id=$request->quality_non_conformitie_id;
        $QualityAction->save();
        return redirect()->route('quality.action')->with('success', 'Successfully updated action.');
    }
}
