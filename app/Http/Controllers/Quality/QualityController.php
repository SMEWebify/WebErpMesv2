<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Services\QualityKPIService;
use App\Services\SelectDataService;
use App\Models\Quality\QualityCause;
use App\Models\Quality\QualityAction;
use App\Models\Quality\QualityFailure;
use App\Models\Quality\QualityCorrection;
use App\Models\Quality\QualityDerogation;
use App\Models\Quality\QualityControlDevice;
use App\Models\Quality\QualityNonConformity;

class QualityController extends Controller
{
    
    protected $SelectDataService;
    protected $qualityKPIService;

    public function __construct(SelectDataService $SelectDataService, QualityKPIService $qualityKPIService)
    {
        $this->SelectDataService = $SelectDataService;
        $this->qualityKPIService = $qualityKPIService;
    }
    
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $userSelect = $this->SelectDataService->getUsers();
        $ServicesSelect = $this->SelectDataService->getServices();

        $QualityCauses = QualityCause::All();
        $QualityFailures = QualityFailure::All();
        $QualityCorrections = QualityCorrection::All();
        $QualityControlDevices = QualityControlDevice::orderBy('id')->paginate(10);

        // Using the QualityKPIService to retrieve KPI data
        $generalStats = $this->qualityKPIService->getGeneralStatistics();
        $rates = $this->qualityKPIService->getInternalExternalRates();
        $chartData = $this->qualityKPIService->getTopGenerators();
        $statusCounts = $this->qualityKPIService->getStatusCounts();

        
        return view('quality/quality-index', array_merge([
                                                        'QualityCauses' => $QualityCauses,
                                                        'QualityFailures' => $QualityFailures,
                                                        'QualityCorrections' => $QualityCorrections,
                                                        'QualityControlDevices' => $QualityControlDevices,
                                                        'userSelect' => $userSelect,
                                                        'ServicesSelect' =>  $ServicesSelect,
                                                        'chartData'=> $chartData,
                                                        ]
                                                        , $generalStats, $rates, $statusCounts));
    }
}
