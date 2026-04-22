<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Admin\Factory;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\InvoicePayment;
use App\Models\Accounting\AccountingEntry;
use App\Models\Purchases\PurchaseInvoiceLines;
use App\Models\Accounting\AccountingAllocation;

class AccountingEntryService
{
    /**
     * Numéro de séquence unique par journal et par exercice fiscal.
     * Toutes les lignes d'une même pièce partagent le même numéro.
     */
    protected function getNextSequenceNumber(string $journalCode, Carbon $date): int
    {
        return (AccountingEntry::where('journal_code', $journalCode)
            ->whereYear('accounting_date', $date->year)
            ->max('sequence_number') ?? 0) + 1;
    }

    private function baseLine(string $journalCode, string $journalLabel, int $seq, Carbon $date, string $currency): array
    {
        return [
            'journal_code'    => $journalCode,
            'journal_label'   => $journalLabel,
            'sequence_number' => $seq,
            'accounting_date' => $date,
            'validation_date' => Carbon::now(),
            'currency_code'   => $currency,
            'exported'        => false,
        ];
    }

    /**
     * Écriture de vente (journal VENT) — 3 lignes équilibrées par ligne de facture.
     *
     * Ligne 1 : Débit 411 (client)                  → TTC
     * Ligne 2 : Crédit compte produit (701xxx)       → HT
     * Ligne 3 : Crédit compte TVA (44571x)           → TVA (omise si taux = 0)
     */
    public function createSaleEntry(InvoiceLines $invoiceLine): void
    {
        $invoice    = $invoiceLine->invoice;
        $factory    = Factory::first();
        $currency   = $factory->curency ?? 'EUR';
        $allocation = AccountingAllocation::find($invoiceLine->accounting_allocation_id);

        if (!$allocation) return;

        // Montants
        $unitPrice = (float) ($invoiceLine->unit_price ?? $invoiceLine->orderLine?->selling_price ?? 0);
        $discount  = (float) ($invoiceLine->discount  ?? $invoiceLine->orderLine?->discount ?? 0);
        $vatRate   = (float) ($invoiceLine->vat_rate   ?? $invoiceLine->orderLine?->VAT?->rate ?? 0);
        $qty       = (float) $invoiceLine->qty;

        $ht  = round($qty * $unitPrice * (1 - $discount / 100), 2);
        $tva = round($ht * $vatRate / 100, 2);
        $ttc = $ht + $tva;

        if ($ttc <= 0) return;

        // Client en compte auxiliaire (411 collectif)
        $clientCode  = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $invoice->companie?->code ?? ''), 0, 8));
        $clientLabel = $invoice->companie?->label ?? 'Client';

        $date = Carbon::parse($invoice->created_at);
        $seq  = $this->getNextSequenceNumber('VENT', $date);
        $base = $this->baseLine('VENT', 'Journal des Ventes', $seq, $date, $currency);

        $pieceRef  = $invoice->code;
        $pieceDate = $date;
        $lib       = 'Vente ' . ($invoiceLine->orderLine?->label ?? $invoice->code);

        // Ligne 1 — Débit 411 (TTC)
        AccountingEntry::create($base + [
            'account_number'           => '411',
            'account_label'            => 'Clients',
            'auxiliary_account_number' => $clientCode,
            'auxiliary_account_label'  => $clientLabel,
            'justification_reference'  => $pieceRef,
            'justification_date'       => $pieceDate,
            'document_reference'       => $pieceRef,
            'document_date'            => $pieceDate,
            'entry_label'              => $lib,
            'debit_amount'             => $ttc,
            'credit_amount'            => 0,
            'invoice_line_id'          => $invoiceLine->id,
        ]);

        // Ligne 2 — Crédit compte produit (HT)
        AccountingEntry::create($base + [
            'account_number'           => $allocation->code_account,
            'account_label'            => $allocation->label,
            'auxiliary_account_number' => null,
            'auxiliary_account_label'  => null,
            'justification_reference'  => $pieceRef,
            'justification_date'       => $pieceDate,
            'document_reference'       => $pieceRef,
            'document_date'            => $pieceDate,
            'entry_label'              => $lib,
            'debit_amount'             => 0,
            'credit_amount'            => $ht,
            'invoice_line_id'          => $invoiceLine->id,
        ]);

        // Ligne 3 — Crédit TVA (seulement si taux > 0)
        if ($tva > 0) {
            AccountingEntry::create($base + [
                'account_number'           => $allocation->vat_account,
                'account_label'            => 'TVA collectée',
                'auxiliary_account_number' => null,
                'auxiliary_account_label'  => null,
                'justification_reference'  => $pieceRef,
                'justification_date'       => $pieceDate,
                'document_reference'       => $pieceRef,
                'document_date'            => $pieceDate,
                'entry_label'              => $lib,
                'debit_amount'             => 0,
                'credit_amount'            => $tva,
                'invoice_line_id'          => $invoiceLine->id,
            ]);
        }
    }

    /**
     * Écriture d'achat (journal ACHAT) — 3 lignes équilibrées.
     *
     * Ligne 1 : Débit compte achat (601xxx)          → HT
     * Ligne 2 : Débit TVA déductible (44566x)        → TVA (omise si taux = 0)
     * Ligne 3 : Crédit 401 (fournisseur)             → TTC
     */
    public function createPurchaseEntry(PurchaseInvoiceLines $line): void
    {
        $invoice    = $line->purchaseInvoice;
        $factory    = Factory::first();
        $currency   = $factory->curency ?? 'EUR';
        $allocation = AccountingAllocation::find($line->accounting_allocation_id);

        if (!$allocation) return;

        $purchaseLine = $line->purchaseLines;
        $unitPrice    = (float) ($purchaseLine?->selling_price ?? 0);
        $qty          = (float) ($purchaseLine?->qty ?? 1);
        $discount     = (float) ($purchaseLine?->discount ?? 0);
        $vatRate      = (float) ($purchaseLine?->VAT?->rate ?? 0);

        $ht  = round($qty * $unitPrice * (1 - $discount / 100), 2);
        $tva = round($ht * $vatRate / 100, 2);
        $ttc = $ht + $tva;

        if ($ttc <= 0) return;

        $supplierCode  = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $invoice->companie?->code ?? ''), 0, 8));
        $supplierLabel = $invoice->companie?->label ?? 'Fournisseur';

        $date = Carbon::parse($invoice->created_at);
        $seq  = $this->getNextSequenceNumber('ACHAT', $date);
        $base = $this->baseLine('ACHAT', 'Journal des Achats', $seq, $date, $currency);

        $pieceRef = $invoice->supplier_reference ?? $invoice->code;
        $lib      = 'Achat ' . ($purchaseLine?->label ?? $invoice->code);

        // Ligne 1 — Débit compte achat (HT)
        AccountingEntry::create($base + [
            'account_number'           => $allocation->code_account,
            'account_label'            => $allocation->label,
            'auxiliary_account_number' => null,
            'auxiliary_account_label'  => null,
            'justification_reference'  => $pieceRef,
            'justification_date'       => $date,
            'document_reference'       => $invoice->code,
            'document_date'            => $date,
            'entry_label'              => $lib,
            'debit_amount'             => $ht,
            'credit_amount'            => 0,
            'purchase_invoice_line_id' => $line->id,
        ]);

        // Ligne 2 — Débit TVA déductible (si taux > 0)
        if ($tva > 0) {
            AccountingEntry::create($base + [
                'account_number'           => $allocation->vat_account,
                'account_label'            => 'TVA déductible',
                'auxiliary_account_number' => null,
                'auxiliary_account_label'  => null,
                'justification_reference'  => $pieceRef,
                'justification_date'       => $date,
                'document_reference'       => $invoice->code,
                'document_date'            => $date,
                'entry_label'              => $lib,
                'debit_amount'             => $tva,
                'credit_amount'            => 0,
                'purchase_invoice_line_id' => $line->id,
            ]);
        }

        // Ligne 3 — Crédit 401 fournisseur (TTC)
        AccountingEntry::create($base + [
            'account_number'           => '401',
            'account_label'            => 'Fournisseurs',
            'auxiliary_account_number' => $supplierCode,
            'auxiliary_account_label'  => $supplierLabel,
            'justification_reference'  => $pieceRef,
            'justification_date'       => $date,
            'document_reference'       => $invoice->code,
            'document_date'            => $date,
            'entry_label'              => $lib,
            'debit_amount'             => 0,
            'credit_amount'            => $ttc,
            'purchase_invoice_line_id' => $line->id,
        ]);
    }

    /**
     * Écriture d'encaissement (journal ENCAIS) — 2 lignes équilibrées.
     *
     * Ligne 1 : Débit compte trésorerie (512xxx)     → montant règlement
     * Ligne 2 : Crédit 411 client                    → montant règlement
     */
    public function createPaymentEntry(InvoicePayment $payment): void
    {
        $invoice  = $payment->invoice;
        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';
        $method   = $payment->paymentMethod;

        $bankAccount = $method?->code_account ?: '512000';
        $bankLabel   = $method?->label        ?: 'Banque';

        $clientCode  = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $invoice->companie?->code ?? ''), 0, 8));
        $clientLabel = $invoice->companie?->label ?? 'Client';

        $ref = $payment->reference
            ? "{$invoice->code} - {$payment->reference}"
            : $invoice->code;

        $date = Carbon::parse($payment->payment_date);
        $seq  = $this->getNextSequenceNumber('ENCAIS', $date);
        $base = $this->baseLine('ENCAIS', 'Journal des Encaissements', $seq, $date, $currency);
        $lib  = 'Règlement ' . $invoice->code;

        // Ligne 1 — Débit trésorerie
        AccountingEntry::create($base + [
            'account_number'           => $bankAccount,
            'account_label'            => $bankLabel,
            'auxiliary_account_number' => null,
            'auxiliary_account_label'  => null,
            'justification_reference'  => $ref,
            'justification_date'       => $date,
            'document_reference'       => $invoice->code,
            'document_date'            => Carbon::parse($invoice->created_at),
            'entry_label'              => $lib,
            'debit_amount'             => $payment->amount,
            'credit_amount'            => 0,
        ]);

        // Ligne 2 — Crédit 411 client
        AccountingEntry::create($base + [
            'account_number'           => '411',
            'account_label'            => 'Clients',
            'auxiliary_account_number' => $clientCode,
            'auxiliary_account_label'  => $clientLabel,
            'justification_reference'  => $ref,
            'justification_date'       => $date,
            'document_reference'       => $invoice->code,
            'document_date'            => Carbon::parse($invoice->created_at),
            'entry_label'              => $lib,
            'debit_amount'             => 0,
            'credit_amount'            => $payment->amount,
        ]);
    }

    public function getAllocationId(int $typeImputation, int $vatId): ?int
    {
        $allocation = AccountingAllocation::where('type_imputation', $typeImputation)
            ->where('accounting_vats_id', $vatId)
            ->first();

        return $allocation?->id;
    }
}
