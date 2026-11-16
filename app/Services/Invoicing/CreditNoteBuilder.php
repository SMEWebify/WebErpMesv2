<?php

namespace App\Services\Invoicing;

use App\Models\Workflow\CreditNoteLines;
use App\Models\Workflow\CreditNotes;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Services\AccountingEntryService;
use App\Services\DocumentCodeGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Avoirs sur facture, entiers ou partiels.
 *
 * Par ligne : quantité et prix unitaire crédités (par défaut tout ce qui reste
 * créditable, au prix de la facture). Jamais plus que ce que la ligne porte, avoirs
 * précédents non refusés compris, en quantité comme en montant HT.
 *
 * `restores_quantity` : la quantité revient dans le reste à facturer de la ligne de
 * commande (retour, pièces refaites puis refacturées) ou non (geste commercial,
 * rebut non refait). Un avoir refusé reprend cette quantité ; rouvert, il la rend.
 */
class CreditNoteBuilder
{
    private const EPSILON = 0.0001;

    public function __construct(
        private DocumentCodeGenerator $codes,
        private AccountingEntryService $accounting,
        private DownPayments $downPayments,
    ) {}

    /**
     * Ce qui reste créditable sur une ligne de facture.
     *
     * @return array{qty: float, ht: float, unit_price: float}
     */
    public function creditable(InvoiceLines $line): array
    {
        $credited = CreditNoteLines::where('invoice_line_id', $line->id)
            ->whereHas('creditNote', fn ($q) => $q->whereIn('statu', config('receivables.deducted_credit_note_statuses', [1, 2])))
            ->get();

        $creditedHt = $credited->sum(fn (CreditNoteLines $l) => $this->ht((float) $l->qty, (float) $l->unit_price, $l->resolved_discount));
        $lineHt     = $this->ht((float) $line->qty, $line->resolved_unit_price, $line->resolved_discount);

        return [
            'qty'        => max(0.0, round((float) $line->qty - (float) $credited->sum('qty'), 3)),
            'ht'         => max(0.0, round($lineHt - $creditedHt, 2)),
            'unit_price' => $line->resolved_unit_price,
        ];
    }

    /**
     * Crée l'avoir. Sans quantité ni prix pour une ligne : tout ce qui reste créditable,
     * au prix de la facture (comportement d'avant pour un avoir complet).
     *
     * @param  array<int, float|string|null>  $quantities  id de ligne de facture => quantité
     * @param  array<int, float|string|null>  $prices      id de ligne de facture => prix unitaire HT
     *
     * @throws \DomainException
     */
    public function create(Collection $invoiceLines, array $quantities = [], array $prices = [], bool $restoreQuantities = true): CreditNotes
    {
        if ($invoiceLines->isEmpty()) {
            throw new \DomainException(__('credit_notes.refused.no_line'));
        }
        if ($invoiceLines->pluck('invoices_id')->unique()->count() > 1) {
            throw new \DomainException(__('credit_notes.refused.several_invoices'));
        }

        // Une déduction d'acompte (ligne négative) ne se reprend pas par avoir : l'avoir
        // porte sur les lignes vendues, la déduction reste acquise sur l'acompte.
        if ($invoiceLines->contains(fn (InvoiceLines $line) => $line->down_payment_line_id !== null)) {
            throw new \DomainException(__('down_payments.refused.credit_note_on_deduction'));
        }

        // Un acompte déjà déduit d'une facture ne se rembourse plus par avoir : le client
        // serait crédité deux fois (déduction + avoir). L'avoir se fait sur la facture finale.
        $deducted = $invoiceLines->first(fn (InvoiceLines $line) => (int) $line->invoice?->invoice_type === DownPayments::TYPE
            && $this->downPayments->deducted($line) > 0.004);
        if ($deducted) {
            throw new \DomainException(__('down_payments.refused.credit_note_on_deducted', ['code' => $deducted->invoice->code]));
        }

        return DB::transaction(function () use ($invoiceLines, $quantities, $prices, $restoreQuantities) {
            // Verrou de la facture : deux avoirs simultanés ne créditent pas deux fois la même ligne.
            $invoice = Invoices::whereKey($invoiceLines->first()->invoices_id)->lockForUpdate()->firstOrFail();

            $plan = $invoiceLines->map(fn (InvoiceLines $line) => $this->planLine($line, $quantities[$line->id] ?? null, $prices[$line->id] ?? null));

            $code       = $this->codes->generateDocumentCode('credit-note', CreditNotes::orderBy('id', 'desc')->value('id') ?? 0);
            $creditNote = CreditNotes::create([
                'code'                   => $code,
                'label'                  => $code,
                'invoices_id'            => $invoice->id,
                'companies_id'           => $invoice->companies_id,
                'companies_contacts_id'  => $invoice->companies_contacts_id,
                'companies_addresses_id' => $invoice->companies_addresses_id,
                'statu'                  => 1,
                'user_id'                => Auth::id(),
                'reason'                 => '',
                'validated_by'           => null,
                'validated_at'           => null,
            ]);

            foreach ($plan as [$invoiceLine, $qty, $price]) {
                $creditNoteLine = new CreditNoteLines([
                    'credit_note_id'     => $creditNote->id,
                    'order_line_id'      => $invoiceLine->order_line_id,
                    'invoice_line_id'    => $invoiceLine->id,
                    'product_id'         => $invoiceLine->product_id ?? $invoiceLine->orderLine?->product_id,
                    // Snapshot descriptif : une ligne libre n'a pas de ligne de
                    // commande où retrouver ces informations.
                    'label'              => $invoiceLine->display_label,
                    'ind'                => $invoiceLine->display_ind,
                    'qty'                => $qty,
                    'unit_price'         => $price,
                    'discount'           => $invoiceLine->resolved_discount,
                    'vat_rate'           => $invoiceLine->resolved_vat_rate,
                    'accounting_vats_id' => $invoiceLine->resolved_vat_id,
                ]);
                $creditNoteLine->restores_quantity = $restoreQuantities && $invoiceLine->order_line_id !== null;
                $creditNoteLine->save();

                // Écriture d'avoir (montants inversés) : sans elle, l'avoir n'apparaissait pas au FEC.
                $this->accounting->createCreditNoteEntry($creditNoteLine);

                if ($creditNoteLine->restores_quantity) {
                    $this->moveQuantity($creditNoteLine, +1);
                }
            }

            return $creditNote;
        });
    }

