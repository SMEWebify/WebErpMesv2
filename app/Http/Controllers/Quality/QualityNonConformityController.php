<?php

namespace App\Http\Controllers\Quality;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Services\SelectDataService;
use App\Models\Workflow\DeliveryLines;
use App\Models\Quality\QualityNonConformity;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NonConformityNotification;
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
                                                                
        // notification for all user in database
        $users = User::where('non_conformity_notification', 1)->get();
        Notification::send($users, new NonConformityNotification($NonConformity));
        return redirect()->route('quality.nonConformitie')->with('success', 'Successfully created non conformitie.');
    }

    public function createNCFromDelivery($id){
        $DeliveryLine = DeliveryLines::find($id);
        $NewNonConformity = QualityNonConformity::create([
            'code'=> "NC-OR-#". $DeliveryLine->OrderLine->orders_id,
            'label'=>"NC-L-#". $DeliveryLine->OrderLine->id,
            'statu'=>1,
            'type'=>2,
            'user_id'=>$DeliveryLine->delivery->user_id,
            'companie_id'=>$DeliveryLine->delivery->companies_id,
            'order_lines_id'=>$DeliveryLine->OrderLine->id,
            'deliverys_id'=>$DeliveryLine->deliverys_id,
            'delivery_line_id'=>$id,
        ]);

        // notification for all user in database
        $users = User::where('non_conformity_notification', 1)->get();
        Notification::send($users, new NonConformityNotification($NewNonConformity));
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
