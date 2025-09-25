<?php

namespace App\Http\Controllers;

use App\Services\OrderKPIService;
use App\Services\QuoteKPIService;
use App\Services\AccountingReportService;

class ReportsController extends Controller
{
    protected $orderKPIService;
    protected $quoteKPIService;

    public function __construct(OrderKPIService $orderKPIService, QuoteKPIService $quoteKPIService)
    {
        $this->orderKPIService = $orderKPIService;
        $this->quoteKPIService = $quoteKPIService;
    }

    /**
     * Display the reports dashboard.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $deliveredOrdersPercentage = $this->orderKPIService->getDeliveredOrdersPercentage();
        $invoicedOrdersPercentage  = $this->orderKPIService->getInvoicedOrdersPercentage();
        $serviceRate               = $this->orderKPIService->getServiceRate();

        $averageQuoteAmount = $this->quoteKPIService->getAverageQuoteAmount();
        $conversionRate     = $this->quoteKPIService->getQuoteConversionRate();
        $responseRate       = $this->quoteKPIService->getQuoteResponseRate();

        $topOrderCustomers = $this->orderKPIService->getTopCustomersByOrderVolume(5);
        $topQuoteCustomers = $this->quoteKPIService->getTopCustomersByQuoteVolume(5);

        return view('reports.index', compact(
            'deliveredOrdersPercentage',
            'invoicedOrdersPercentage',
            'serviceRate',
            'averageQuoteAmount',
            'conversionRate',
            'responseRate',
            'topOrderCustomers',
            'topQuoteCustomers'
        ));
    }

    /**
     * Display accounting reports summary.
     */
    public function accounting(AccountingReportService $accountingReportService)
    {
        $revenue  = $accountingReportService->getTotalRevenue();
        $expenses = $accountingReportService->getTotalExpense();
        $profit   = $accountingReportService->getProfit();

        return view('reports.accounting', compact('revenue', 'expenses', 'profit'));
    }
}