    /**
     * Statut d'un avoir changé : refusé (3), il reprend les quantités qu'il avait rendues
     * au reste à facturer ; rouvert, il les rend de nouveau.
     *
     * @throws \DomainException quantités déjà refacturées entre-temps
     */
    public function syncQuantities(CreditNotes $creditNote, int $from, int $to): void
    {
        $wasRefused = $from === 3;
        $isRefused  = $to === 3;
        if ($wasRefused === $isRefused) {
            return;
        }

        $lines = $creditNote->creditNotelines()->where('restores_quantity', true)->whereNotNull('order_line_id')->get();

        foreach ($lines as $line) {
            $this->moveQuantity($line, $isRefused ? -1 : +1);
        }
    }

    /** @return array{0: InvoiceLines, 1: float, 2: float} */
    private function planLine(InvoiceLines $line, $qty, $price): array
    {
        $left  = $this->creditable($line);
        $label = $line->display_code ?: $line->display_label;

        if ($left['qty'] <= self::EPSILON || $left['ht'] <= 0.004) {
            throw new \DomainException(__('credit_notes.refused.fully_credited', ['line' => $label]));
        }

        $qty   = ($qty === null || $qty === '') ? $left['qty'] : round((float) $qty, 3);
        $price = ($price === null || $price === '') ? $left['unit_price'] : round((float) $price, 4);

        if ($qty <= 0 || $qty > $left['qty'] + self::EPSILON) {
            throw new \DomainException(__('credit_notes.refused.qty', ['line' => $label, 'max' => format_qty($left['qty'])]));
        }
        if ($price < 0 || $price > $left['unit_price'] + self::EPSILON) {
            throw new \DomainException(__('credit_notes.refused.price', ['line' => $label, 'max' => number_format($left['unit_price'], 2, ',', ' ')]));
        }
        if ($this->ht($qty, $price, $line->resolved_discount) > $left['ht'] + 0.004) {
            throw new \DomainException(__('credit_notes.refused.amount', ['line' => $label, 'max' => number_format($left['ht'], 2, ',', ' ')]));
        }

        return [$line, $qty, $price];
    }

    /** Rend (+1) ou reprend (−1) la quantité d'une ligne d'avoir au reste à facturer. */
    private function moveQuantity(CreditNoteLines $line, int $direction): void
    {
        $orderLine = OrderLines::lockForUpdate()->find($line->order_line_id);
        if (! $orderLine) {
            return;
        }

        $qty = (float) $line->qty;

        if ($direction > 0) {
            $orderLine->invoiced_qty            = max(0, $orderLine->invoiced_qty - $qty);
            $orderLine->invoiced_remaining_qty += $qty;
        } else {
            // Refacturée depuis : la reprendre facturerait la commande au-delà de sa quantité.
            if ((float) $orderLine->invoiced_remaining_qty + self::EPSILON < $qty) {
                throw new \DomainException(__('credit_notes.refused.already_reinvoiced', [
                    'line' => $orderLine->code ?: $orderLine->label,
                ]));
            }
            $orderLine->invoiced_qty           += $qty;
            $orderLine->invoiced_remaining_qty -= $qty;
        }

        if ($orderLine->invoiced_qty <= 0) {
            $orderLine->invoice_status = 1;
        } elseif ($orderLine->invoiced_remaining_qty > 0) {
            $orderLine->invoice_status = 2;
        } else {
            $orderLine->invoice_status = 3;
        }

        $orderLine->save();
    }

    private function ht(float $qty, float $price, float $discount): float
    {
        return round($qty * $price * (1 - $discount / 100), 2);
    }
}
