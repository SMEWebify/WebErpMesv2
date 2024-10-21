<?php

namespace App\Services;

use App\Models\Workflow\Invoices;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InvoiceService
{
    /**
     * New Invoice
     *
     * @param string $code
     * @param string $label
     * @param object $companyId
     * @param object $companyAddressId
     * @param object $compacompanyContactIdnyId
     * @param object $userId
     * @return Invoices
     */
    public function createInvoice($code, $label, $companyId, $companyAddressId, $companyContactId, $userId)
    {
        return Invoices::create([
            'uuid' => Str::uuid(),
            'code' => $code,
            'label' => $label,
            'companies_id' => $companyId,
            'companies_addresses_id' => $companyAddressId,
            'companies_contacts_id' => $companyContactId,
            'user_id' => $userId,
            'due_date' => Carbon::now()->addDays(30),
        ]);
    }
}
