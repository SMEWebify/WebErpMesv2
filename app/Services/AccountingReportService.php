<?php
namespace App\Services;

use App\Models\Workflow\Invoices;
use App\Models\Purchases\Purchases;

class AccountingReportService
{
    public function getTotalRevenue()
    {
        return Invoices::all()->sum(function ($invoice) {
            return $invoice->getTotalPriceAttribute();
        });
    }

    public function getTotalExpense()
    {
        return Purchases::all()->sum(function ($purchase) {
            return $purchase->getTotalPriceAttribute();
        });
    }

    public function getProfit()
    {
        return $this->getTotalRevenue() - $this->getTotalExpense();
    }
}