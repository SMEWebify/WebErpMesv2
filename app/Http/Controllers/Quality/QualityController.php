<?php

namespace App\Http\Controllers\Quality;

use App\Services\QualityKPIService;
use App\Services\SelectDataService;
use App\Models\Quality\QualityCause;
use App\Models\Quality\QualityFailure;
use App\Models\Quality\QualityCorrection;
use App\Models\Quality\QualityControlDevice;

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

        $calibrationDueSoonDevices = QualityControlDevice::with('UserManagement')
            ->calibrationDueSoon()
            ->get();

        $calibrationOverdueDevices = QualityControlDevice::with('UserManagement')
            ->calibrationOverdue()
            ->get();

        // Using the QualityKPIService to retrieve KPI data
        $generalStats = $this->qualityKPIService->getGeneralStatistics();
        $rates = $this->qualityKPIService->getInternalExternalRates();
        $chartData = $this->qualityKPIService->getTopGenerators();
        $statusCounts = $this->qualityKPIService->getStatusCounts();
        $litigationRate = $this->qualityKPIService->GetCalculateLitigationRate();
        $resolutionTimes = $this->qualityKPIService->getAverageResolutionTime();

        
        return view('quality/quality-index', array_merge([
                                                        'QualityCauses' => $QualityCauses,
                                                        'QualityFailures' => $QualityFailures,
                                                        'QualityCorrections' => $QualityCorrections,
                                                        'QualityControlDevices' => $QualityControlDevices,
                                                        'userSelect' => $userSelect,
                                                        'ServicesSelect' =>  $ServicesSelect,
                                                        'calibrationDueSoonDevices' => $calibrationDueSoonDevices,
                                                        'calibrationOverdueDevices' => $calibrationOverdueDevices,
                                                        'calibrationAlertThresholdDays' => QualityControlDevice::CALIBRATION_ALERT_THRESHOLD_DAYS,
                                                        'chartData'=> $chartData,
                                                        'litigationRate'=> $litigationRate,
                                                        'resolutionTimes'=> $resolutionTimes,
                                                        ]
                                                        , $generalStats, $rates, $statusCounts));
    }
}
