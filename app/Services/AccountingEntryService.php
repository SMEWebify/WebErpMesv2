<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Admin\Factory;
use App\Models\Workflow\InvoiceLines;
use App\Models\Accounting\AccountingEntry;
use App\Models\Purchases\PurchaseInvoiceLines;
use App\Models\Accounting\AccountingAllocation;

class AccountingEntryService
{
   /**
     * Create an accounting entry.
     *
     * @param string $journalCode
     * @param string $journalLabel
     * @param \App\Models\Workflow\InvoiceLines|\App\Models\Purchases\PurchaseInvoiceLines $invoiceLine
     * @param float $debitAmount
     * @param float $creditAmount
     * @return AccountingEntry
     */
    private function createEntry($journalCode, $journalLabel, $invoice, $invoiceLine, $debitAmount, $creditAmount, $lineLabel)
    {
        $factory = Factory::first();
        
        // Retrieve accounting information from the invoice line
        $allocation = AccountingAllocation::find($invoiceLine->accounting_allocation_id);

        // Create the accounting entry
        return AccountingEntry::create([
            'journal_code' => $journalCode,
            'journal_label' => $journalLabel,
            'sequence_number' => $this->getNextSequenceNumber(),
            'accounting_date' => Carbon::now(),
            'account_number' => $allocation->code_account,
            'account_label' => $allocation->label,
            'justification_reference' => $invoice->code,
            'justification_date' => $invoice->created_at,
            'document_reference' => $invoice->code,
            'document_date' => $invoice->created_at,
            'entry_label' => ($journalCode === 'VENT' ? 'Vente ' : 'Achat ') . $lineLabel,
            'debit_amount' => $debitAmount,
            'credit_amount' => $creditAmount,
            'entry_lettering' => null,
            'lettering_date' => null,
            'validation_date' => Carbon::now(),
            'currency_code' => $factory->curency,
            'invoice_line_id' => ($journalCode === 'VENT' ? $invoiceLine->id : null),
            'purchase_invoice_line_id' => ($journalCode === 'VENT' ? null : $invoiceLine->id),
        ]);
    }

    /**
     * Create a sale entry for an invoice line.
     *
     * @param \App\Models\Workflow\InvoiceLines $invoiceLine
     * @return AccountingEntry
     */
    public function createSaleEntry(InvoiceLines $invoiceLine)
    {
        return $this->createEntry('VENT', 'Journal des Ventes',$invoiceLine->invoice, $invoiceLine, 0, $invoiceLine->orderLine->total, $invoiceLine->orderLine->label);
    }

    /**
     * Create a purchase entry for an invoice line.
     *
     * @param \App\Models\Purchases\PurchaseInvoiceLines $PurchaseInvoiceLine
     * @return AccountingEntry
     */
    public function createPurchaseEntry(PurchaseInvoiceLines $PurchaseInvoiceLine)
    {
        return $this->createEntry('ACHAT', 'Journal des Achats', $PurchaseInvoiceLine->purchaseInvoice, $PurchaseInvoiceLine, $PurchaseInvoiceLine->purchaseLines->total, 0, $PurchaseInvoiceLine->purchaseLines->label );
    }
    /**
     * Obtenir le prochain numéro de séquence pour le journal comptable
     *
     * @return int
     */
    protected function getNextSequenceNumber()
    {
        return AccountingEntry::max('sequence_number') + 1;
    }

    /**
     * Get the allocation ID based on type_imputation and accounting_vats_id.
     *
     * @param int $typeImputation
     * @param int $vatId
     * @return int|null
     */
    public function getAllocationId(int $typeImputation, int $vatId): ?int
    {
        $allocation = AccountingAllocation::where('type_imputation', $typeImputation)
                                            ->where('accounting_vats_id', $vatId)
                                            ->first();

        return $allocation ? $allocation->id : null;
    }
}