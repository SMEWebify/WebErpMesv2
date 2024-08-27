<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Models\Quality\QualityCause;
use App\Models\Quality\QualityAction;
use App\Models\Quality\QualityFailure;
use App\Models\Quality\QualityCorrection;
use App\Models\Quality\QualityDerogation;
use App\Models\Quality\QualityControlDevice;
use App\Models\Quality\QualityNonConformity;

class QualityKPIService
{
    public function getGeneralStatistics()
    {
        // Total number of entries for each category
        $totalDerogations = QualityDerogation::count();
        $totalDerogationsOpen = QualityDerogation::where('statu',1)->count();
        $totalNonConformities = QualityNonConformity::count();
        $totalNonConformitiesOpen = QualityNonConformity::where('statu',1)->count();
        $totalActions = QualityAction::count();
        $totalActionsOpen = QualityAction::where('statu',1)->count();

        return compact('totalDerogations', 'totalDerogationsOpen', 'totalNonConformities', 'totalNonConformitiesOpen', 'totalActions', 'totalActionsOpen');
    }

    public function getInternalExternalRates()
    {
        // Number of internal entries for each category
        $internalDerogations = QualityDerogation::where('type', 1)->count();
        $internalNonConformities = QualityNonConformity::where('type', 1)->count();
        $internalActions = QualityAction::where('type', 1)->count();

        // Calculate the internal rate as a percentage for each category
        $totalDerogations = QualityDerogation::count();
        $internalDerogationRate = ($totalDerogations > 0) ? ($internalDerogations / $totalDerogations) * 100 : 0;
        $totalNonConformities = QualityNonConformity::count();
        $internalNonConformityRate = ($totalNonConformities > 0) ? ($internalNonConformities / $totalNonConformities) * 100 : 0;
        $totalActions = QualityAction::count();
        $internalActionRate = ($totalActions > 0) ? ($internalActions / $totalActions) * 100 : 0;

        // External rate as a percentage
        $externalDerogationRate = 100 - $internalDerogationRate;
        $externalNonConformityRate = 100 - $internalNonConformityRate;
        $externalActionRate = 100 - $internalActionRate;

        return compact('internalDerogationRate', 'externalDerogationRate', 'internalNonConformityRate', 'externalNonConformityRate', 'internalActionRate', 'externalActionRate');
    }

    public function getTopGenerators()
    {
        // Query to obtain the 10 largest generators of non-conformities
        $topGenerators = QualityNonConformity::select('companie_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('companie_id')
            ->groupBy('companie_id')
            ->orderByDesc('count')
            ->limit(7)
            ->get();

        // Retrieval of company names associated with identifiers
        $companies = Companies::whereIn('id', $topGenerators->pluck('companie_id'))
            ->pluck('label', 'id');

        // Default color table
        $defaultColors = [
            'rgba(255, 99, 132, 0.2)',
            'rgba(255, 159, 64, 0.2)',
            'rgba(255, 205, 86, 0.2)',
            'rgba(75, 192, 192, 0.2)',
            'rgba(54, 162, 235, 0.2)',
            'rgba(153, 102, 255, 0.2)',
            'rgba(201, 203, 207, 0.2)'
        ];

        // Preparing data for the chart
        $chartData = [
            'labels' => $companies->values()->all(),
            'datasets' => [
                [
                    'label' => __('general_content.non_conformities_trans_key'),
                    'data' => $topGenerators->pluck('count')->all(),
                    'backgroundColor' => $defaultColors,
                    'beginAtZero' => true,
                ],
            ],
        ];

        return $chartData;
    }

    public function getStatusCounts()
    {
        $allStatus = [1, 2, 3, 4];

        $derogationStatusCounts = QualityDerogation::groupBy('statu')
            ->select('statu', DB::raw('count(*) as count'))
            ->pluck('count', 'statu')->toArray();
        $nonConformityStatusCounts = QualityNonConformity::groupBy('statu')
            ->select('statu', DB::raw('count(*) as count'))
            ->pluck('count', 'statu')->toArray();
        $actionStatusCounts = QualityAction::groupBy('statu')
            ->select('statu', DB::raw('count(*) as count'))
            ->pluck('count', 'statu')->toArray();

        foreach ($allStatus as $status) {
            if (!isset($derogationStatusCounts[$status])) {
                $derogationStatusCounts[$status] = 0;
            }
            if (!isset($nonConformityStatusCounts[$status])) {
                $nonConformityStatusCounts[$status] = 0;
            }
            if (!isset($actionStatusCounts[$status])) {
                $actionStatusCounts[$status] = 0;
            }
        }

        ksort($derogationStatusCounts);
        ksort($nonConformityStatusCounts);
        ksort($actionStatusCounts);

        return compact('derogationStatusCounts', 'nonConformityStatusCounts', 'actionStatusCounts');
    }

    public function GetCalculateLitigationRate()
    {
        // Calculate the total number of order lines
        $totalOrderLines = OrderLines::count();
        // Calculate the number of disputed order lines
        $litigationCount = QualityNonConformity::whereNotNull('order_lines_id')->count();
        // Calculate the litigation rate
        if ($totalOrderLines > 0) {
            $litigationRate = ($litigationCount / $totalOrderLines) * 100;
        } else {
            $litigationRate = 0;
        }
        // Return the result
        return round($litigationRate,2);
    }
}
