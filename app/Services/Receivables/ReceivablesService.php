<?php

namespace App\Services\Receivables;

use App\Enums\CreditBlockLevel;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Créances clients : reste dû par facture et encours par société.
 *
 * Aucun montant n'est stocké sur les factures : tout se recalcule depuis les
 * lignes (prix figé sur la ligne de facture, sinon celui de la ligne de
 * commande — même règle que InvoiceLines::resolved_* et AffairSummaryService).
 *
 *   reste dû = TTC − règlements − avoirs émis sur cette facture
 *
 * L'avoir n'a pas d'imputation propre : il corrige la facture qu'il cite
 * (credit_notes.invoices_id). Un reste dû négatif (trop-perçu) compte pour
 * zéro, pour ne pas masquer l'impayé d'une autre facture.
 *
 * Le statut `invoices.statu` n'est pas lu : il se change à la main ou par la
 * PDP, et ne tient pas compte des avoirs.
 */
class ReceivablesService
{
    /**
     * En deçà, une facture est soldée (arrondis des lignes). Écrit en
     * littéral dans le SQL : lié en paramètre, SQLite le compare comme texte.
     */
    public const EPSILON = 0.005;

    /** Factures (au sens créance) : les proformas (3) n'en sont pas. */
    public const INVOICE_TYPE = 1;

    /**
     * Une ligne par facture : companies_id, échéance, amount_ttc, paid,
     * credited et balance (reste dû, éventuellement négatif).
     *
     * @param bool $includeDrafts un brouillon n'est pas relançable, mais il
     *        compte dans l'encours : ses lignes de commande ne sont plus
     *        « à facturer », elles disparaîtraient sinon des deux côtés.
     */
    public function invoiceBalances(bool $includeDrafts = false): Builder
    {
        $invoices = DB::table('invoices')
            ->whereNull('invoices.deleted_at')
            ->where('invoices.invoice_type', self::INVOICE_TYPE)
            ->when(! $includeDrafts, fn ($q) => $q->where('invoices.statu', '!=', 1))
            ->select([
                'invoices.id', 'invoices.code', 'invoices.label', 'invoices.statu',
                'invoices.companies_id', 'invoices.companies_contacts_id',
                'invoices.due_date', 'invoices.created_at',
                'invoices.reminder_level_id', 'invoices.last_reminder_at',
            ])
            ->selectSub($this->invoiceTotalQuery(), 'amount_ttc')
            ->selectSub(
                DB::table('invoice_payments')
                    ->selectRaw('COALESCE(SUM(invoice_payments.amount), 0)')
                    ->whereColumn('invoice_payments.invoice_id', 'invoices.id'),
                'paid'
            )
            ->selectSub($this->creditedQuery(), 'credited');

        return DB::query()
            ->fromSub($invoices, 'inv')
            ->select('inv.*')
            ->selectRaw('ROUND(inv.amount_ttc, 2) - inv.paid - ROUND(inv.credited, 2) AS balance');
    }

    /**
     * Solde d'une facture (brouillon compris) : amount_ttc, paid, credited, balance.
     * Même calcul que les relances et l'encours, pour l'onglet Règlements de la fiche.
     */
    public function invoiceBalance(int $invoiceId): ?object
    {
        return DB::query()
            ->fromSub($this->invoiceBalances(true), 'b')
            ->where('b.id', $invoiceId)
            ->first();
    }

    /**
     * Factures émises qui restent dues, toutes sociétés ou une seule.
     */
    public function openInvoices(?int $companyId = null): Builder
    {
        return DB::query()
            ->fromSub($this->invoiceBalances(), 'b')
            ->whereRaw('b.balance > ' . self::EPSILON)
            ->when($companyId, fn ($q) => $q->where('b.companies_id', $companyId));
    }

    public function exposure(int $companyId): CreditExposure
    {
        return $this->exposures([$companyId])[$companyId];
    }

    /**
     * Encours de plusieurs sociétés en une requête (une page de liste).
     *
     * @param int[] $companyIds
     * @return array<int, CreditExposure> indexé par id de société
     */
    public function exposures(array $companyIds): array
    {
        $companyIds = array_values(array_unique(array_map('intval', $companyIds)));
        if ($companyIds === []) {
            return [];
        }

        $invoicesDue = DB::query()
            ->fromSub($this->invoiceBalances(includeDrafts: true)->whereIn('inv.companies_id', $companyIds), 'b')
            ->whereColumn('b.companies_id', 'companies.id')
            ->selectRaw('COALESCE(SUM(CASE WHEN b.balance > ' . self::EPSILON . ' THEN b.balance ELSE 0 END), 0)');

        $query = DB::table('companies')
            ->whereIn('companies.id', $companyIds)
            ->select(['companies.id', 'companies.credit_limit', 'companies.credit_block_level'])
            ->selectSub($invoicesDue, 'invoices_due');

        if (config('receivables.include_open_orders', true)) {
            $query->selectSub($this->openOrdersQuery(), 'open_orders');
        }

        $exposures = [];
        foreach ($query->get() as $row) {
            $exposures[(int) $row->id] = new CreditExposure(
                companyId:   (int) $row->id,
                invoicesDue: round((float) $row->invoices_due, 2),
                openOrders:  round((float) ($row->open_orders ?? 0), 2),
                limit:       $row->credit_limit !== null ? (float) $row->credit_limit : null,
                level:       CreditBlockLevel::fromColumn($row->credit_block_level),
            );
        }

        return $exposures;
    }

