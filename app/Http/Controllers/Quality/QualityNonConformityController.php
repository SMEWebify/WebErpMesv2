<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Services\SelectDataService;
use App\Models\Quality\QualityNonConformity;
use App\Services\QualityNonConformityService;
use App\Http\Requests\Quality\StoreQualityNonConformityRequest;
use App\Http\Requests\Quality\UpdateQualityNonConformityRequest;

class QualityNonConformityController extends Controller
{
    protected $SelectDataService;
    public $qualityNonConformityService;

    public function __construct(SelectDataService $SelectDataService, QualityNonConformityService $qualityNonConformityService)
    {
        $this->SelectDataService = $SelectDataService;
        $this->qualityNonConformityService = $qualityNonConformityService;
    }
    
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {

        $userSelect = $this->SelectDataService->getUsers();
        $ServicesSelect = $this->SelectDataService->getServices();
        $CompanieSelect = Companies::select('id', 'code','client_type','civility','label','last_name')->orderBy('label')->get();
        $CausesSelect = $this->SelectDataService->getQualityCause();
        $FailuresSelect = $this->SelectDataService->getQualityFailure();
        $CorrectionsSelect = $this->SelectDataService->getQualityCorrection();
        
        $NonConformitysSelect = $this->SelectDataService->getQualityNonConformity();
        $QualityNonConformitys = QualityNonConformity::orderBy('id', 'desc')->paginate(10);
        $LastNonConformity =  DB::table('quality_non_conformities')->orderBy('id', 'desc')->first();
        
        return view('quality/quality-non-conformities', [
            'LastNonConformity' => $LastNonConformity,
            'QualityNonConformitys' => $QualityNonConformitys,
            'NonConformitysSelect' =>  $NonConformitysSelect,
            'userSelect' => $userSelect,
            'ServicesSelect' =>  $ServicesSelect,
            'CompanieSelect' =>  $CompanieSelect,
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
        // Create non-conformity via service
        $this->qualityNonConformityService->createNonConformity($request->validated());
        
        return redirect()->route('quality.nonConformitie')->with('success', 'Successfully created non conformitie.');
    }

    public function createNCFromDelivery($id){
        // Create non-conformity via service
        $this->qualityNonConformityService->createNCFromDelivery($id);
        
        return redirect()->back()->with('success', 'Successfully created non conformitie.');
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
        
        if($request->type_update) $QualityNonConformity->type=1;
        else $QualityNonConformity->type = 2;

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

    public function closeResolutionDate($id)
    {
        $nonConformity = QualityNonConformity::findOrFail($id);
        
        if ($nonConformity) {
            $nonConformity->resolution_date = now();
            $nonConformity->statu =3;
            $nonConformity->save();
            
            return redirect()->back()->with('success', 'The resolution date has been updated.');
        }
    }

    public function reopenResolutionDate($id)
    {
        $nonConformity = QualityNonConformity::findOrFail($id);
        
        if ($nonConformity) {
            $nonConformity->resolution_date = null;
            $nonConformity->statu =1;
            $nonConformity->save();
            
            return redirect()->back()->with('success', 'The NC date has been updated.');
        }
    }
}
