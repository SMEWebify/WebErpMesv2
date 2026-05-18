<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\PreOrder;

class RelationalBreadcrumb extends Component
{
    public array $ancestors   = [];
    public array $current     = [];
    public array $descendants = [];

    public function __construct(mixed $entity)
    {
        $type = class_basename($entity);

        match ($type) {
            'Leads'         => $this->buildForLead($entity),
            'Opportunities' => $this->buildForOpportunity($entity),
            'Quotes'        => $this->buildForQuote($entity),
            'Orders'        => $this->buildForOrder($entity),
            'Deliverys'     => $this->buildForDelivery($entity),
            'Invoices'      => $this->buildForInvoice($entity),
            'CreditNotes'   => $this->buildForCreditNote($entity),
            default         => null,
        };
    }

    // ── Node builders ────────────────────────────────────────────────────────

    private function leadNode($lead): array
    {
        return [
            'label' => 'Lead #' . $lead->id,
            'url'   => route('leads.show', ['id' => $lead->id]),
            'color' => 'secondary',
            'icon'  => 'fas fa-seedling',
        ];
    }

    private function opportunityNode($opp): array
    {
        return [
            'label' => $opp->label,
            'url'   => route('opportunities.show', ['id' => $opp->id]),
            'color' => 'info',
            'icon'  => 'fas fa-star',
        ];
    }

    private function quoteNode($quote): array
    {
        return [
            'label' => $quote->code,
            'url'   => route('quotes.show', ['id' => $quote->id]),
            'color' => 'primary',
            'icon'  => 'fas fa-file-alt',
        ];
    }

    private function orderNode($order): array
    {
        return [
            'label' => $order->code,
            'url'   => route('orders.show', ['id' => $order->id]),
            'color' => 'success',
            'icon'  => 'fas fa-shopping-cart',
        ];
    }

    private function deliveryNode($delivery): array
    {
        return [
            'label' => $delivery->code,
            'url'   => route('deliverys.show', ['id' => $delivery->id]),
            'color' => 'warning',
            'icon'  => 'fas fa-truck',
        ];
    }

    private function invoiceNode($invoice): array
    {
        return [
            'label' => $invoice->code,
            'url'   => route('invoices.show', ['id' => $invoice->id]),
            'color' => 'danger',
            'icon'  => 'fas fa-receipt',
        ];
    }

    private function preOrderNode($preOrder): array
    {
        return [
            'label' => 'Pré-cmd #' . $preOrder->id,
            'url'   => route('pre-orders.show', $preOrder),
            'color' => 'info',
            'icon'  => 'fas fa-robot',
        ];
    }

    private function creditNoteNode($creditNote): array
    {
        return [
            'label' => $creditNote->code,
            'url'   => route('credit.notes.show', ['id' => $creditNote->id]),
            'color' => 'dark',
            'icon'  => 'fas fa-file-minus',
        ];
    }

    // ── Chain builders ───────────────────────────────────────────────────────

    private function buildForLead($lead): void
    {
        $this->current = [
            'label' => 'Lead #' . $lead->id,
            'color' => 'secondary',
            'icon'  => 'fas fa-seedling',
        ];

        foreach ($lead->Opportunity as $opp) {
            $this->descendants[] = $this->opportunityNode($opp);
        }
    }

    private function buildForOpportunity($opp): void
    {
        $this->current = [
            'label' => $opp->label,
            'color' => 'info',
            'icon'  => 'fas fa-star',
        ];

        if ($opp->leads_id && $opp->lead) {
            $this->ancestors[] = $this->leadNode($opp->lead);
        }

        foreach ($opp->quotes as $quote) {
            $this->descendants[] = $this->quoteNode($quote);
        }
    }

    private function buildForQuote($quote): void
    {
        $this->current = [
            'label' => $quote->code,
            'color' => 'primary',
            'icon'  => 'fas fa-file-alt',
        ];

        if ($quote->opportunities_id && $quote->opportunities) {
            $opp = $quote->opportunities;
            if ($opp->leads_id && $opp->lead) {
                $this->ancestors[] = $this->leadNode($opp->lead);
            }
            $this->ancestors[] = $this->opportunityNode($opp);
        }

        foreach ($quote->Orders as $order) {
            $this->descendants[] = $this->orderNode($order);
        }
    }

