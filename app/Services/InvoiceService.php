<?php

namespace App\Services;

use App\Models\Accounting\AccountingPaymentConditions;
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
     * @param int|null $paymentConditionId condition de la commande facturée ; à défaut,
     *                                     la condition par défaut fixe l'échéance
     * @return Invoices
     */
    public function createInvoice($code, $label, $companyId, $companyAddressId, $companyContactId, $userId, $customerReference = null, $paymentConditionId = null)
    {
        return Invoices::create([
            'uuid'                   => Str::uuid(),
            'code'                   => $code,
            'label'                  => $label,
            'companies_id'           => $companyId,
            'companies_addresses_id' => $companyAddressId,
            'companies_contacts_id'  => $companyContactId,
            'user_id'                => $userId,
            // Écrit explicitement : un modèle tout juste créé ne porte pas le défaut de la
            // colonne, et InvoiceLineService ne passerait pas l'écriture de vente.
            'invoice_type'           => 1,
            'due_date'               => $this->dueDate($paymentConditionId),
            'customer_reference'     => $customerReference,
        ]);
    }

    /**
     * Échéance d'une facture créée aujourd'hui. Sans condition de paiement connue
     * (commande sans condition, aucune condition par défaut), 30 jours comme avant.
     */
    public function dueDate($paymentConditionId = null, ?Carbon $from = null): Carbon
    {
        $from ??= Carbon::now();

        $condition = ($paymentConditionId ? AccountingPaymentConditions::find($paymentConditionId) : null)
            ?? AccountingPaymentConditions::getDefault();

        return $condition
            ? $condition->dueDateFrom($from)
            : $from->copy()->startOfDay()->addDays(30);
    }
}
