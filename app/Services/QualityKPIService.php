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
    /**
     * Get general statistics for quality KPIs.
     *
     * This function retrieves the total number of entries and the number of open entries
     * for each of the following categories: Derogations, Non-Conformities, and Actions.
     *
     * @return array An associative array containing:
     *               - 'totalDerogations': Total number of derogations.
     *               - 'totalDerogationsOpen': Total number of open derogations.
     *               - 'totalNonConformities': Total number of non-conformities.
     *               - 'totalNonConformitiesOpen': Total number of open non-conformities.
     *               - 'totalActions': Total number of actions.
     *               - 'totalActionsOpen': Total number of open actions.
     */
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

    /**
     * Calculate and return the internal and external rates for derogations, non-conformities, and actions.
     *
     * This function calculates the internal rates as a percentage for each category (derogations, non-conformities, and actions)
     * based on the number of internal entries. It also calculates the external rates as the complement to 100% of the internal rates.
     *
     * @return array An associative array containing:
     *               - 'internalDerogationRate': The internal derogation rate as a percentage.
     *               - 'externalDerogationRate': The external derogation rate as a percentage.
     *               - 'internalNonConformityRate': The internal non-conformity rate as a percentage.
     *               - 'externalNonConformityRate': The external non-conformity rate as a percentage.
     *               - 'internalActionRate': The internal action rate as a percentage.
     *               - 'externalActionRate': The external action rate as a percentage.
     */
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

    /**
     * Retrieve the top 7 companies generating the most non-conformities and prepare data for a chart.
     *
     * This function performs the following steps:
     * 1. Queries the `QualityNonConformity` model to get the top 7 companies with the highest count of non-conformities.
     * 2. Retrieves the names of these companies using their IDs from the `Companies` model.
     * 3. Defines a default set of colors for the chart.
     * 4. Prepares the data structure required for rendering a chart, including labels, data points, and colors.
     *
     * @return array The chart data including labels, datasets, and other configurations.
     */
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

    /**
     * Get the counts of different statuses for Quality Derogation, Non-Conformity, and Action.
     *
     * This method retrieves the counts of records grouped by their status for three different models:
     * QualityDerogation, QualityNonConformity, and QualityAction. It ensures that all possible statuses
     * (defined in $allStatus) are accounted for, even if there are no records for a particular status.
     * The counts are returned in an associative array with the status as the key and the count as the value.
     *
     * @return array An associative array containing the counts of statuses for each model:
     *               - 'derogationStatusCounts': array with counts of statuses for QualityDerogation
     *               - 'nonConformityStatusCounts': array with counts of statuses for QualityNonConformity
     *               - 'actionStatusCounts': array with counts of statuses for QualityAction
     */
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

    /**
     * Calculate the litigation rate for order lines.
     *
     * This function calculates the litigation rate by determining the ratio of disputed order lines
     * to the total number of order lines, and then converting that ratio to a percentage.
     *
     * @return float The litigation rate as a percentage, rounded to two decimal places.
     */
    public function GetCalculateLitigationRate()
    {
        // Calculate the total number of order lines
        $totalOrderLines = OrderLines::articles()->count();
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
    
    /*
     * Calculates the average resolution time for non-conformities.
    *
    * @param int|null $companyId Company ID to filter non-conformities. If null, takes all companies.
    * @return float Average resolution time in hours (or days if modified).
    */
    public function getAverageResolutionTime($companyId = null)
    {
        // If a company ID is provided, filter orders by company
        $resolvedNonConformitiesQuery = QualityNonConformity::whereNotNull('resolution_date');

        if ($companyId) {
            $resolvedNonConformitiesQuery->where('companies_id', $companyId);
        }

        $resolvedNonConformities = $resolvedNonConformitiesQuery->get();

        $totalTimeSpent = $resolvedNonConformities->reduce(function ($carry, $nonConformity) {
            $createdAt = $nonConformity->created_at;
            $resolutionDate = $nonConformity->resolution_date;
            $timeSpent = $createdAt->diffInDays($resolutionDate); 
            return $carry + $timeSpent;
        }, 0);
        
        $resolvedCount = $resolvedNonConformities->count();

        if ($resolvedCount === 0) {
            return 0; // no case solved
        }

        return $totalTimeSpent / $resolvedCount;
    }
}
