<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Models\Workflow\DeliveryLines;
use App\Services\SelectDataService;
use App\Services\DocumentCodeGenerator;
use App\Models\Quality\QualityNonConformity;
use App\Services\QualityNonConformityService;
use App\Http\Requests\Quality\StoreQualityNonConformityRequest;
use App\Http\Requests\Quality\UpdateQualityNonConformityRequest;

class QualityNonConformityController extends Controller
{
    protected $SelectDataService;
    public $qualityNonConformityService;
    protected $documentCodeGenerator;

    public function __construct(SelectDataService $SelectDataService, 
                                QualityNonConformityService $qualityNonConformityService,
                                DocumentCodeGenerator $documentCodeGenerator)
    {
        $this->SelectDataService = $SelectDataService;
        $this->qualityNonConformityService = $qualityNonConformityService;
        $this->documentCodeGenerator = $documentCodeGenerator;
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
        $LastNonConformity = QualityNonConformity::orderBy('id', 'desc')->first();
        $codeNonConformity = $LastNonConformity ? $LastNonConformity->id : 0;
        $codeNonConformity = $this->documentCodeGenerator->generateDocumentCode('non-conformities', $codeNonConformity);
        
        return view('quality/quality-non-conformities', [
            'codeNonConformity' => $codeNonConformity,
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
        
        return redirect()->route('quality.nonConformitie')->with('success', __('general_content.non_conformity_created_success_trans_key'));
    }

    public function createNCFromDelivery($uuid, $id){
        // Ownership check: verify the delivery line belongs to the delivery identified by UUID
        $deliveryLine = DeliveryLines::with('delivery')->findOrFail($id);
        abort_unless($deliveryLine->delivery && $deliveryLine->delivery->uuid === $uuid, 403);
        // Create non-conformity via service
        $this->qualityNonConformityService->createNCFromDelivery($id);
        
        return redirect()->back()->with('success', __('general_content.non_conformity_created_success_trans_key'));
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
        return redirect()->route('quality.nonConformitie')->with('success', __('general_content.non_conformity_updated_success_trans_key'));
    }

    /**
     * Close the resolution date for a specific non-conformity.
     *
     * This method finds a non-conformity by its ID, sets the resolution date to the current date and time,
     * updates the status to 3, and saves the changes. If successful, it redirects back with a success message.
     *
     * @param int $id The ID of the non-conformity to be updated.
     * @return \Illuminate\Http\RedirectResponse Redirects back with a success message upon successful update.
     */
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

    /**
     * Reopen the resolution date of a non-conformity.
     *
     * This method sets the resolution date of the specified non-conformity to null
     * and updates its status to 1, indicating that it has been reopened. After
     * saving the changes, it redirects back to the previous page with a success message.
     *
     * @param int $id The ID of the non-conformity to be reopened.
     * @return \Illuminate\Http\RedirectResponse Redirects back with a success message.
     */
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
