<?php

namespace App\Services\Invoicing;

use App\Models\Accounting\AccountingEntry;
use App\Models\Companies\CompanyDocumentDefault;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\AccountingEntryService;
use App\Services\DocumentCodeGenerator;
use App\Services\InvoiceService;
use App\Services\Receivables\ReceivablesService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Factures d'acompte (invoice_type 4) et leur déduction sur les factures suivantes.
 *
 * L'acompte ne touche jamais aux quantités : ses lignes n'ont pas de ligne de
 * commande (une par taux de TVA de la commande), les BL et le « reste à facturer »
 * l'ignorent. Il est ensuite déduit des factures ordinaires de la commande par une
 * ligne négative (quantité −1, prix positif : BR-27 interdit un prix net négatif)
 * qui pointe sa ligne d'acompte (`invoice_lines.down_payment_line_id`).
 *
 * Déduction au prorata cumulé : après une facture, la part déduite d'un acompte
 * suit la part facturée de la commande (HT facturé / HT commandé). La facture qui
 * solde la commande reprend donc tout le reste, sans écart d'arrondi. Seule la part
 * **payée** d'un acompte émis est déductible : un acompte impayé reste dû sur sa
 * propre facture. Le reste à déduire n'est jamais stocké, il se relit sur les lignes.
 */
class DownPayments
{
    public const TYPE = 4;

    private const EPSILON = 0.005;

    public function __construct(
        private InvoiceService $invoiceService,
        private AccountingEntryService $accounting,
        private DocumentCodeGenerator $codes,
        private ReceivablesService $receivables,
    ) {}

    /**
     * HT de la commande par code de TVA (lignes article seulement).
     *
     * @return Collection<int, array{vat_id: ?int, rate: float, ht: float}>
     */
    public function orderBase(Orders $order): Collection
    {
        return OrderLines::with('VAT')
            ->where('orders_id', $order->id)
            ->articles()
            ->get()
            ->groupBy(fn (OrderLines $l) => (string) ($l->accounting_vats_id ?? ''))
            ->map(fn (Collection $lines) => [
                'vat_id' => $lines->first()->accounting_vats_id,
                'rate'   => (float) ($lines->first()->VAT?->rate ?? 0),
                'ht'     => round($lines->sum(fn (OrderLines $l) => $this->orderLineHt($l, (float) $l->qty)), 2),
            ])
            ->filter(fn (array $g) => $g['ht'] > 0)
            ->sortByDesc('ht')
            ->values();
    }

    public function orderTotal(Orders $order): float
    {
        return round((float) $this->orderBase($order)->sum('ht'), 2);
    }

    /** Factures d'acompte de la commande, brouillons compris, les plus anciennes d'abord. */
    public function forOrder(int $orderId): Collection
    {
        return Invoices::with('invoiceLines')
            ->where('invoice_type', self::TYPE)
            ->where('order_id', $orderId)
            ->orderBy('id')
            ->get();
    }

