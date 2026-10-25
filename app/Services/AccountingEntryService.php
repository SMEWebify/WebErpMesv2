<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Admin\Factory;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\InvoicePayment;
use App\Models\Workflow\CreditNotes;
use App\Models\Workflow\CreditNoteLines;
use App\Models\Accounting\AccountingEntry;
use App\Models\Purchases\PurchaseInvoiceLines;
use App\Models\Accounting\AccountingAllocation;
use App\Services\Accounting\VatResolver;
use App\Services\Accounting\VatRuleNotFoundException;

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

        // La matrice de TVA (vente) prime sur l'allocation pour les comptes ;
        // l'allocation reste le repli. Une matrice incomplète ne bloque pas le
        // grand livre (le blocage est à l'émission) : on retombe sur l'allocation.
        $rule = null;
        try {
            $rule = app(VatResolver::class)->resolveSale($invoice->companie, $invoiceLine->Product);
        } catch (VatRuleNotFoundException $e) {
            $rule = null;
        }

        $salesAccount = $rule?->sales_account ?: $allocation?->code_account;
        $salesLabel   = $allocation?->label ?? 'Vente';
        $vatAccount   = $rule?->vat_account  ?: $allocation?->vat_account;

        if (!$salesAccount) return;

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
            'account_number'           => $salesAccount,
            'account_label'            => $salesLabel,
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
                'account_number'           => $vatAccount,
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
        $invoice      = $line->purchaseInvoice;
        $factory      = Factory::first();
        $currency     = $factory->curency ?? 'EUR';
        $allocation   = AccountingAllocation::find($line->accounting_allocation_id);
        $purchaseLine = $line->purchaseLines;

        // Matrice de TVA (achat) prioritaire sur l'allocation ; repli sur elle.
        $rule = null;
        try {
            $rule = app(VatResolver::class)->resolvePurchase($invoice->companie, $purchaseLine?->product);
        } catch (VatRuleNotFoundException $e) {
            $rule = null;
        }

        $purchaseAccount = $rule?->purchase_account ?: $allocation?->code_account;
        $purchaseLabel   = $allocation?->label ?? 'Achat';
        $vatAccount      = $rule?->vat_account ?: $allocation?->vat_account;

        if (!$purchaseAccount) return;

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

        // Autoliquidation (acquisition UE / import) : la double écriture TVA due
        // ↔ déductible n'est pas automatisée — on signale l'écriture, la TVA se
        // passe à la main / se reprend sur la CA3.
        if ($rule?->manual_vat) {
            $lib .= ' (autoliquidation — écriture TVA à compléter)';
        }

        // Ligne 1 — Débit compte achat (HT)
        AccountingEntry::create($base + [
            'account_number'           => $purchaseAccount,
            'account_label'            => $purchaseLabel,
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
                'account_number'           => $vatAccount,
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
        // Lien vers le règlement : sa suppression efface ou contre-passe l'écriture.
        $base['invoice_payment_id'] = $payment->id;

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

    /**
     * Annule l'écriture d'encaissement d'un règlement supprimé : effacée si elle n'est pas encore
     * partie chez le comptable, contre-passée (débit et crédit inversés, à la date du jour) si
     * elle a déjà été exportée — une écriture transmise ne disparaît jamais.
     * À appeler avant de supprimer le règlement.
     */
    public function reversePaymentEntry(InvoicePayment $payment): void
    {
        $lines = AccountingEntry::where('invoice_payment_id', $payment->id)->get();
        if ($lines->isEmpty()) {
            $lines = $this->legacyPaymentLines($payment);
        }
        if ($lines->isEmpty()) {
            return;
        }

        $this->cancelEntries($lines);
    }

    /**
     * Régénère l'écriture de vente d'une ligne de brouillon dont la quantité, le prix ou la
     * remise vient de changer : sans ça, le FEC gardait le montant de la création.
     * L'ancienne écriture est effacée, ou contre-passée si elle a déjà été exportée.
     */
    public function refreshSaleEntry(InvoiceLines $invoiceLine): void
    {
        $lines = AccountingEntry::where('invoice_line_id', $invoiceLine->id)->get();
        if ($lines->isEmpty()) {
            return; // ligne sans écriture (pas d'affectation comptable) : rien à suivre
        }

        $this->cancelEntries($lines);
        $this->createSaleEntry($invoiceLine->fresh());
    }

    /**
     * Annule des lignes d'écriture : effacées si aucune n'est partie chez le comptable,
     * contre-passées sinon (débit et crédit inversés, à la date du jour, même journal) —
     * une écriture transmise ne disparaît jamais.
     */
    private function cancelEntries($lines): void
    {
        if (!$lines->contains(fn (AccountingEntry $line) => (bool) $line->exported)) {
            AccountingEntry::whereIn('id', $lines->pluck('id'))->delete();

            return;
        }

        $date = Carbon::today();
        foreach ($lines->groupBy('journal_code') as $journal => $group) {
            $first = $group->first();
            $base  = $this->baseLine($journal, $first->journal_label, $this->getNextSequenceNumber($journal, $date), $date, $first->currency_code ?? 'EUR');

            foreach ($group as $line) {
                AccountingEntry::create($base + [
                    'account_number'           => $line->account_number,
                    'account_label'            => $line->account_label,
                    'auxiliary_account_number' => $line->auxiliary_account_number,
                    'auxiliary_account_label'  => $line->auxiliary_account_label,
                    'justification_reference'  => $line->justification_reference,
                    'justification_date'       => $date,
                    'document_reference'       => $line->document_reference,
                    'document_date'            => $line->document_date,
                    'entry_label'              => 'Annulation ' . $line->entry_label,
                    'debit_amount'             => $line->credit_amount,
                    'credit_amount'            => $line->debit_amount,
                ]);
            }
        }

        // L'original contre-passé n'est plus « vivant » : détaché de sa pièce, il ne sera pas
        // annulé une seconde fois. La pièce reste lisible par document_reference.
        AccountingEntry::whereIn('id', $lines->pluck('id'))
            ->update(['invoice_line_id' => null, 'invoice_payment_id' => null, 'credit_note_line_id' => null]);
    }

    /**
     * Écriture d'avoir (journal VENT) — miroir de l'écriture de vente de la ligne facturée :
     * crédit 411 (TTC), débit produit (HT), débit TVA. Mêmes comptes que la facture
     * (matrice de TVA, sinon affectation de la ligne de facture). Sans compte, rien n'est écrit,
     * comme pour la facture.
     */
    public function createCreditNoteEntry(CreditNoteLines $line): void
    {
        $creditNote  = $line->creditNote;
        $invoiceLine = $line->invoiceLine;
        if (!$creditNote || !$invoiceLine) {
            return;
        }

        $allocation = AccountingAllocation::find($invoiceLine->accounting_allocation_id);
        try {
            $rule = app(VatResolver::class)->resolveSale($creditNote->companie, $invoiceLine->Product);
        } catch (VatRuleNotFoundException $e) {
            $rule = null;
        }

        $salesAccount = $rule?->sales_account ?: $allocation?->code_account;
        $salesLabel   = $allocation?->label ?? 'Vente';
        $vatAccount   = $rule?->vat_account ?: $allocation?->vat_account;
        if (!$salesAccount) {
            return;
        }

        $ht  = round((float) $line->qty * (float) $line->unit_price * (1 - (float) $line->discount / 100), 2);
        $tva = round($ht * (float) $line->vat_rate / 100, 2);
        $ttc = $ht + $tva;
        if ($ttc <= 0) {
            return;
        }

        $clientCode  = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $creditNote->companie?->code ?? ''), 0, 8));
        $clientLabel = $creditNote->companie?->label ?? 'Client';

        $date = Carbon::parse($creditNote->created_at);
        $base = $this->baseLine('VENT', 'Journal des Ventes', $this->getNextSequenceNumber('VENT', $date), $date, Factory::first()->curency ?? 'EUR');
        $common = [
            'justification_reference' => $creditNote->code,
            'justification_date'      => $date,
            'document_reference'      => $creditNote->code,
            'document_date'           => $date,
            'entry_label'             => 'Avoir ' . $creditNote->code . ' - ' . $line->display_label,
            'credit_note_line_id'     => $line->id,
        ];

        AccountingEntry::create($base + $common + [
            'account_number' => '411', 'account_label' => 'Clients',
            'auxiliary_account_number' => $clientCode, 'auxiliary_account_label' => $clientLabel,
            'debit_amount' => 0, 'credit_amount' => $ttc,
        ]);
        AccountingEntry::create($base + $common + [
            'account_number' => $salesAccount, 'account_label' => $salesLabel,
            'auxiliary_account_number' => null, 'auxiliary_account_label' => null,
            'debit_amount' => $ht, 'credit_amount' => 0,
        ]);
        if ($tva > 0) {
            AccountingEntry::create($base + $common + [
                'account_number' => $vatAccount, 'account_label' => 'TVA collectée',
                'auxiliary_account_number' => null, 'auxiliary_account_label' => null,
                'debit_amount' => $tva, 'credit_amount' => 0,
            ]);
        }
    }

    /**
     * Aligne les écritures d'un avoir sur son statut : un avoir refusé (3) n'est pas déduit de
     * la créance (config/receivables.php), il ne doit pas non plus compter en comptabilité.
     */
    public function syncCreditNoteEntries(CreditNotes $creditNote): void
    {
        $lineIds = $creditNote->creditNotelines()->pluck('id');
        $live    = AccountingEntry::whereIn('credit_note_line_id', $lineIds)->get();

        if ((int) $creditNote->statu === 3) {
            if ($live->isNotEmpty()) {
                $this->cancelEntries($live);
            }

            return;
        }

        if ($live->isEmpty()) {
            $creditNote->creditNotelines()->get()->each(fn (CreditNoteLines $line) => $this->createCreditNoteEntry($line));
        }
    }

    /**
     * Écriture d'un règlement antérieur au lien invoice_payment_id, retrouvée par ses références
     * (journal, facture, pièce, date, montant). Si plusieurs correspondent, la plus récente.
     */
    private function legacyPaymentLines(InvoicePayment $payment)
    {
        $invoice = $payment->invoice;
        $ref     = $payment->reference ? "{$invoice->code} - {$payment->reference}" : $invoice->code;

        $candidates = AccountingEntry::where('journal_code', 'ENCAIS')
            ->whereNull('invoice_payment_id')
            ->where('document_reference', $invoice->code)
            ->where('justification_reference', $ref)
            ->whereDate('accounting_date', Carbon::parse($payment->payment_date))
            ->where(fn ($q) => $q->where('debit_amount', $payment->amount)->orWhere('credit_amount', $payment->amount))
            ->orderByDesc('sequence_number')
            ->get();

        $sequence = $candidates->first()?->sequence_number;

        return $candidates->where('sequence_number', $sequence)->values();
    }

    public function getAllocationId(int $typeImputation, int $vatId): ?int
    {
        $allocation = AccountingAllocation::where('type_imputation', $typeImputation)
            ->where('accounting_vats_id', $vatId)
            ->first();

        return $allocation?->id;
    }
}
