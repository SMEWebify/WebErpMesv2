<?php

namespace App\Services;

use Illuminate\Support\Number;
use App\Models\Workflow\Quotes;
use Illuminate\Support\Facades\DB;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\OpportunitiesActivitiesLogs;

class OpportunitiesKPIService
{
    /**
     * Retrieve the opportunities data rate grouped by status.
     *
     * This function queries the 'opportunities' table and selects the status ('statu')
     * along with the count of opportunities for each status. The results are grouped
     * by the status and returned as a collection.
     *
     * @return \Illuminate\Support\Collection The collection of opportunities data rate.
     */
    public function getOpportunitiesDataRate()
    {
        return DB::table('opportunities')
                    ->select('statu', DB::raw('count(*) as OpportunitiesCountRate'))
                    ->groupBy('statu')
                    ->get();
    }

    /**
     * Retrieve the most recent activities logs.
     *
     * This function queries the 'opportunities_activities_logs' table and retrieves
     * the latest activity logs up to the specified limit. The results are returned
     * as a collection.
     *
     * @param int $limit The number of recent activities to retrieve. Default is 5.
     * @return \Illuminate\Support\Collection The collection of recent activities logs.
     */
    public function getRecentActivities($limit = 5)
    {
        return OpportunitiesActivitiesLogs::latest()->take($limit)->get();
    }

    /**
     * Retrieve the total amount of opportunities grouped by probability.
     *
     * This function queries the 'opportunities' table and selects the probability ('probality')
     * along with the sum of budgets for each probability. The results are filtered to include
     * only specific statuses (1, 2, 3), grouped by probability, and returned as a collection.
     *
     * @return \Illuminate\Support\Collection The collection of opportunities total amount by probability.
     */
    public function getOpportunitiesByAmount()
    {
        return Opportunities::select('probality', DB::raw('SUM(budget) as total_amount'))
                            ->whereIn('statu', [1, 2, 3]) // Filter statuses 1, 2, 3
                            ->groupBy('probality')
                            ->get();
    }

    /**
     * Retrieve opportunities grouped by their close date.
     *
     * This method selects the close date and counts the number of opportunities
     * for each close date, grouping the results by the close date.
     *
     * @return \Illuminate\Support\Collection A collection of opportunities grouped by close date with their respective counts.
     */
    public function getOpportunitiesByCloseDate()
    {
        return Opportunities::select('close_date', DB::raw('count(*) as count'))
                            ->groupBy('close_date')
                            ->get();
    }

    /**
     * Retrieve the top 10 opportunities grouped by company.
     *
     * This function fetches opportunities along with their associated company,
     * groups them by the company ID, and returns the count of opportunities for each company.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOpportunitiesByCompany()
    {
        return Opportunities::with('companie')
                            ->select('companies_id', DB::raw('count(*) as count'))
                            ->groupBy('companies_id')
                            ->limit(10)
                            ->get();
    }

    /**
     * Get the total count of opportunities.
     *
     * This method retrieves the total number of opportunities
     * from the Opportunities model.
     *
     * @return int The total count of opportunities.
     */
    public function getOpportunitiesCount()
    {
        return Opportunities::count();
    }

    /**
     * Get a summary of quotes, including the total value of quotes won and lost.
     *
     * This function retrieves quotes with a status of 'won' (status code 3) and 'lost' (status code 4),
     * calculates the total value for each category, and returns the results formatted as a string with
     * two decimal places.
     *
     * @return array An associative array containing:
     *               - 'totalQuotesWon': The total value of quotes won, formatted as a string.
     *               - 'totalQuotesLost': The total value of quotes lost, formatted as a string.
     */
    public function getQuotesSummary()
    {
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $quotesWon = Quotes::where('statu', 3)->whereNotNull('opportunities_id')->get();
        $totalQuotesWon = Number::currency($quotesWon->sum(function ($quote) {
            return $quote->getTotalPriceAttribute();
        }), $currency, config('app.locale'));

        $quotesLost = Quotes::where('statu', 4)->whereNotNull('opportunities_id')->get();
        $totalQuotesLost = Number::currency($quotesLost->sum(function ($quote) {
            return $quote->getTotalPriceAttribute();
        }), $currency, config('app.locale')); 

        return compact('totalQuotesWon', 'totalQuotesLost');
    }
}