    /**
     * Crée une facture d'acompte en brouillon pour un montant HT, réparti sur les
     * taux de TVA de la commande au prorata de leur base.
     *
     * @throws \DomainException commande interne / annulée / sans montant, montant hors limites
     */
    public function create(Orders $order, float $amountHt, int $userId, ?string $label = null): Invoices
    {
        $amountHt = round($amountHt, 2);

        if ((int) $order->type !== 1) {
            throw new \DomainException(__('down_payments.refused.internal_order'));
        }
        if ((int) $order->statu === 6) {
            throw new \DomainException(__('down_payments.refused.canceled_order'));
        }

        $base  = $this->orderBase($order);
        $total = round((float) $base->sum('ht'), 2);
        if ($total <= 0) {
            throw new \DomainException(__('down_payments.refused.empty_order'));
        }
        if ($amountHt <= 0) {
            throw new \DomainException(__('down_payments.refused.amount'));
        }

        return DB::transaction(function () use ($order, $amountHt, $userId, $label, $base, $total) {
            // Verrou de la commande : deux acomptes simultanés ne dépassent pas le total.
            Orders::whereKey($order->id)->lockForUpdate()->first();

            $already = $this->forOrder($order->id)->sum(fn (Invoices $i) => $this->invoiceHt($i));
            if ($amountHt + $already > $total + self::EPSILON) {
                throw new \DomainException(__('down_payments.refused.over_total', [
                    'max' => number_format(max(0, $total - $already), 2, ',', ' '),
                ]));
            }

            $percent   = round($amountHt / $total * 100, 2);
            $recipient = CompanyDocumentDefault::resolveFor(
                $order->companies_id, 'invoice', $order->companies_addresses_id, $order->companies_contacts_id
            );

            $code    = $this->codes->generateDocumentCode('invoice');
            $invoice = $this->invoiceService->createInvoice(
                $code,
                $label ?: __('down_payments.invoice_label', ['order' => $order->code]),
                $order->companies_id,
                $recipient['address_id'],
                $recipient['contact_id'],
                $userId,
                $order->customer_reference,
                $order->accounting_payment_conditions_id,
            );
            $invoice->invoice_type = self::TYPE;
            $invoice->order_id     = $order->id;
            $invoice->save();

            // Montant réparti par taux ; le dernier groupe prend l'arrondi.
            $remaining = $amountHt;
            $ordre     = 10;
            foreach ($base as $i => $group) {
                $part = $i === $base->count() - 1
                    ? $remaining
                    : round($group['ht'] * $amountHt / $total, 2);
                $remaining = round($remaining - $part, 2);
                if ($part <= 0) {
                    continue;
                }

                $line = InvoiceLines::create([
                    'invoices_id'              => $invoice->id,
                    'label'                    => __('down_payments.line_label', [
                        'percent' => rtrim(rtrim(number_format($percent, 2, ',', ''), '0'), ','),
                        'order'   => $order->code,
                    ]),
                    'code'                     => $order->code,
                    'ordre'                    => $ordre,
                    'qty'                      => 1,
                    'unit_price'               => $part,
                    'discount'                 => 0,
                    'vat_rate'                 => $group['rate'],
                    'accounting_vats_id'       => $group['vat_id'],
                    'accounting_allocation_id' => $group['vat_id'] ? $this->accounting->getAllocationId(1, $group['vat_id']) : null,
                    'invoice_status'           => 1,
                ]);
                $this->book($line->setRelation('invoice', $invoice));
                $ordre += 10;
            }

            return $invoice;
        });
    }

    /**
     * Ajoute à une facture en brouillon les déductions des acomptes de ses commandes.
     * À appeler dans la transaction de création, **après** la mise à jour des
     * quantités facturées des lignes de commande (le prorata en dépend).
     *
     * @return Collection<int, InvoiceLines> lignes de déduction créées
     */
    public function applyToInvoice(Invoices $invoice): Collection
    {
        $created = collect();
        // Relue : une facture tout juste créée ne porte pas le statut par défaut de la colonne.
        $invoice = $invoice->fresh() ?? $invoice;
        if ((int) $invoice->invoice_type !== 1 || (int) $invoice->statu !== 1) {
            return $created;
        }

        $lines = InvoiceLines::with('orderLine')
            ->where('invoices_id', $invoice->id)
            ->get();

        $orderIds = $lines->whereNull('down_payment_line_id')
            ->pluck('orderLine.orders_id')
            ->filter()
            ->unique();
        if ($orderIds->isEmpty()) {
            return $created;
        }

        $ordre = (int) $lines->max('ordre');

        foreach ($orderIds as $orderId) {
            $acomptes = $this->issuedForOrder((int) $orderId);
            if ($acomptes->isEmpty()) {
                continue;
            }

            $order = Orders::find($orderId);
            $total = $order ? $this->orderTotal($order) : 0;
            if ($total <= 0) {
                continue;
            }

            // Jamais plus que ce que la facture porte pour cette commande.
            $room = round((float) $lines
                ->whereNull('down_payment_line_id')
                ->filter(fn (InvoiceLines $l) => (int) $l->orderLine?->orders_id === (int) $orderId)
                ->sum(fn (InvoiceLines $l) => $l->line_total), 2);

            $share = min(1.0, $this->invoicedHt((int) $orderId) / $total);

            foreach ($acomptes as $acompte) {
                foreach ($acompte->invoiceLines as $acompteLine) {
                    $target = min(
                        $this->available($acompteLine),
                        round($this->lineHt($acompteLine) * $share - $this->deducted($acompteLine), 2),
                        $room,
                    );
                    if ($target < self::EPSILON) {
                        continue;
                    }

                    $ordre += 10;
                    $created->push($this->createDeductionLine($invoice, $acompte, $acompteLine, $target, $ordre));
                    $room = round($room - $target, 2);
                }
            }
        }

        return $created;
    }

