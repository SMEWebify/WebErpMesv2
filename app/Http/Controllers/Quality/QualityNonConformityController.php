<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Services\SelectDataService;
use App\Models\Quality\QualityNonConformity;
use App\Http\Requests\Quality\StoreQualityNonConformityRequest;
use App\Http\Requests\Quality\UpdateQualityNonConformityRequest;

class QualityNonConformityController extends Controller
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
        $ServicesSelect = $this->SelectDataService->getServices();
        $CompaniesSelect = Companies::select('id', 'code','client_type','civility','label','last_name')->orderBy('label')->get();
        $CausesSelect = $this->SelectDataService->getQualityCause();
        $FailuresSelect = $this->SelectDataService->getQualityFailure();
        $CorrectionsSelect = $this->SelectDataService->getQualityCorrection();
        
        $NonConformitysSelect = $this->SelectDataService->getQualityNonConformity();
        $QualityNonConformitys = QualityNonConformity::orderBy('id')->paginate(10);
        $LastNonConformity =  DB::table('quality_non_conformities')->orderBy('id', 'desc')->first();
        
        return view('quality/quality-non-conformities', [
            'LastNonConformity' => $LastNonConformity,
            'QualityNonConformitys' => $QualityNonConformitys,
            'NonConformitysSelect' =>  $NonConformitysSelect,
            'userSelect' => $userSelect,
            'ServicesSelect' =>  $ServicesSelect,
            'CompaniesSelect' =>  $CompaniesSelect,
            'CausesSelect' =>  $CausesSelect,
            'CorrectionsSelect' => $CorrectionsSelect,
            'FailuresSelect' =>  $FailuresSelect,
        ]);
    }
    
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreQualityNonConformityRequest $request)
    {
        $NonConformity =  QualityNonConformity::create($request->only('code',
                                                                'label', 
                                                                'statu',
                                                                'type', 
                                                                'user_id',
                                                                'service_id',  
                                                                'failure_id',  
                                                                'failure_comment', 
                                                                'causes_id', 
                                                                'causes_comment',  
                                                                'correction_id',  
                                                                'correction_comment',   
                                                                'companie_id'));
        return redirect()->route('quality.nonConformitie')->with('success', 'Successfully created non conformitie.');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateQualityNonConformityRequest $request)
    {
        $QualityNonConformity = QualityNonConformity::find($request->id);
        $QualityNonConformity->label=$request->label;
        $QualityNonConformity->statu=$request->statu;
        $QualityNonConformity->type=$request->type;
        $QualityNonConformity->user_id=$request->user_id;
        $QualityNonConformity->service_id=$request->service_id;
        $QualityNonConformity->failure_id=$request->failure_id;
        $QualityNonConformity->failure_comment=$request->failure_comment;
        $QualityNonConformity->causes_id=$request->causes_id;
        $QualityNonConformity->causes_comment=$request->causes_comment;
        $QualityNonConformity->correction_id=$request->correction_id;
        $QualityNonConformity->correction_comment=$request->correction_comment;
        $QualityNonConformity->companie_id=$request->companie_id;
        $QualityNonConformity->qty=$request->qty;
        $QualityNonConformity->save();
        return redirect()->route('quality.nonConformitie')->with('success', 'Successfully updated non conformitie.');
    }
}
