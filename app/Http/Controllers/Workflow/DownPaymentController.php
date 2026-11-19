<?php

namespace App\Http\Controllers\Workflow;

use App\Http\Controllers\Controller;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Orders;
use App\Services\AccountingPeriodService;
use App\Services\Invoicing\DownPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Factures d'acompte : création depuis la fiche commande, recalcul de la
 * déduction sur une facture en brouillon. Règles dans App\Services\Invoicing\DownPayments.
 */
class DownPaymentController extends Controller
{
    public function __construct(private DownPayments $downPayments) {}

    public function store(Request $request, int $orderId)
    {
        $order = Orders::findOrFail($orderId);

        $data = $request->validate([
            'mode'  => 'required|in:percent,amount',
            'value' => 'required|numeric|gt:0',
            'label' => 'nullable|string|max:255',
        ]);

        $amount = $data['mode'] === 'percent'
            ? round($this->downPayments->orderTotal($order) * min(100, (float) $data['value']) / 100, 2)
            : round((float) $data['value'], 2);

        try {
            $invoice = $this->downPayments->create($order, $amount, Auth::id(), $data['label'] ?? null);
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', ['id' => $invoice->id])
            ->with('success', __('down_payments.created', ['code' => $invoice->code]));
    }

    public function recompute(int $id)
    {
        $invoice = Invoices::findOrFail($id);

        if (app(AccountingPeriodService::class)->isLocked($invoice->created_at)) {
            return redirect()->back()->with('error', "Période {$invoice->created_at->format('m/Y')} verrouillée — cette facture ne peut plus être modifiée.");
        }

        try {
            $this->downPayments->recompute($invoice);
        } catch (\DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', ['id' => $invoice->id])
            ->with('success', __('down_payments.invoice.recomputed'));
    }
}