    /**
     * Brouillon : retire les déductions et les recalcule (bouton « Recalculer »).
     */
    public function recompute(Invoices $invoice): Collection
    {
        $this->assertDraft($invoice);

        return DB::transaction(function () use ($invoice) {
            $deductions = InvoiceLines::where('invoices_id', $invoice->id)
                ->whereNotNull('down_payment_line_id')
                ->get();
            foreach ($deductions as $line) {
                AccountingEntry::where('invoice_line_id', $line->id)->delete();
                $line->delete();
            }

            return $this->applyToInvoice($invoice->fresh());
        });
    }

    /**
     * Brouillon : fixe le montant HT d'une déduction, borné par la part payée de
     * l'acompte qui n'est pas déjà déduite ailleurs.
     *
     * @throws \DomainException
     */
    public function setDeduction(InvoiceLines $line, float $amountHt): InvoiceLines
    {
        $this->assertDraft($line->invoice);
        $amountHt = round($amountHt, 2);

        $acompteLine = $line->downPaymentLine;
        if (! $acompteLine) {
            throw new \DomainException(__('down_payments.refused.not_a_deduction'));
        }

        $max = $this->available($acompteLine, $line->id);
        if ($amountHt < 0 || $amountHt > $max + self::EPSILON) {
            throw new \DomainException(__('down_payments.refused.deduction_max', [
                'max' => number_format($max, 2, ',', ' '),
            ]));
        }

        $line->unit_price = $amountHt;
        $line->qty        = -1;
        $line->discount   = 0;
        $line->save();
        $this->accounting->refreshSaleEntry($line);

        return $line;
    }

    /**
     * Part HT d'une ligne d'acompte encore déductible : part payée (hors avoirs) de
     * l'acompte émis, moins ce qui est déjà déduit (sauf $exceptLineId).
     */
    public function available(InvoiceLines $acompteLine, ?int $exceptLineId = null): float
    {
        $acompte = $acompteLine->invoice;
        if (! $acompte || (int) $acompte->invoice_type !== self::TYPE || (int) $acompte->statu === 1) {
            return 0.0;
        }

        return max(0.0, round($this->lineHt($acompteLine) * $this->paidRatio($acompte)
            - $this->deducted($acompteLine, $exceptLineId), 2));
    }

    /** HT déjà déduit d'une ligne d'acompte, factures supprimées exclues. */
    public function deducted(InvoiceLines $acompteLine, ?int $exceptLineId = null): float
    {
        return round((float) InvoiceLines::where('down_payment_line_id', $acompteLine->id)
            ->when($exceptLineId, fn ($q) => $q->where('id', '<>', $exceptLineId))
            ->whereHas('invoice')
            ->get()
            ->sum(fn (InvoiceLines $l) => -(float) $l->qty * (float) $l->unit_price), 2);
    }

    /**
     * Récapitulatif d'un acompte pour l'écran : HT, TTC, payé, déduit, reste.
     *
     * @return array{ht: float, ttc: float, paid: float, deducted: float, available: float, remaining: float}
     */
    public function summary(Invoices $acompte): array
    {
        $ht       = $this->invoiceHt($acompte);
        $balance  = $this->receivables->invoiceBalance($acompte->id);
        $deducted = round($acompte->invoiceLines->sum(fn (InvoiceLines $l) => $this->deducted($l)), 2);

        return [
            'ht'        => $ht,
            'ttc'       => round((float) ($balance->amount_ttc ?? 0), 2),
            'paid'      => round((float) ($balance->paid ?? 0), 2),
            'deducted'  => $deducted,
            'available' => round($acompte->invoiceLines->sum(fn (InvoiceLines $l) => $this->available($l)), 2),
            'remaining' => max(0.0, round($ht - $deducted, 2)),
        ];
    }

