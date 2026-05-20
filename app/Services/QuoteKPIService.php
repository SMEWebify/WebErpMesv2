<?php

namespace App\Services;

use App\Models\Workflow\Quotes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class QuoteKPIService
{
    /**
     * Retrieves the rate of grouped quotes by status.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getQuotesDataRate(int $year, ?int $companyId = null)
    {
        $cacheKey = 'quote_data_rate_' . $year . '_company_' . ($companyId ?? 'all');
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($year, $companyId) {
            $query = DB::table('quotes')
                        ->select('statu', DB::raw('count(*) as QuoteCountRate'))
                        ->whereYear('created_at', $year)
                        ->whereNull('deleted_at')
                        ->groupBy('statu');

            if ($companyId) {
                $query->where('companies_id', $companyId);
            }

            return $query->get();
        });
    }


    /**
     * Retrieves the monthly summary of quotes for the current year, filtered by company.
     *
     * @param int $year
     * @param int|null $companyId
     * @return \Illuminate\Support\Collection
     */
    public function getQuoteMonthlyRecap(int $year, ?int $companyId = null)
    {
        $cacheKey = 'quote_monthly_recap_' . $year . '_company_' . ($companyId ?? 'all');
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($year, $companyId) {
            // Commence la requête avec une jointure et un filtrage éventuel par compagnie
            $query = DB::table('quote_lines')
                ->selectRaw('
                    MONTH(created_at) AS month,
                    SUM((selling_price * qty)-(selling_price * qty)*(discount/100)) AS quoteSum
                ')
                ->whereYear('quote_lines.created_at', $year)
                ->groupByRaw('MONTH(quote_lines.created_at)');

            // If a company ID is provided, add the filter
            if ($companyId) {
                $query->where('quotes.companies_id', $companyId);
            }

            // Execute and return results
            return $query->get();
        });

    }

    /**
     * Retrieves the monthly summary of quote for the last year.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getQuoteMonthlyRecapPreviousYear(int $year)
    {
        $lastyear = $year-1;
        $cacheKey = 'quote_monthly_recap_lastyear_' . $lastyear;
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($lastyear) {
            return DB::table('quote_lines')
                        ->selectRaw('
                            MONTH(created_at) AS month,
                            SUM((selling_price * qty)-(selling_price * qty)*(discount/100)) AS quoteSum
                        ')
                        ->whereYear('quote_lines.created_at', $lastyear)
                        ->groupByRaw('MONTH(quote_lines.created_at)')
                        ->get();
        });
    }

    /**
    * Retrieve customers sorted by quote volume for the current year.
    *
    * @param int $limit The number of customers to retrieve (default 5).
    * @return \Illuminate\Database\Eloquent\Collection Collection of customers sorted by quote volume.
    */
    public function getTopCustomersByQuoteVolume($limit = 5)
    {
        $cacheKey = 'quote_top_customers_' . now()->year . '_limit_' . $limit;
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($limit) {
            return Quotes::select('companies_id', DB::raw('COUNT(*) as quote_count'))
                            ->whereYear('created_at', now()->year)
                            ->whereNull('deleted_at')
                            ->groupBy('companies_id')
                            ->orderBy('quote_count', 'desc')
                            ->take($limit)
                            ->with('companie:id,label,code')
                            ->get();
        });
    }

    /**
     * Get the count of quotes by user.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getQuotesCountByUser()
    {
        return Cache::remember('quote_count_by_user', now()->addHours(1), function () {
            return Quotes::select('user_id', 'statu', DB::raw('count(*) as total'))
                    ->with('UserManagement:id,name')
                    ->whereIn('statu', [1, 2, 3, 4, 5, 6])
                    ->whereNull('deleted_at')
                    ->groupBy('user_id', 'statu')
                    ->get()
                    ->groupBy('user_id');
        });
    }

    /**
     * Calculate the average amount per quote.
     *
     * This method retrieves the total number of quotes and calculates the sum of all quote amounts.
     * It then divides the total amount by the number of quotes to get the average quote amount.
     *
     * @return float The average amount per quote. Returns 0 if there are no quotes.
     */
    public function getAverageQuoteAmount()
    {
        return Cache::remember('quote_average_amount', now()->addHours(1), function () {
            $result = DB::table('quotes')
                ->leftJoin('quote_lines', 'quote_lines.quotes_id', '=', 'quotes.id')
                ->leftJoin('accounting_vats', 'accounting_vats.id', '=', 'quote_lines.accounting_vats_id')
                ->whereNull('quotes.deleted_at')
                ->selectRaw('quotes.id, COALESCE(SUM(
                    quote_lines.selling_price * quote_lines.qty * (1 - quote_lines.discount / 100) *
                    (1 + COALESCE(accounting_vats.rate, 0) / 100)
                ), 0) as quote_total')
                ->groupBy('quotes.id')
                ->pluck('quote_total');

            return $result->isEmpty() ? 0 : $result->avg();
        });
    }

    /**
     * Calculate the quote conversion rate.
     *
     * This function calculates the percentage of quotes that have been converted into orders.
     *
     * @return float The quote conversion rate as a percentage.
     */
    public function getQuoteConversionRate()
    {
        return Cache::remember('quote_conversion_rate', now()->addHours(1), function () {
            $totalQuotes = Quotes::whereNull('deleted_at')->count();
            if ($totalQuotes == 0) {
                return 0;
            }
            $convertedQuotes = Quotes::whereNull('deleted_at')->whereHas('Orders')->count();
            return round(($convertedQuotes / $totalQuotes) * 100, 2);
        });
    }

    /**
     * Get the total amount of quotes grouped by status.
     *
     * This method retrieves all distinct statuses from the quotes and calculates the total amount
     * for each status.
     *
     * @return array An associative array where the keys are statuses and the values are the total amounts.
     * 
     *   public function getTotalAmountByStatus()
     *   {
     *       // Statuts possibles : 'accepted', 'rejected', 'pending', etc.
     *       $statuses = Quotes::select('statu')->distinct()->pluck('statu');

     *       $amountsByStatus = [];
     *       foreach ($statuses as $status) {
     *           // Récupérer tous les devis ayant ce statut
     *           $quotes = Quotes::where('statu', $status)->get();

     *           // Calculer la somme des montants via l'attribut calculé
     *           $totalAmount = $quotes->sum(function ($quote) {
    *              return $quote->total_price; // Utilisation de l'attribut calculé
    *            });
    *   
    *           $amountsByStatus[$status] = $totalAmount;
    *        }

    *       return $amountsByStatus;
    *   }

    */

    /**
     * Calculate the quote response rate.
     *
     * This function retrieves the total number of quotes and the number of quotes
     * that have received a response (e.g., accepted, rejected). It then calculates
     * the response rate as a percentage.
     *
     * @return float The response rate as a percentage, rounded to 2 decimal places.
     */
    public function getQuoteResponseRate()
    {
        return Cache::remember('quote_response_rate', now()->addHours(1), function () {
            $counts = DB::table('quotes')
                ->whereNull('deleted_at')
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN statu IN (3, 4, 5, 6) THEN 1 ELSE 0 END) as responded')
                ->first();

            if (!$counts || $counts->total == 0) {
                return 0;
            }

            return round(($counts->responded / $counts->total) * 100, 2);
        });
    }

}
