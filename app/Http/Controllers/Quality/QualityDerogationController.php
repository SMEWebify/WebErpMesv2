<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SelectDataService;
use App\Models\Quality\QualityDerogation;
use App\Http\Requests\Quality\StoreQualityDerogationRequest;
use App\Http\Requests\Quality\UpdateQualityDerogationRequest;

class QualityDerogationController extends Controller
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
        $QualityDerogations = QualityDerogation::orderBy('id')->paginate(10);
        $LastDerogation =  DB::table('quality_derogations')->orderBy('id', 'desc')->first();
        
        return view('quality/quality-derogations', [
            'LastDerogation' =>  $LastDerogation,
            'QualityDerogations' => $QualityDerogations,
            'NonConformitysSelect' =>  $NonConformitysSelect,
            'userSelect' => $userSelect,
        ]);
    }
    
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreQualityDerogationRequest $request)
    {
        $Derogation =  QualityDerogation::create($request->only('code',
                                                                'label', 
                                                                'statu',
                                                                'type', 
                                                                'user_id',
                                                                'pb_descp',  
                                                                'proposal', 
                                                                'reply', 
                                                                'quality_non_conformitie_id',  
                                                                'decision'));
        return redirect()->route('quality.derogation')->with('success', 'Successfully created derogation.');
    }

    /**
    * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateQualityDerogationRequest $request)
    {
        $QualityDerogation = QualityDerogation::find($request->id);
        $QualityDerogation->label=$request->label;
        $QualityDerogation->statu=$request->statu;
        $QualityDerogation->type=$request->type;
        $QualityDerogation->user_id=$request->user_id;
        $QualityDerogation->pb_descp=$request->pb_descp;
        $QualityDerogation->proposal=$request->proposal;
        $QualityDerogation->reply=$request->reply;
        $QualityDerogation->quality_non_conformitie_id=$request->quality_non_conformitie_id;
        $QualityDerogation->decision=$request->decision;
        $QualityDerogation->save();
        return redirect()->route('quality.derogation')->with('success', 'Successfully updated derogation.');
    }
}
