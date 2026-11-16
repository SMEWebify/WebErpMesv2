<?php

namespace App\Services;

use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Orders;
use App\Services\Invoicing\DownPayments;
use App\Services\Receivables\ReceivablesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

/**
 * Facturé et encaissé d'une commande, pour le bilan d'affaire de la fiche commande.
 *
 * Montants **TTC**, comme le total de la commande auquel ils sont comparés. Comptent
 * les factures (1) et les factures d'acompte (4) — jamais les proformas. Une facture
 * qui déduit un acompte compte pour son net : acompte + factures nettes = facturé.
 */
class OrderInvoiceDataService
{
    public function __construct(private ReceivablesService $receivables) {}

    /** TTC facturé sur la commande (brouillons compris), acomptes compris. */
    public function getInvoicingAmount(Orders $order): float
    {
        return round(array_sum($this->orderShareByInvoice($order)), 2);
    }

    /**
     * TTC encaissé sur la commande : règlements des factures qui la portent. Une
     * facture qui porte aussi d'autres commandes compte au prorata de sa part.
     */
    public function getInvoicingReceivedPayment(Orders $order): string
    {
        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';

        $shares   = $this->orderShareByInvoice($order);
        $received = 0.0;

        if ($shares !== []) {
            $balances = DB::query()
                ->fromSub($this->receivables->invoiceBalances(true), 'b')
                ->whereIn('b.id', array_keys($shares))
                ->get(['b.id', 'b.amount_ttc', 'b.paid']);

            foreach ($balances as $balance) {
                $total = (float) $balance->amount_ttc;
                if ($total > 0) {
                    $received += (float) $balance->paid * min(1.0, $shares[$balance->id] / $total);
                }
            }
        }

        return Number::currency($received, $currency, config('app.locale'));
    }

    /**
     * TTC de la commande porté par chaque facture : lignes de ses lignes de commande,
     * factures d'acompte de la commande et leurs déductions.
     *
     * @return array<int, float> id de facture => TTC
     */
    private function orderShareByInvoice(Orders $order): array
    {
        $orderLineIds   = $order->orderLines->pluck('id');
        $acompteIds     = Invoices::where('invoice_type', DownPayments::TYPE)->where('order_id', $order->id)->pluck('id');
        $acompteLineIds = InvoiceLines::whereIn('invoices_id', $acompteIds)->pluck('id');

        $ttc = 'il.qty * COALESCE(il.unit_price, ol.selling_price, 0)'
            . ' * (1 - COALESCE(il.discount, ol.discount, 0) / 100.0)'
            . ' * (1 + COALESCE(il.vat_rate, av.rate, 0) / 100.0)';

        return DB::table('invoice_lines as il')
            ->join('invoices as i', 'i.id', '=', 'il.invoices_id')
            ->leftJoin('order_lines as ol', 'ol.id', '=', 'il.order_line_id')
            ->leftJoin('accounting_vats as av', 'av.id', '=', 'ol.accounting_vats_id')
            ->whereNull('il.deleted_at')
            ->whereNull('i.deleted_at')
            ->whereIn('i.invoice_type', ReceivablesService::RECEIVABLE_TYPES)
            ->where(fn ($q) => $q->whereIn('il.order_line_id', $orderLineIds)
                ->orWhereIn('il.down_payment_line_id', $acompteLineIds)
                ->orWhereIn('il.invoices_id', $acompteIds))
            ->groupBy('il.invoices_id')
            ->selectRaw("il.invoices_id, SUM($ttc) AS ttc")
            ->pluck('ttc', 'invoices_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }
}
