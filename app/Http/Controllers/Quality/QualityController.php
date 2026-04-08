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
        $QualityCauses      = QualityCause::all();
        $QualityFailures    = QualityFailure::all();
        $QualityCorrections = QualityCorrection::all();

        $calibrationDueSoonDevices  = QualityControlDevice::with('UserManagement')->calibrationDueSoon()->get();
        $calibrationOverdueDevices  = QualityControlDevice::with('UserManagement')->calibrationOverdue()->get();

        $generalStats   = $this->qualityKPIService->getGeneralStatistics();
        $rates          = $this->qualityKPIService->getInternalExternalRates();
        $chartData      = $this->qualityKPIService->getTopGenerators();
        $statusCounts   = $this->qualityKPIService->getStatusCounts();
        $litigationRate = $this->qualityKPIService->GetCalculateLitigationRate();
        $resolutionTimes = $this->qualityKPIService->getAverageResolutionTime();

        $reactKpi = array_merge($generalStats, [
            'litigationRate'  => $litigationRate,
            'resolutionTimes' => $resolutionTimes,
        ]);

        $reactRates = $rates;

        $reactStatusCounts = $statusCounts;

        $reactCalibrationAlerts = [
            'overdue'       => $calibrationOverdueDevices->map(fn ($d) => [
                'label'              => $d->label,
                'code'               => $d->code,
                'calibration_due_at' => optional($d->calibration_due_at)->format('d/m/Y'),
                'user'               => optional($d->UserManagement)->name,
            ])->values()->all(),
            'dueSoon'       => $calibrationDueSoonDevices->map(fn ($d) => [
                'label'              => $d->label,
                'code'               => $d->code,
                'calibration_due_at' => optional($d->calibration_due_at)->format('d/m/Y'),
                'user'               => optional($d->UserManagement)->name,
            ])->values()->all(),
            'thresholdDays' => QualityControlDevice::CALIBRATION_ALERT_THRESHOLD_DAYS,
        ];

        $reactInitialFailures = $QualityFailures->map(fn ($f) => [
            'id'    => $f->id,
            'code'  => $f->code,
            'label' => $f->label,
        ])->values()->all();

        $reactInitialCauses = $QualityCauses->map(fn ($c) => [
            'id'    => $c->id,
            'code'  => $c->code,
            'label' => $c->label,
        ])->values()->all();

        $reactInitialCorrections = $QualityCorrections->map(fn ($c) => [
            'id'    => $c->id,
            'code'  => $c->code,
            'label' => $c->label,
        ])->values()->all();

        $reactEndpoints = [
            'deviceList'        => route('quality.json.devices'),
            'deviceCreate'      => route('quality.json.devices.create'),
            'deviceUpdate'      => route('quality.json.devices.update', ['id' => '__ID__']),
            'selectData'        => route('quality.json.select-data'),
            'failureCreate'     => route('quality.json.failure.create'),
            'failureUpdate'     => route('quality.json.failure.update', ['id' => '__ID__']),
            'causeCreate'       => route('quality.json.cause.create'),
            'causeUpdate'       => route('quality.json.cause.update', ['id' => '__ID__']),
            'correctionCreate'  => route('quality.json.correction.create'),
            'correctionUpdate'  => route('quality.json.correction.update', ['id' => '__ID__']),
        ];

        $reactTrans = [
            'dashboard'                      => __('general_content.dashboard_trans_key'),
            'measuring_devices'              => __('general_content.measuring_devices_trans_key'),
            'settings'                       => __('general_content.settings_trans_key'),
            'open'                           => __('general_content.open_trans_key'),
            'actions'                        => __('general_content.action_trans_key'),
            'non_conformities'               => __('general_content.non_conformities_trans_key'),
            'derogations'                    => __('general_content.derogations_trans_key'),
            'litigation_rate'                => __('general_content.litigation_rate_trans_key'),
            'resolution_time'                => __('general_content.resolution_time_trans_key'),
            'days'                           => __('general_content.days_trans_key'),
            'internal'                       => __('general_content.internal_trans_key'),
            'external'                       => __('general_content.external_trans_key'),
            'internal_external_rates'        => 'Taux interne / externe',
            'top_generators'                 => 'Top générateurs NC',
            'status_distribution'            => 'Distribution des statuts',
            'in_progress'                    => __('general_content.in_progress_trans_key'),
            'waiting_customer'               => __('general_content.waiting_customer_data_trans_key'),
            'validated'                      => __('general_content.validate_trans_key'),
            'canceled'                       => __('general_content.canceled_trans_key'),
            'calibration_overdue'            => __('general_content.calibration_overdue_trans_key'),
            'calibration_due_soon'           => __('general_content.calibration_due_soon_trans_key'),
            'calibration_due_soon_description' => __('general_content.calibration_due_soon_description_trans_key', ['days' => QualityControlDevice::CALIBRATION_ALERT_THRESHOLD_DAYS]),
            'code'                           => __('general_content.external_id_trans_key'),
            'label'                          => __('general_content.label_trans_key'),
            'service'                        => __('general_content.service_trans_key'),
            'user'                           => __('general_content.user_trans_key'),
            'serial_number'                  => __('general_content.serial_number_trans_key'),
            'calibrated_at'                  => __('general_content.calibrated_at_trans_key'),
            'calibration_due_at'             => __('general_content.calibration_due_at_trans_key'),
            'calibration_status'             => __('general_content.calibration_status_trans_key'),
            'calibration_provider'           => __('general_content.calibration_provider_trans_key'),
            'location'                       => __('general_content.location_trans_key'),
            'capability_index'               => __('general_content.capability_index_trans_key'),
            'picture'                        => __('general_content.picture_trans_key'),
            'choose_file'                    => __('general_content.choose_file_trans_key'),
            'new_measuring_device'           => __('general_content.new_measuring_devices_trans_key'),
            'failure_types'                  => __('general_content.failure_type_trans_key'),
            'cause_types'                    => __('general_content.cause_type_trans_key'),
            'correction_types'               => __('general_content.correction_type_trans_key'),
            'new_failure'                    => __('general_content.new_failure_trans_key'),
            'new_cause'                      => __('general_content.new_cause_trans_key'),
            'new_correction'                 => __('general_content.new_correction_trans_key'),
            'no_data'                        => __('general_content.no_data_trans_key'),
            'save'                           => __('general_content.update_trans_key'),
            'saving'                         => __('general_content.saving_trans_key'),
            'submit'                         => __('general_content.submit_trans_key'),
            'cancel'                         => __('general_content.cancel_trans_key'),
            'update'                         => __('general_content.update_trans_key'),
            'created_success'               => __('general_content.success_trans_key'),
        ];

        return view('quality/quality-index', [
            'reactKpi'                => $reactKpi,
            'reactRates'              => $reactRates,
            'reactStatusCounts'       => $reactStatusCounts,
            'reactTopGenerators'      => $chartData,
            'reactCalibrationAlerts'  => $reactCalibrationAlerts,
            'reactInitialFailures'    => $reactInitialFailures,
            'reactInitialCauses'      => $reactInitialCauses,
            'reactInitialCorrections' => $reactInitialCorrections,
            'reactEndpoints'          => $reactEndpoints,
            'reactTrans'              => $reactTrans,
        ]);
    }

    /**
     * Return users and services for select inputs.
     */
    public function jsonSelectData()
    {
        $users    = $this->SelectDataService->getUsers()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values();
        $services = $this->SelectDataService->getServices()->map(fn ($s) => ['id' => $s->id, 'label' => $s->label])->values();

        return response()->json(['users' => $users, 'services' => $services]);
    }
}
