<?php

namespace App\Http\Controllers\Quality;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Models\Quality\QualityCause;
use App\Models\Quality\QualityAction;
use App\Models\Quality\QualityFailure;
use App\Models\Methods\MethodsServices;
use App\Models\Quality\QualityCorrection;
use App\Models\Quality\QualityDerogation;
use App\Models\Quality\QualityControlDevice;
use App\Models\Quality\QualityNonConformity;

class QualityController extends Controller
{
    public function index()
    {
        $QualityActions = QualityAction::orderBy('id')->paginate(10);
        $LastAction =  DB::table('quality_actions')->orderBy('id', 'desc')->first();
        $QualityCauses = QualityCause::All();
        $CausesSelect = QualityCause::select('id', 'LABEL')->orderBy('LABEL')->get();
        $QualityFailures = QualityFailure::All();
        $FailuresSelect = QualityFailure::select('id', 'LABEL')->orderBy('LABEL')->get();
        $QualityCorrections = QualityCorrection::All();
        $CorrectionsSelect = QualityCorrection::select('id', 'LABEL')->orderBy('LABEL')->get();
        $QualityDerogations = QualityDerogation::orderBy('id')->paginate(10);
        $QualityNonConformitys = QualityNonConformity::orderBy('id')->paginate(10);
        $NonConformitysSelect = QualityNonConformity::select('id', 'CODE')->orderBy('CODE')->get();
        $LastNonConformity =  DB::table('quality_non_conformities')->orderBy('id', 'desc')->first();
        $QualityControlDevices = QualityControlDevice::orderBy('id')->paginate(10);
        $QualityDerogations = QualityDerogation::orderBy('id')->paginate(10);
        $LastDerogation =  DB::table('quality_derogations')->orderBy('id', 'desc')->first();
        $userSelect = User::select('id', 'name')->get();
        $ServicesSelect = MethodsServices::select('id', 'LABEL')->orderBy('LABEL')->get();
        $CompaniesSelect = Companies::select('id', 'LABEL')->orderBy('LABEL')->get();
        
        return view('quality/quality-index', [
            'QualityActions' => $QualityActions,
            'LastAction' => $LastAction,
            'QualityDerogations' => $QualityDerogations, 
            'LastDerogation' =>  $LastDerogation,
            'QualityCauses' => $QualityCauses,
            'CausesSelect' =>  $CausesSelect,
            'QualityFailures' => $QualityFailures,
            'FailuresSelect' =>  $FailuresSelect,
            'QualityCorrections' => $QualityCorrections,
            'CorrectionsSelect' => $CorrectionsSelect ,
            'QualityDerogations' => $QualityDerogations,
            'QualityNonConformitys' => $QualityNonConformitys,
            'NonConformitysSelect' =>  $NonConformitysSelect,
            'LastNonConformity' => $LastNonConformity,
            'QualityControlDevices' => $QualityControlDevices,
            'userSelect' => $userSelect,
            'ServicesSelect' =>  $ServicesSelect,
            'CompaniesSelect' =>  $CompaniesSelect
        ]);
    }
}
