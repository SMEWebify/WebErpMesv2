<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;
use Illuminate\Support\Facades\Cache;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseReceiptLines;

class PurchaseKPIService
{

    public function getPurchaseQuotationDataRate()
    {
        return DB::table('purchases_quotations')
                    ->select('statu', DB::raw('count(*) as PurchaseQuotationCountRate'))
                    ->groupBy('statu')
                    ->get();
    }
    
    public function getPurchasesDataRate()
    {
        return DB::table('purchases')
            ->select('statu', DB::raw('count(*) as PurchaseCountRate'))
            ->groupBy('statu')
            ->get();
    }

    public function getPurchaseMonthlyRecap()
    {
        $currentYear = Carbon::now()->format('Y');
        $cacheKey = 'purchase_monthly_recap_' . $currentYear;
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($currentYear) {
            return DB::table('purchase_lines')
                ->join('tasks', 'purchase_lines.tasks_id', '=', 'tasks.id')
                ->join('order_lines', 'tasks.order_lines_id', '=', 'order_lines.id')
                ->selectRaw('
                    MONTH(purchase_lines.created_at) AS month,
                    SUM((order_lines.selling_price * order_lines.qty)-(order_lines.selling_price * order_lines.qty)*(order_lines.discount/100)) AS purchaseSum
                ')
                ->whereYear('purchase_lines.created_at', $currentYear)
                ->groupByRaw('MONTH(purchase_lines.created_at)')
                ->get();
        });
    }

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

    public function getAverageReceptionDelayBySupplier()
    {
        $averageReceptionDelayBySupplier = PurchaseReceiptLines::join('purchase_lines', 'purchase_receipt_lines.purchase_line_id', '=', 'purchase_lines.id')
            ->join('purchases', 'purchase_lines.purchases_id', '=', 'purchases.id')
            ->join('companies', 'purchases.companies_id', '=', 'companies.id')
            ->selectRaw('companies.label AS supplier_name, AVG(DATEDIFF(purchase_receipt_lines.created_at, purchase_lines.created_at)) AS avg_reception_delay')
            ->groupBy('companies.label')
            ->get();

        return $averageReceptionDelayBySupplier->sortBy('avg_reception_delay');
    }

    public function getTopProducts()
    {
        return PurchaseLines::select('products.label', 'purchase_lines.product_id', DB::raw('SUM(purchase_lines.qty) as total_quantity'))
            ->join('products', 'products.id', '=', 'purchase_lines.product_id')
            ->groupBy('purchase_lines.product_id', 'products.label')
            ->orderByDesc('total_quantity')
            ->take(5)
            ->get();
    }

    public function getTotalPurchaseAmount()
    {
        return PurchaseLines::sum('total_selling_price');
    }

    public function getTotalPurchaseCount()
    {
        return PurchaseLines::count();
    }

    public function getAverageAmount()
    {
        $totalPurchaseCount = $this->getTotalPurchaseCount();
        $totalPurchaseAmount = $this->getTotalPurchaseAmount();

        return $totalPurchaseCount > 0 ? $totalPurchaseAmount / $totalPurchaseCount : 0;
    }

    public function getPurchaseReciepCountDataRate()
    {
        return DB::table('purchase_receipts')
                    ->select('statu', DB::raw('count(*) as PurchaseReciepCountRate'))
                    ->groupBy('statu')
                    ->get();
    }

    public function getPurchaseInvoiceDataRate()
    {
        return DB::table('purchase_invoices')
                    ->select('statu', DB::raw('count(*) as PurchaseInvoiceCountRate'))
                    ->groupBy('statu')
                    ->get();
    }

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
