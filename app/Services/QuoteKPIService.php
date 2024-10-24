<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class QuoteKPIService
{
    /**
     * Retrieves the rate of grouped quotes by status.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getQuotesDataRate($year, $companyId = null)
    {
        $query = DB::table('quotes')
                    ->select('statu', DB::raw('count(*) as QuoteCountRate'))
                    ->whereYear('created_at', $year)
                    ->groupBy('statu');

            // If a company ID is provided, add the filter
            if ($companyId) {
                $query->where('companies_id', $companyId);
            }

            return $query->get();
    }

    /**
     * Retrieves the monthly summary of quotes for the current year.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getQuotesrMonthlyRecap($year)
    {
        return DB::table('quote_lines')->selectRaw('
                                                    MONTH(delivery_date) AS month,
                                                    SUM((selling_price * qty)-(selling_price * qty)*(discount/100)) AS quoteSum
                                                ')
                                                ->whereYear('created_at', $year)
                                                ->groupByRaw('MONTH(delivery_date) ')
                                                ->get();
    }
}
