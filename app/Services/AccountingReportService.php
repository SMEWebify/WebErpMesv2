<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class AccountingReportService
{
    // Statuts factures client comptabilisables (hors brouillon=1)
    private const INVOICE_STATUSES = [2, 3, 4, 5]; // Envoyée, En attente, Impayée, Payée

    // Statuts bons de commande fournisseur comptabilisables (hors brouillon=1, hors annulé=5)
    private const PURCHASE_STATUSES = [2, 3, 4]; // Commandé, Reçu partiel, Reçu

    /**
     * Chiffre d'affaires TTC — calculé en base à partir des lignes de facture.
     * Utilise le snapshot (unit_price/discount/vat_rate sur invoice_lines) en priorité,
     * repli sur order_lines pour les lignes antérieures à la migration.
     */
    public function getTotalRevenue(): float
    {
        $result = DB::table('invoice_lines as il')
            ->join('invoices as i', 'i.id', '=', 'il.invoices_id')
            ->join('order_lines as ol', 'ol.id', '=', 'il.order_line_id')
            ->leftJoin('accounting_vats as vat', 'vat.id', '=', 'ol.accounting_vats_id')
            ->whereIn('i.statu', self::INVOICE_STATUSES)
            ->whereNull('i.deleted_at')
            ->whereNull('il.deleted_at')
            ->selectRaw('
                COALESCE(SUM(
                    il.qty
                    * COALESCE(il.unit_price, ol.selling_price)
                    * (1 - COALESCE(il.discount, ol.discount, 0) / 100)
                    * (1 + COALESCE(il.vat_rate, vat.rate, 0) / 100)
                ), 0) as total
            ')
            ->value('total');

        return (float) ($result ?? 0);
    }

    /**
     * Dépenses TTC — calculé en base à partir des lignes de bon de commande fournisseur.
     */
    public function getTotalExpense(): float
    {
        $result = DB::table('purchase_lines as pl')
            ->join('purchases as p', 'p.id', '=', 'pl.purchases_id')
            ->leftJoin('accounting_vats as vat', 'vat.id', '=', 'pl.accounting_vats_id')
            ->whereIn('p.statu', self::PURCHASE_STATUSES)
            ->whereNull('p.deleted_at')
            ->whereNull('pl.deleted_at')
            ->selectRaw('
                COALESCE(SUM(
                    pl.qty
                    * pl.selling_price
                    * (1 - COALESCE(pl.discount, 0) / 100)
                    * (1 + COALESCE(vat.rate, 0) / 100)
                ), 0) as total
            ')
            ->value('total');

        return (float) ($result ?? 0);
    }

    public function getProfit(): float
    {
        return $this->getTotalRevenue() - $this->getTotalExpense();
    }
}