    /** Codes des factures d'acompte que déduit une facture (Factur-X BT-25, PDF). */
    public function deductedInvoices(Invoices $invoice): Collection
    {
        $ids = InvoiceLines::where('invoices_id', $invoice->id)
            ->whereNotNull('down_payment_line_id')
            ->pluck('down_payment_line_id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return Invoices::whereIn('id', InvoiceLines::whereIn('id', $ids)->pluck('invoices_id'))
            ->orderBy('id')
            ->get(['id', 'code', 'created_at']);
    }

    private function createDeductionLine(Invoices $invoice, Invoices $acompte, InvoiceLines $acompteLine, float $ht, int $ordre): InvoiceLines
    {
        $line = new InvoiceLines([
            'invoices_id'              => $invoice->id,
            'label'                    => __('down_payments.deduction_label', ['code' => $acompte->code]),
            'code'                     => $acompte->code,
            'ordre'                    => $ordre,
            'qty'                      => -1,
            'unit_price'               => $ht,
            'discount'                 => 0,
            'vat_rate'                 => $acompteLine->vat_rate,
            'accounting_vats_id'       => $acompteLine->accounting_vats_id,
            'accounting_allocation_id' => $acompteLine->accounting_allocation_id,
            'methods_units_id'         => $acompteLine->methods_units_id,
            'invoice_status'           => $invoice->statu,
        ]);
        $line->down_payment_line_id = $acompteLine->id;
        $line->save();

        $this->book($line->setRelation('invoice', $invoice));

        return $line;
    }

    private function book(InvoiceLines $line): void
    {
        if ($line->accounting_allocation_id) {
            $this->accounting->createSaleEntry($line);
        }
    }

    /** Acomptes émis de la commande, verrouillés jusqu'à la fin de la transaction. */
    private function issuedForOrder(int $orderId): Collection
    {
        return Invoices::with('invoiceLines')
            ->where('invoice_type', self::TYPE)
            ->where('order_id', $orderId)
            ->where('statu', '!=', 1)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /** HT déjà facturé sur la commande (quantités facturées, brouillons compris). */
    private function invoicedHt(int $orderId): float
    {
        return round((float) OrderLines::where('orders_id', $orderId)
            ->articles()
            ->get()
            ->sum(fn (OrderLines $l) => $this->orderLineHt($l, (float) $l->invoiced_qty)), 2);
    }

    /** Part payée d'un acompte, bornée par ce qui n'a pas été remboursé par avoir. */
    private function paidRatio(Invoices $acompte): float
    {
        $balance = $this->receivables->invoiceBalance($acompte->id);
        $ttc     = (float) ($balance->amount_ttc ?? 0);
        if ($ttc <= 0) {
            return 0.0;
        }

        $covered = min((float) $balance->paid, $ttc - (float) $balance->credited);

        return max(0.0, min(1.0, $covered / $ttc));
    }

    private function orderLineHt(OrderLines $line, float $qty): float
    {
        return $qty * (float) $line->selling_price * (1 - (float) $line->discount / 100);
    }

    private function lineHt(InvoiceLines $line): float
    {
        return round((float) $line->qty * (float) $line->unit_price * (1 - (float) $line->discount / 100), 2);
    }

    private function invoiceHt(Invoices $invoice): float
    {
        return round($invoice->invoiceLines->sum(fn (InvoiceLines $l) => $this->lineHt($l)), 2);
    }

    private function assertDraft(?Invoices $invoice): void
    {
        if (! $invoice || (int) $invoice->statu !== 1) {
            throw new \DomainException(__('down_payments.refused.not_draft'));
        }
    }
}
