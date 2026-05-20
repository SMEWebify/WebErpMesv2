<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\InvoiceLines;
use Carbon\Carbon;

class InvoiceKPIService
{
    public function getInvoicesDataRate()
    {
        return Cache::remember('invoice_data_rate_' . now()->year, now()->addHours(1), function () {
            return DB::table('invoices')
                        ->select('statu', DB::raw('count(*) as InvoiceCountRate'))
                        ->where('invoice_type', 1)
                        ->whereNull('deleted_at')
                        ->groupBy('statu')
                        ->get();
        });
    }

    public function getInvoiceMonthlyRecap(int $year, ?Carbon $start = null, ?Carbon $end = null)
    {
        $cacheKey = $start
            ? 'invoice_monthly_recap_fiscal_' . $start->format('Ymd')
            : 'invoice_monthly_recap_' . $year;

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($year, $start, $end) {
            $query = DB::table('invoice_lines')
                        ->join('order_lines', 'invoice_lines.order_line_id', '=', 'order_lines.id')
                        ->join('invoices', 'invoice_lines.invoices_id', '=', 'invoices.id')
                        ->selectRaw('
                            MONTH(invoice_lines.created_at) AS month,
                            SUM((order_lines.selling_price * invoice_lines.qty) - (order_lines.selling_price * invoice_lines.qty)*(order_lines.discount/100)) AS orderSum
                        ')
                        ->where('invoices.invoice_type', 1)
                        ->whereNull('invoices.deleted_at')
                        ->whereNull('invoice_lines.deleted_at');

            if ($start && $end) {
                $query->whereBetween('invoice_lines.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);
            } else {
                $query->whereYear('invoice_lines.created_at', $year);
            }

            return $query->groupByRaw('MONTH(invoice_lines.created_at)')->get();
        });
    }

    public function getTotalInvoicesCount()
    {
        return Cache::remember('invoice_total_count_' . now()->year, now()->addHours(1), function () {
            return Invoices::where('invoice_type', 1)->whereNull('deleted_at')->count();
        });
    }

    public function getTotalInvoiceAmount()
    {
        return Cache::remember('invoice_total_amount_' . now()->year, now()->addHours(1), function () {
            return DB::table('invoices')
                ->join('invoice_lines', fn ($j) => $j
                    ->on('invoice_lines.invoices_id', '=', 'invoices.id')
                    ->whereNull('invoice_lines.deleted_at'))
                ->join('order_lines', 'invoice_lines.order_line_id', '=', 'order_lines.id')
                ->leftJoin('accounting_vats', 'accounting_vats.id', '=', 'order_lines.accounting_vats_id')
                ->where('invoices.invoice_type', 1)
                ->whereNull('invoices.deleted_at')
                ->selectRaw('COALESCE(SUM(
                    order_lines.selling_price * invoice_lines.qty * (1 - order_lines.discount / 100) *
                    (1 + COALESCE(accounting_vats.rate, 0) / 100)
                ), 0) as total')
                ->value('total') ?? 0;
        });
    }

    public function getTotalPaymentsReceived()
    {
        return Cache::remember('invoice_total_paid_' . now()->year, now()->addHours(1), function () {
            return DB::table('invoices')
                ->join('invoice_lines', fn ($j) => $j
                    ->on('invoice_lines.invoices_id', '=', 'invoices.id')
                    ->whereNull('invoice_lines.deleted_at'))
                ->join('order_lines', 'invoice_lines.order_line_id', '=', 'order_lines.id')
                ->leftJoin('accounting_vats', 'accounting_vats.id', '=', 'order_lines.accounting_vats_id')
                ->where('invoices.invoice_type', 1)
                ->where('invoices.statu', 5)
                ->whereNull('invoices.deleted_at')
                ->selectRaw('COALESCE(SUM(
                    order_lines.selling_price * invoice_lines.qty * (1 - order_lines.discount / 100) *
                    (1 + COALESCE(accounting_vats.rate, 0) / 100)
                ), 0) as total')
                ->value('total') ?? 0;
        });
    }

    public function getPaidInvoicesCount($companyId = null)
    {
        $cacheKey = 'invoice_paid_count_company_' . ($companyId ?? 'all');
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($companyId) {
            $query = Invoices::where('invoice_type', 1)->where('statu', 5)->whereNull('deleted_at');
            if ($companyId) {
                $query->where('companies_id', $companyId);
            }
            return $query->count();
        });
    }

    public function getUnpaidInvoicesCount($companyId = null)
    {
        $cacheKey = 'invoice_unpaid_count_company_' . ($companyId ?? 'all');
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($companyId) {
            $query = Invoices::where('invoice_type', 1)->where('statu', '!=', 5)->whereNull('deleted_at');
            if ($companyId) {
                $query->where('companies_id', $companyId);
            }
            return $query->count();
        });
    }

    public function getAveragePaymentDelay()
    {
        return Cache::remember('invoice_avg_payment_delay', now()->addHours(1), function () {
            return Invoices::where('invoice_type', 1)
                            ->whereNotNull('payment_date')
                            ->whereNull('deleted_at')
                            ->select(DB::raw('AVG(DATEDIFF(payment_date, due_date)) as avg_delay'))
                            ->value('avg_delay');
        });
    }

    public function getLatePaymentRate($totalInvoices)
    {
        return Cache::remember('invoice_late_payment_rate', now()->addHours(1), function () use ($totalInvoices) {
            return Invoices::where('invoice_type', 1)
                            ->where('statu', 4)
                            ->where('due_date', '<', now())
                            ->whereNull('deleted_at')
                            ->count() / ($totalInvoices ?: 1) * 100;
        });
    }

    public function getInvoiceMonthlyRecapPreviousYear(int $year)
    {
        return $this->getInvoiceMonthlyRecap($year - 1);
    }

    public function getTopClients()
    {
        return Cache::remember('invoice_top_clients_' . now()->year, now()->addHours(1), function () {
            return Invoices::select('companies_id', DB::raw('SUM((invoice_lines.qty * order_lines.selling_price) - (invoice_lines.qty * order_lines.selling_price)*(order_lines.discount/100)) as total_amount'))
                            ->join('invoice_lines', 'invoices.id', '=', 'invoice_lines.invoices_id')
                            ->join('order_lines', 'invoice_lines.order_line_id', '=', 'order_lines.id')
                            ->where('invoices.invoice_type', 1)
                            ->whereNull('invoice_lines.deleted_at')
                            ->groupBy('companies_id')
                            ->orderByDesc('total_amount')
                            ->take(5)
                            ->get();
        });
    }

    public function getTopProducts()
    {
        return Cache::remember('invoice_top_products_' . now()->year, now()->addHours(1), function () {
            return InvoiceLines::select('order_lines.product_id', DB::raw('SUM(invoice_lines.qty) as total_quantity'))
                                ->join('order_lines', 'invoice_lines.order_line_id', '=', 'order_lines.id')
                                ->join('invoices', 'invoice_lines.invoices_id', '=', 'invoices.id')
                                ->where('invoices.invoice_type', 1)
                                ->whereNull('invoices.deleted_at')
                                ->groupBy('order_lines.product_id')
                                ->orderByDesc('total_quantity')
                                ->take(5)
                                ->get();
        });
    }
}
