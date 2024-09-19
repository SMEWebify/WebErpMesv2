<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\InvoiceLines;
use Carbon\Carbon;

class InvoiceKPIService
{
    /**
     * Retrieves the rate of grouped invoices by status.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getInvoicesDataRate()
    {
        return DB::table('invoices')
                    ->select('statu', DB::raw('count(*) as InvoiceCountRate'))
                    ->groupBy('statu')
                    ->get();
    }

    /**
     * Retrieves the monthly summary of invoices for the current year.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getInvoiceMonthlyRecap($year)
    {
        return DB::table('invoice_lines')
                    ->join('order_lines', 'invoice_lines.order_line_id', '=', 'order_lines.id')
                    ->selectRaw('
                        MONTH(invoice_lines.created_at) AS month,
                        SUM((order_lines.selling_price * invoice_lines.qty) - (order_lines.selling_price * invoice_lines.qty)*(order_lines.discount/100)) AS orderSum
                    ')
                    ->whereYear('invoice_lines.created_at', $year)
                    ->groupByRaw('MONTH(invoice_lines.created_at)')
                    ->get();
    }

    /**
     * Retrieves the total number of invoices.
     *
     * @return int
     */
    public function getTotalInvoicesCount()
    {
        return Invoices::count();
    }

    /**
     * Retrieves the total amount of invoices.
     *
     * @return float
     */
    public function getTotalInvoiceAmount()
    {
        return Invoices::all()->reduce(function ($carry, $invoice) {
            return $carry + $invoice->getTotalPriceAttribute();
        }, 0);
    }

    /**
    * Retrieves the total amount of payments received.
     *
     * @return float
     */
    public function getTotalPaymentsReceived()
    {
        return Invoices::where('statu', 5)->get()->reduce(function ($carry, $invoice) {
            return $carry + $invoice->getTotalPriceAttribute();
        }, 0);
    }

    /**
     * Retrieves the number of invoices paid.
     *
     * @return int
     */
    public function getPaidInvoicesCount($companyId = null)
    {
        $query = Invoices::where('statu', 5);
    
        if ($companyId) {
            $query->where('companies_id', $companyId);
        }
    
        return $query->count();
    }

    /**
    * Retrieves the number of unpaid invoices.
     *
     * @return int
     */
    public function getUnpaidInvoicesCount($companyId = null)
    {
        $query = Invoices::where('statu', '!=', 4);
    
        if ($companyId) {
            $query->where('companies_id', $companyId);
        }
    
        return $query->count();
    }

    /**
    * Retrieves the average payment period.
     *
     * @return float
     */
    public function getAveragePaymentDelay()
    {
        return Invoices::whereNotNull('payment_date')
                        ->select(DB::raw('AVG(DATEDIFF(payment_date, due_date)) as avg_delay'))
                        ->value('avg_delay');
    }

    /**
     * Recovers the late payment rate.
     *
     * @param int $totalInvoices
     * @return float
     */
    public function getLatePaymentRate($totalInvoices)
    {
        return Invoices::where('statu', 4)
                        ->where('due_date', '<', now())
                        ->count() / ($totalInvoices ?: 1) * 100;
    }

    /**
   * Retrieves top customers by invoice amount.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getTopClients()
    {
        return Invoices::select('companies_id', DB::raw('SUM((invoice_lines.qty * order_lines.selling_price) - (invoice_lines.qty * order_lines.selling_price)*(order_lines.discount/100)) as total_amount'))
                        ->join('invoice_lines', 'invoices.id', '=', 'invoice_lines.invoices_id')
                        ->join('order_lines', 'invoice_lines.order_line_id', '=', 'order_lines.id')
                        ->groupBy('companies_id')
                        ->orderByDesc('total_amount')
                        ->take(5)
                        ->get();
    }

    /**
    * Retrieves the main products/services billed.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getTopProducts()
    {
        return InvoiceLines::select('order_lines.product_id', DB::raw('SUM(invoice_lines.qty) as total_quantity'))
                            ->join('order_lines', 'invoice_lines.order_line_id', '=', 'order_lines.id')
                            ->groupBy('order_lines.product_id')
                            ->orderByDesc('total_quantity')
                            ->take(5)
                            ->get();
    }
}