    /**
     * TTC de lignes de devis (conversion en commande). Les lignes de
     * présentation valent zéro.
     *
     * @param int[] $lineIds
     */
    public function quoteLinesTotal(int $quoteId, array $lineIds): float
    {
        if ($lineIds === []) {
            return 0.0;
        }

        return round((float) DB::table('quote_lines as ql')
            ->leftJoin('accounting_vats as qav', 'qav.id', '=', 'ql.accounting_vats_id')
            ->where('ql.quotes_id', $quoteId)
            ->whereIn('ql.id', array_map('intval', $lineIds))
            ->whereNull('ql.deleted_at')
            ->selectRaw('COALESCE(SUM(ql.selling_price * ql.qty'
                . ' * (1 - COALESCE(ql.discount, 0) / 100.0) * (1 + COALESCE(qav.rate, 0) / 100.0)), 0) AS total')
            ->value('total'), 2);
    }

    /** TTC d'une facture, corrélé sur invoices.id. */
    private function invoiceTotalQuery(): Builder
    {
        $lineHt = 'invoice_lines.qty * COALESCE(invoice_lines.unit_price, ol.selling_price, 0)'
            . ' * (1 - COALESCE(invoice_lines.discount, ol.discount, 0) / 100.0)';

        return DB::table('invoice_lines')
            ->leftJoin('order_lines as ol', 'ol.id', '=', 'invoice_lines.order_line_id')
            ->leftJoin('accounting_vats as av', 'av.id', '=', 'ol.accounting_vats_id')
            ->whereColumn('invoice_lines.invoices_id', 'invoices.id')
            ->whereNull('invoice_lines.deleted_at')
            ->selectRaw("COALESCE(SUM(($lineHt) * (1 + COALESCE(invoice_lines.vat_rate, av.rate, 0) / 100.0)), 0)");
    }

    /** TTC des avoirs émis sur une facture, corrélé sur invoices.id. */
    private function creditedQuery(): Builder
    {
        $lineHt = 'cnl.qty * COALESCE(cnl.unit_price, col.selling_price, 0)'
            . ' * (1 - COALESCE(cnl.discount, col.discount, 0) / 100.0)';

        return DB::table('credit_note_lines as cnl')
            ->join('credit_notes as cn', 'cn.id', '=', 'cnl.credit_note_id')
            ->leftJoin('order_lines as col', 'col.id', '=', 'cnl.order_line_id')
            ->leftJoin('accounting_vats as cav', 'cav.id', '=', 'col.accounting_vats_id')
            ->whereColumn('cn.invoices_id', 'invoices.id')
            ->whereNull('cn.deleted_at')
            ->whereNull('cnl.deleted_at')
            ->whereIn('cn.statu', config('receivables.deducted_credit_note_statuses', [1, 2]))
            ->selectRaw("COALESCE(SUM(($lineHt) * (1 + COALESCE(cnl.vat_rate, cav.rate, 0) / 100.0)), 0)");
    }

    /**
     * TTC commandé non facturé d'une société, corrélé sur companies.id.
     * Prix stocké de la ligne (`selling_price`) : une ligne en prix calculé
     * (use_calculated_price) est comptée à son dernier prix enregistré.
     */
    private function openOrdersQuery(): Builder
    {
        return DB::table('order_lines as ol')
            ->join('orders as o', 'o.id', '=', 'ol.orders_id')
            ->leftJoin('accounting_vats as oav', 'oav.id', '=', 'ol.accounting_vats_id')
            ->whereColumn('o.companies_id', 'companies.id')
            ->where('o.type', 1)
            ->whereNotIn('o.statu', config('receivables.excluded_order_statuses', [0, 5, 6]))
            ->whereNull('o.deleted_at')
            ->whereNull('ol.deleted_at')
            ->where(fn ($q) => $q->where('ol.line_type', 'article')->orWhereNull('ol.line_type'))
            ->where('ol.invoiced_remaining_qty', '>', 0)
            ->selectRaw('COALESCE(SUM(ol.selling_price * ol.invoiced_remaining_qty'
                . ' * (1 - COALESCE(ol.discount, 0) / 100.0) * (1 + COALESCE(oav.rate, 0) / 100.0)), 0)');
    }
}
