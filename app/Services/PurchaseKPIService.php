<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use Illuminate\Support\Facades\Cache;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseReceiptLines;

class PurchaseKPIService
{

    /**
     * Retrieve the purchase quotation data rate.
     *
     * This function queries the 'purchases_quotations' table, selects the 'statu' column,
     * and counts the number of occurrences for each 'statu' value. The results are grouped
     * by the 'statu' column and returned as a collection.
     *
     * @return \Illuminate\Support\Collection The collection of purchase quotation data rates.
     */
    public function getPurchaseQuotationDataRate()
    {
        return DB::table('purchases_quotations')
                    ->select('statu', DB::raw('count(*) as PurchaseQuotationCountRate'))
                    ->groupBy('statu')
                    ->get();
    }
    
    /**
     * Retrieve the purchase data rate grouped by status.
     *
     * This function queries the 'purchases' table and selects the status ('statu')
     * and the count of purchases for each status. The results are grouped by the
     * status and returned as a collection.
     *
     * @return \Illuminate\Support\Collection The collection of purchase data rates.
     */
    public function getPurchasesDataRate()
    {
        return DB::table('purchases')
            ->select('statu', DB::raw('count(*) as PurchaseCountRate'))
            ->groupBy('statu')
            ->get();
    }

    /**
     * Get the monthly recap of purchases for the current year.
     *
     * This function retrieves the monthly summary of purchases for the current year.
     * It uses caching to store the results for one hour to improve performance.
     * The data is fetched from the 'purchase_lines', 'tasks', and 'order_lines' tables.
     *
     * @return \Illuminate\Support\Collection The monthly recap of purchases, including the month and the total purchase sum.
     */
    public function getPurchaseMonthlyRecap($Year)
    {
        $cacheKey = 'purchase_monthly_recap_11' . $Year;
        //return Cache::remember($cacheKey, now()->addHours(1), function () use ($Year) {
            return DB::table('purchase_lines')
                ->selectRaw(expression: '
                    MONTH(purchase_lines.created_at) AS month,
                    SUM((purchase_lines.selling_price * purchase_lines.qty)-(purchase_lines.selling_price * purchase_lines.qty)*(purchase_lines.discount/100)) AS purchaseSum
                ')
                ->whereYear('purchase_lines.created_at', $Year)
                ->groupByRaw('MONTH(purchase_lines.created_at)')
                ->get();
        //});
    }

    /**
     * Retrieve the top 5 rated suppliers.
     *
     * This method fetches suppliers with a status of 2 (active suppliers) and calculates their average rating.
     * It returns the top 5 suppliers based on their average rating, only including those with at least one rating.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTopRatedSuppliers()
    {
        return Companies::where('statu_supplier', 2)
            ->withCount('rating')
            ->having('rating_count', '>', 0)
            ->orderByDesc(function ($company) {
                return $company->select(DB::raw('avg(rating)'))
                    ->from('supplier_ratings')
                    ->whereColumn('companies_id', 'companies.id');
            })
            ->take(5)
            ->get();
    }

    /**
     * Get the average reception delay by supplier.
     *
     * This function calculates the average delay between the creation of purchase lines
     * and the creation of purchase receipt lines for each supplier. It joins the 
     * purchase receipt lines, purchase lines, purchases, and companies tables to 
     * retrieve the necessary data. The result is grouped by supplier name and 
     * includes the average reception delay for each supplier.
     *
     * @return \Illuminate\Support\Collection A collection of suppliers with their average reception delay, sorted by the delay.
     */
    public function getAverageReceptionDelayBySupplier()
    {
        $averageReceptionDelayBySupplier = PurchaseReceiptLines::join('purchase_lines', 'purchase_receipt_lines.purchase_line_id', '=', 'purchase_lines.id')
            ->join('purchases', 'purchase_lines.purchases_id', '=', 'purchases.id')
            ->join('companies', 'purchases.companies_id', '=', 'companies.id')
            ->selectRaw('
                companies.label AS supplier_name,
                AVG(DATEDIFF(purchase_receipt_lines.created_at, purchase_lines.created_at)) AS avg_reception_delay,
                SUM(COALESCE(purchase_receipt_lines.accepted_qty, purchase_receipt_lines.receipt_qty, 0)) AS total_accepted_qty,
                SUM(COALESCE(purchase_receipt_lines.rejected_qty, 0)) AS total_rejected_qty,
                SUM(COALESCE(purchase_receipt_lines.accepted_qty, purchase_receipt_lines.receipt_qty, 0) + COALESCE(purchase_receipt_lines.rejected_qty, 0)) AS total_inspected_qty,
                CASE
                    WHEN SUM(COALESCE(purchase_receipt_lines.accepted_qty, purchase_receipt_lines.receipt_qty, 0) + COALESCE(purchase_receipt_lines.rejected_qty, 0)) > 0
                        THEN SUM(COALESCE(purchase_receipt_lines.accepted_qty, purchase_receipt_lines.receipt_qty, 0)) /
                             SUM(COALESCE(purchase_receipt_lines.accepted_qty, purchase_receipt_lines.receipt_qty, 0) + COALESCE(purchase_receipt_lines.rejected_qty, 0))
                    ELSE NULL
                END AS compliance_rate'
            )
            ->groupBy('companies.label')
            ->get()
            ->map(function ($supplier) {
                $supplier->compliance_rate = is_null($supplier->compliance_rate)
                    ? null
                    : (float) $supplier->compliance_rate;

                return $supplier;
            });

        return $averageReceptionDelayBySupplier->sortBy('avg_reception_delay');
    }

    /**
     * Retrieve the top 5 products based on the total quantity purchased.
     *
     * This function selects the product label and product ID from the purchase lines,
     * joins the products table to get the product details, groups the results by product ID
     * and product label, orders the results by the total quantity in descending order,
     * and limits the results to the top 5 products.
     *
     * @return \Illuminate\Support\Collection The collection of top 5 products with their labels, IDs, and total quantities.
     */
    public function getTopProducts()
    {
        return PurchaseLines::select('products.label', 'purchase_lines.product_id', DB::raw('SUM(purchase_lines.qty) as total_quantity'))
            ->join('products', 'products.id', '=', 'purchase_lines.product_id')
            ->groupBy('purchase_lines.product_id', 'products.label')
            ->orderByDesc('total_quantity')
            ->take(5)
            ->get();
    }

    /**
     * Get the total purchase amount.
     *
     * This function calculates the sum of the 'total_selling_price' column
     * from the PurchaseLines model.
     *
     * @return float The total purchase amount.
     */
    public function getTotalPurchaseAmount()
    {
        return PurchaseLines::sum('total_selling_price');
    }

    /**
     * Get the total count of purchase lines.
     *
     * This method retrieves the total number of purchase lines
     * from the database using the PurchaseLines model.
     *
     * @return int The total count of purchase lines.
     */
    public function getTotalPurchaseCount()
    {
        return PurchaseLines::count();
    }

    /**
     * Retrieve suppliers ordered by their composite evaluation score.
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getSupplierCompositeIndicators(int $limit = 5)
    {
        return Companies::where('statu_supplier', 2)
            ->with(['rating' => function ($query) {
                $query->select([
                    'id',
                    'companies_id',
                    'evaluation_score_quality',
                    'evaluation_score_logistics',
                    'evaluation_score_service',
                    'next_review_at',
                    'approved_at',
                    'evaluation_status',
                    'created_at',
                ]);
            }])
            ->get()
            ->map(function ($company) {
                $compositeScores = $company->rating
                    ->map(fn ($rating) => $rating->composite_score)
                    ->filter(fn ($score) => $score !== null);

                $company->composite_score = $compositeScores->isEmpty()
                    ? null
                    : round($compositeScores->avg(), 1);

                $company->latest_review = $company->rating->sortByDesc(function ($rating) {
                    return [$rating->next_review_at ?? Carbon::createFromTimestamp(0), $rating->created_at];
                })->first();

                return $company;
            })
            ->filter(fn ($company) => $company->composite_score !== null)
            ->sortByDesc('composite_score')
            ->take($limit)
            ->values();
    }

    /**
     * Retrieve suppliers requiring requalification before a given threshold.
     *
     * @param int $withinDays
     * @return \Illuminate\Support\Collection
     */
    public function getSuppliersToRequalify(int $withinDays = 0)
    {
        $thresholdDate = Carbon::now()->addDays($withinDays)->endOfDay();

        return Companies::where('statu_supplier', 2)
            ->whereHas('rating', function ($query) use ($thresholdDate) {
                $query->whereNotNull('next_review_at')
                    ->whereDate('next_review_at', '<=', $thresholdDate);
            })
            ->with(['rating' => function ($query) {
                $query->select([
                    'id',
                    'companies_id',
                    'next_review_at',
                    'evaluation_status',
                    'evaluation_score_quality',
                    'evaluation_score_logistics',
                    'evaluation_score_service',
                    'created_at',
                ]);
            }])
            ->get()
            ->map(function ($company) {
                $latestEvaluation = $company->rating->sortByDesc(function ($rating) {
                    return [$rating->next_review_at ?? Carbon::createFromTimestamp(0), $rating->created_at];
                })->first();

                $company->next_review_at = $latestEvaluation?->next_review_at;
                $company->evaluation_status = $latestEvaluation?->evaluation_status;
                $company->composite_score = $latestEvaluation?->composite_score;

                return $company;
            })
            ->sortBy(function ($company) {
                return optional($company->next_review_at)->timestamp ?? 0;
            })
            ->values();
    }

    /**
     * Calculate the average purchase amount.
     *
     * This method retrieves the total purchase count and the total purchase amount,
     * then calculates the average purchase amount by dividing the total purchase amount
     * by the total purchase count. If the total purchase count is zero, it returns 0
     * to avoid division by zero.
     *
     * @return float The average purchase amount or 0 if there are no purchases.
     */
    public function getAverageAmount()
    {
        $totalPurchaseCount = $this->getTotalPurchaseCount();
        $totalPurchaseAmount = $this->getTotalPurchaseAmount();

        return $totalPurchaseCount > 0 ? $totalPurchaseAmount / $totalPurchaseCount : 0;
    }

    /**
     * Retrieve the count of purchase receipts grouped by their status.
     *
     * This function queries the 'purchase_receipts' table and returns the count
     * of receipts for each status. The result is grouped by the 'statu' column.
     *
     * @return \Illuminate\Support\Collection The collection of purchase receipt counts grouped by status.
     */
    public function getPurchaseReciepCountDataRate()
    {
        return DB::table('purchase_receipts')
                    ->select('statu', DB::raw('count(*) as PurchaseReciepCountRate'))
                    ->groupBy('statu')
                    ->get();
    }

    /**
     * Get the monthly recap of purchase receipts for the current year.
     *
     * This function retrieves the count of purchase receipts per month
     * for the current year.
     *
     * @return \Illuminate\Support\Collection A collection of objects where each object contains:
     * - month: The month of the purchase receipt.
     * - receiptCount: The total number of receipts for that month.
     */
    public function getPurchaseReceiptMonthlyRecap()
    {
        $currentYear = Carbon::now()->format('Y');

        return DB::table('purchase_receipts')
            ->selectRaw('
                MONTH(purchase_receipts.created_at) AS month,
                COUNT(*) AS receiptCount
            ')
            ->whereYear('purchase_receipts.created_at', $currentYear)
            ->groupByRaw('MONTH(purchase_receipts.created_at)')
            ->get();
    }

    /**
     * Retrieve the purchase invoice data rate.
     *
     * This function queries the 'purchase_invoices' table and returns the count of purchase invoices
     * grouped by their status ('statu'). The result is a collection of objects where each object contains
     * the status and the count of purchase invoices for that status.
     *
     * @return \Illuminate\Support\Collection A collection of objects with 'statu' and 'PurchaseInvoiceCountRate' properties.
     */
    public function getPurchaseInvoiceDataRate()
    {
        return DB::table('purchase_invoices')
                    ->select('statu', DB::raw('count(*) as PurchaseInvoiceCountRate'))
                    ->groupBy('statu')
                    ->get();
    }

    /**
     * Get the monthly recap of purchase invoices for the current year.
     *
     * This function retrieves data from the 'purchase_invoice_lines', 'purchase_lines', 'tasks', 
     * and 'order_lines' tables to calculate the monthly sum of purchases for the current year.
     * The sum is calculated as the total selling price of order lines minus any discounts applied.
     *
     * @return \Illuminate\Support\Collection A collection of objects where each object contains:
     * - month: The month of the purchase invoice.
     * - purchaseSum: The total sum of purchases for that month.
     */
    public function getPurchaseInvoiceMonthlyRecap()
    {
        $CurentYear = Carbon::now()->format('Y');

        return DB::table('purchase_invoice_lines')
                    ->join('purchase_lines', 'purchase_invoice_lines.purchase_line_id', '=', 'purchase_lines.id')
                    ->join('tasks', 'purchase_lines.tasks_id', '=', 'tasks.id')
                    ->join('order_lines', 'tasks.order_lines_id', '=', 'order_lines.id')
                    ->selectRaw('
                        MONTH(purchase_lines.created_at) AS month,
                        SUM((order_lines.selling_price * order_lines.qty) - (order_lines.selling_price * order_lines.qty) * (order_lines.discount / 100)) AS purchaseSum
                    ')
                    ->whereYear('purchase_invoice_lines.created_at', $CurentYear)
                    ->groupByRaw('MONTH(purchase_invoice_lines.created_at)')
                    ->get();
    }
}
