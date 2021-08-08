<?php

namespace App\Http\Controllers\Quality;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Quality\QualityCause;
use App\Models\Quality\QualityAction;
use App\Models\Quality\QualityFailure;
use App\Models\Quality\QualityCorrection;
use App\Models\Quality\QualityDerogation;
use App\Models\Quality\QualityControlDevice;
use App\Models\Quality\QualityNonConformity;

class QualityController extends Controller
{
    public function index()
    {

        $QualityActions = QualityAction::orderBy('id')->paginate(10);
        $QualityCauses = QualityCause::All();
        $QualityFailures = QualityFailure::All();
        $QualityCorrections = QualityCorrection::All();
        $QualityDerogations = QualityDerogation::orderBy('id')->paginate(10);
        $QualityNonConformitys = QualityNonConformity::orderBy('id')->paginate(10);
        $QualityControlDevices = QualityControlDevice::orderBy('id')->paginate(10);

        $userSelect = User::select('id', 'name')->get();

        return view('quality/quality-index', [
            'QualityActions' => $QualityActions,
            'QualityCauses' => $QualityCauses,
            'QualityFailures' => $QualityFailures,
            'QualityCorrections' => $QualityCorrections,
            'QualityDerogations' => $QualityDerogations,
            'QualityNonConformitys' => $QualityNonConformitys,
            'QualityControlDevices' => $QualityControlDevices,
            'userSelect' => $userSelect,
        ]);
    }
}
