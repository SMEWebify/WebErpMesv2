<?php

namespace App\Services;

use App\Models\Workflow\Opportunities;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\OpportunitiesActivitiesLogs;
use Illuminate\Support\Facades\DB;

class OpportunitiesKPIService
{
    public function getOpportunitiesDataRate()
    {
        return DB::table('opportunities')
                    ->select('statu', DB::raw('count(*) as OpportunitiesCountRate'))
                    ->groupBy('statu')
                    ->get();
    }

    public function getRecentActivities($limit = 5)
    {
        return OpportunitiesActivitiesLogs::latest()->take($limit)->get();
    }

    public function getOpportunitiesByAmount()
    {
        return Opportunities::select('probality', DB::raw('SUM(budget) as total_amount'))
                            ->groupBy('probality')
                            ->get();
    }

    public function getOpportunitiesByCloseDate()
    {
        return Opportunities::select('close_date', DB::raw('count(*) as count'))
                            ->groupBy('close_date')
                            ->get();
    }

    public function getOpportunitiesByCompany()
    {
        return Opportunities::with('companie')
                            ->select('companies_id', DB::raw('count(*) as count'))
                            ->groupBy('companies_id')
                            ->get();
    }

    public function getOpportunitiesCount()
    {
        return Opportunities::count();
    }

    public function getQuotesSummary()
    {
        $quotesWon = Quotes::where('statu', 3)->whereNotNull('opportunities_id')->get();
        $totalQuotesWon = $quotesWon->sum(function ($quote) {
            return $quote->getTotalPriceAttribute();
        });

        $quotesLost = Quotes::where('statu', 4)->whereNotNull('opportunities_id')->get();
        $totalQuotesLost = $quotesLost->sum(function ($quote) {
            return $quote->getTotalPriceAttribute();
        });

        return compact('totalQuotesWon', 'totalQuotesLost');
    }
}
