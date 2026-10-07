<?php

namespace App\Services\Affairs;

use App\Models\EmailLog;
use App\Models\GuestVisits;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Models\Purchases\Purchases;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\InvoicePayment;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\OpportunitiesEventsLogs;
use App\Models\Workflow\OpportunityVisits;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use Carbon\Carbon;

/**
 * Historique unifié d'une affaire : tout ce qui s'est passé sur l'opportunité
 * et sur les documents qui en descendent, du plus récent au plus ancien.
 *
 * Chaque entrée porte la section dont elle relève : une section masquée
 * (permission de menu) ne produit aucune entrée, et sa requête n'est pas jouée.
 * Les libellés de type sont traduits côté front ; l'entrée ne transporte que
 * des données.
 *
 * Forme d'une entrée : {type, section, date, ref, label, statu, amount, url}
 */
class AffairTimelineService
{
    public const SECTIONS = ['quotes', 'orders', 'purchases', 'deliveries', 'invoices'];

    /**
     * @param string[]|null $visibleSections sections autorisées (null = toutes)
     */
    public function timeline(Opportunities $opportunity, ?array $visibleSections = null): array
    {
        $visible = array_fill_keys($visibleSections ?? self::SECTIONS, true);
        $scope   = new AffairScope($opportunity);
        $items   = [];

        $items[] = $this->item('opportunity', 'opportunity', $opportunity->created_at, null, $opportunity->label, (int) $opportunity->statu, null,
            route('opportunities.show', $opportunity->id));

        foreach (OpportunitiesEventsLogs::query()->where('opportunities_id', $opportunity->id)->toBase()
                     ->get(['id', 'label', 'type', 'start_date', 'created_at']) as $event) {
            $items[] = $this->item('event', 'opportunity', $event->start_date ?? $event->created_at, null, $event->label, (int) $event->type, null,
                route('opportunities.show', $opportunity->id) . '#Events');
        }

        foreach (OpportunityVisits::query()->where('opportunities_id', $opportunity->id)->toBase()
                     ->get(['id', 'statu', 'visited_at']) as $visit) {
            $items[] = $this->item('visit', 'opportunity', $visit->visited_at, null, null, (int) $visit->statu, null,
                route('opportunities.visit', ['id' => $opportunity->id, 'visit' => $visit->id]));
        }

        $emailTargets = [];

        if (isset($visible['quotes']) && $scope->quoteIds() !== []) {
            $quotes = Quotes::query()->whereIn('id', $scope->quoteIds())->toBase()
                ->get(['id', 'code', 'label', 'statu', 'created_at'])->keyBy('id');
            foreach ($quotes as $quote) {
                $items[] = $this->item('quote', 'quotes', $quote->created_at, $quote->code, $quote->label, (int) $quote->statu, null,
                    route('quotes.show', $quote->id));
            }

            // Consultation du lien public par le client : une entrée par devis et par jour.
            $views = GuestVisits::query()
                ->whereIn('quotes_id', $scope->quoteIds())
                ->selectRaw('quotes_id, DATE(visited_at) as day, MIN(visited_at) as first_at, COUNT(*) as hits')
                ->groupBy('quotes_id', 'day')
                ->toBase()->get();
            foreach ($views as $view) {
                $quote = $quotes->get($view->quotes_id);
                $items[] = $this->item('quote_viewed', 'quotes', $view->first_at, $quote->code ?? null, $quote->label ?? null, null, (int) $view->hits,
                    route('quotes.show', $view->quotes_id));
            }

            $emailTargets[Quotes::class] = ['quotes', $scope->quoteIds(), $quotes->map->code];
        }

        if (isset($visible['orders']) && $scope->orderIds() !== []) {
            $orders = Orders::query()->whereIn('id', $scope->orderIds())->toBase()
                ->get(['id', 'code', 'label', 'statu', 'created_at'])->keyBy('id');
            foreach ($orders as $order) {
                $items[] = $this->item('order', 'orders', $order->created_at, $order->code, $order->label, (int) $order->statu, null,
                    route('orders.show', $order->id));
            }
            $emailTargets[Orders::class] = ['orders', $scope->orderIds(), $orders->map->code];
        }

        if (isset($visible['purchases']) && $scope->purchaseLineIds() !== []) {
            $purchaseIds = PurchaseLines::query()->whereIn('id', $scope->purchaseLineIds())->distinct()->pluck('purchases_id')->all();
            $purchases = Purchases::query()->whereIn('id', $purchaseIds)->toBase()
                ->get(['id', 'code', 'label', 'statu', 'created_at'])->keyBy('id');
            foreach ($purchases as $purchase) {
                $items[] = $this->item('purchase', 'purchases', $purchase->created_at, $purchase->code, $purchase->label, (int) $purchase->statu, null,
                    route('purchases.show', $purchase->id));
            }

            // Une entrée par bon de réception, même s'il couvre plusieurs lignes de l'affaire.
            $receipts = PurchaseReceiptLines::query()
                ->join('purchase_receipts', 'purchase_receipts.id', '=', 'purchase_receipt_lines.purchase_receipt_id')
                ->whereNull('purchase_receipts.deleted_at')
                ->whereIn('purchase_receipt_lines.purchase_line_id', $scope->purchaseLineIds())
                ->selectRaw('purchase_receipts.id, purchase_receipts.code, purchase_receipts.label, MIN(purchase_receipt_lines.created_at) as received_at, COUNT(*) as line_count')
                ->groupBy('purchase_receipts.id', 'purchase_receipts.code', 'purchase_receipts.label')
                ->toBase()->get();
            foreach ($receipts as $receipt) {
                $items[] = $this->item('receipt', 'purchases', $receipt->received_at, $receipt->code, $receipt->label, null, (int) $receipt->line_count,
                    route('purchase.receipts.show', $receipt->id));
            }
            $emailTargets[Purchases::class] = ['purchases', $purchaseIds, $purchases->map->code];
        }

        if (isset($visible['deliveries']) && $scope->orderIds() !== []) {
            $lineIds = $scope->orderLineIds();
            $deliveries = Deliverys::query()
                ->where(function ($q) use ($scope, $lineIds) {
                    $q->whereIn('order_id', $scope->orderIds());
                    if ($lineIds !== []) {
                        $q->orWhereIn('id', DeliveryLines::query()->select('deliverys_id')->whereIn('order_line_id', $lineIds));
                    }
                })
                ->toBase()->get(['id', 'code', 'label', 'statu', 'created_at'])->keyBy('id');
            foreach ($deliveries as $delivery) {
                $items[] = $this->item('delivery', 'deliveries', $delivery->created_at, $delivery->code, $delivery->label, (int) $delivery->statu, null,
                    route('deliverys.show', $delivery->id));
            }
            $emailTargets[Deliverys::class] = ['deliveries', $deliveries->keys()->all(), $deliveries->map->code];
        }

        if (isset($visible['invoices']) && $scope->invoiceIds() !== []) {
            $invoices = Invoices::query()->whereIn('id', $scope->invoiceIds())->toBase()
                ->get(['id', 'code', 'label', 'statu', 'invoice_type', 'created_at'])->keyBy('id');
            foreach ($invoices as $invoice) {
                $items[] = $this->item('invoice', 'invoices', $invoice->created_at, $invoice->code, $invoice->label, (int) $invoice->statu, null,
                    route('invoices.show', $invoice->id));
            }

            foreach (InvoicePayment::query()->whereIn('invoice_id', $scope->invoiceIds())->toBase()
                         ->get(['id', 'invoice_id', 'amount', 'payment_date', 'reference']) as $payment) {
                $items[] = $this->item('payment', 'invoices', $payment->payment_date, $invoices->get($payment->invoice_id)->code ?? null,
                    $payment->reference, null, round((float) $payment->amount, 2), route('invoices.show', $payment->invoice_id));
            }
            $emailTargets[Invoices::class] = ['invoices', $scope->invoiceIds(), $invoices->map->code];
        }

        $emailTargets = array_filter($emailTargets, fn ($target) => $target[1] !== []);
        if ($emailTargets !== []) {
            $emails = EmailLog::query()
                ->where('status', '!=', 'failed')
                ->where(function ($q) use ($emailTargets) {
                    foreach ($emailTargets as $class => [, $ids]) {
                        $q->orWhere(fn ($w) => $w->where('emailable_type', $class)->whereIn('emailable_id', $ids));
                    }
                })
                ->toBase()->get(['id', 'emailable_type', 'emailable_id', 'to', 'subject', 'sent_at', 'created_at']);
            foreach ($emails as $email) {
                [$section, , $codes] = $emailTargets[$email->emailable_type];
                $items[] = $this->item('email', $section, $email->sent_at ?? $email->created_at, $codes->get($email->emailable_id),
                    $email->subject, null, null, null) + ['to' => $email->to];
            }
        }

        usort($items, fn ($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

        return $items;
    }

    private function item(string $type, string $section, $date, ?string $ref, ?string $label, ?int $statu, int|float|null $amount, ?string $url): array
    {
        return [
            'type'    => $type,
            'section' => $section,
            'date'    => $date ? Carbon::parse($date)->format('Y-m-d H:i') : null,
            'ref'     => $ref,
            'label'   => $label,
            'statu'   => $statu,
            'amount'  => $amount,
            'url'     => $url,
        ];
    }
}