    private function buildForOrder($order): void
    {
        $this->current = [
            'label' => $order->code,
            'color' => 'success',
            'icon'  => 'fas fa-shopping-cart',
        ];

        $preOrder = PreOrder::where('converted_order_id', $order->id)->first();
        if ($preOrder) {
            $this->ancestors[] = $this->preOrderNode($preOrder);
        }

        if ($order->quotes_id && $order->Quote) {
            $quote = $order->Quote;
            if ($quote->opportunities_id && $quote->opportunities) {
                $opp = $quote->opportunities;
                if ($opp->leads_id && $opp->lead) {
                    $this->ancestors[] = $this->leadNode($opp->lead);
                }
                $this->ancestors[] = $this->opportunityNode($opp);
            }
            $this->ancestors[] = $this->quoteNode($quote);
        }

        // order_id n'est pas renseigné sur deliverys/invoices → on passe par les lignes
        $orderLineIds = $order->OrderLines->pluck('id');

        $deliveryIds = DeliveryLines::whereIn('order_line_id', $orderLineIds)
            ->pluck('deliverys_id')->unique()->filter();
        foreach (Deliverys::whereIn('id', $deliveryIds)->get() as $delivery) {
            $this->descendants[] = $this->deliveryNode($delivery);
        }

        $invoiceIds = InvoiceLines::whereIn('order_line_id', $orderLineIds)
            ->pluck('invoices_id')->unique()->filter();
        foreach (Invoices::whereIn('id', $invoiceIds)->get() as $invoice) {
            $this->descendants[] = $this->invoiceNode($invoice);
        }
    }

    private function buildForDelivery($delivery): void
    {
        $this->current = [
            'label' => $delivery->code,
            'color' => 'warning',
            'icon'  => 'fas fa-truck',
        ];

        // order_id non renseigné → on remonte via DeliveryLines → OrderLine → Order
        $order = $delivery->DeliveryLines->first()?->OrderLine?->order;
        if ($order) {
            $preOrder = PreOrder::where('converted_order_id', $order->id)->first();
            if ($preOrder) {
                $this->ancestors[] = $this->preOrderNode($preOrder);
            }
            if ($order->quotes_id && $order->Quote) {
                $quote = $order->Quote;
                if ($quote->opportunities_id && $quote->opportunities) {
                    $opp = $quote->opportunities;
                    if ($opp->leads_id && $opp->lead) {
                        $this->ancestors[] = $this->leadNode($opp->lead);
                    }
                    $this->ancestors[] = $this->opportunityNode($opp);
                }
                $this->ancestors[] = $this->quoteNode($quote);
            }
            $this->ancestors[] = $this->orderNode($order);
        }
    }

    private function buildForInvoice($invoice): void
    {
        $this->current = [
            'label' => $invoice->code,
            'color' => 'danger',
            'icon'  => 'fas fa-receipt',
        ];

        // order_id non renseigné → on remonte via InvoiceLines → orderLine → order
        $order = $invoice->invoiceLines->first()?->orderLine?->order;
        if ($order) {
            $preOrder = PreOrder::where('converted_order_id', $order->id)->first();
            if ($preOrder) {
                $this->ancestors[] = $this->preOrderNode($preOrder);
            }
            if ($order->quotes_id && $order->Quote) {
                $quote = $order->Quote;
                if ($quote->opportunities_id && $quote->opportunities) {
                    $opp = $quote->opportunities;
                    if ($opp->leads_id && $opp->lead) {
                        $this->ancestors[] = $this->leadNode($opp->lead);
                    }
                    $this->ancestors[] = $this->opportunityNode($opp);
                }
                $this->ancestors[] = $this->quoteNode($quote);
            }
            $this->ancestors[] = $this->orderNode($order);
        }
    }

    private function buildForCreditNote($creditNote): void
    {
        $this->current = [
            'label' => $creditNote->code,
            'color' => 'dark',
            'icon'  => 'fas fa-file-minus',
        ];

        if ($creditNote->invoices_id && $creditNote->invoice) {
            $invoice = $creditNote->invoice;
            $order = $invoice->invoiceLines->first()?->orderLine?->order;
            if ($order) {
                $preOrder = PreOrder::where('converted_order_id', $order->id)->first();
                if ($preOrder) {
                    $this->ancestors[] = $this->preOrderNode($preOrder);
                }
                if ($order->quotes_id && $order->Quote) {
                    $quote = $order->Quote;
                    if ($quote->opportunities_id && $quote->opportunities) {
                        $opp = $quote->opportunities;
                        if ($opp->leads_id && $opp->lead) {
                            $this->ancestors[] = $this->leadNode($opp->lead);
                        }
                        $this->ancestors[] = $this->opportunityNode($opp);
                    }
                    $this->ancestors[] = $this->quoteNode($quote);
                }
                $this->ancestors[] = $this->orderNode($order);
            }
            $this->ancestors[] = $this->invoiceNode($invoice);
        }
    }

    public function render()
    {
        return view('components.relational-breadcrumb');
    }
}
