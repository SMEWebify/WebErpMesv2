<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Integrations\PdpIncomingInvoice;
use App\Services\Integrations\Pdp\PdpIncomingInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Boîte de réception des factures fournisseurs électroniques (Factur-X).
 * Dépôt manuel d'un document ou consultation de l'inbox alimentée par une PDP,
 * puis conversion en facture d'achat.
 */
class IncomingInvoiceController extends Controller
{
    public function __construct(private PdpIncomingInvoiceService $service) {}

    public function index()
    {
        return view('purchases/incoming-invoices');
    }

    public function listJson(Request $request)
    {
        $query = PdpIncomingInvoice::query()
            ->with(['supplier:id,code,label'])
            ->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $rows = $query->paginate(20);

        return response()->json([
            'data' => $rows->map(fn (PdpIncomingInvoice $r) => [
                'id'              => $r->id,
                'provider'        => $r->provider,
                'seller_name'     => $r->seller_name,
                'seller_vat'      => $r->seller_vat,
                'invoice_number'  => $r->invoice_number,
                'issue_date'      => optional($r->issue_date)->format('d/m/Y'),
                'total_ttc'       => $r->total_ttc,
                'currency'        => $r->currency,
                'status'          => $r->status,
                'supplier'        => $r->supplier ? ['id' => $r->supplier->id, 'label' => $r->supplier->label] : null,
                'purchase_invoice_url' => $r->purchase_invoice_id
                    ? route('purchase.invoices.show', ['id' => $r->purchase_invoice_id])
                    : null,
                'convert_url'     => $r->status === PdpIncomingInvoice::STATUS_RECEIVED
                    ? route('purchases.incoming.convert', $r->id) : null,
                'reject_url'      => in_array($r->status, [PdpIncomingInvoice::STATUS_RECEIVED, PdpIncomingInvoice::STATUS_SUPPLIER_UNMATCHED])
                    ? route('purchases.incoming.reject', $r->id) : null,
            ]),
            'meta' => [
                'total'        => $rows->total(),
                'current_page' => $rows->currentPage(),
                'last_page'    => $rows->lastPage(),
            ],
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'document' => 'required|file|max:10240|mimetypes:application/pdf,text/xml,application/xml,text/plain',
        ]);

        try {
            $content  = file_get_contents($request->file('document')->getRealPath());
            $incoming = $this->service->ingest($content, 'manual');
        } catch (\Throwable $e) {
            Log::error('IncomingInvoice: ingestion failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => "Document illisible ou non conforme Factur-X : {$e->getMessage()}"], 422);
        }

        $msg = $incoming->status === PdpIncomingInvoice::STATUS_SUPPLIER_UNMATCHED
            ? "Facture reçue, mais fournisseur ({$incoming->seller_name}) non rapproché."
            : "Facture {$incoming->invoice_number} reçue de {$incoming->seller_name}.";

        return response()->json(['message' => $msg, 'status' => $incoming->status]);
    }

    public function convert(PdpIncomingInvoice $incoming)
    {
        try {
            $invoice = $this->service->convertToPurchaseInvoice($incoming, auth()->id());
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'  => "Facture d'achat {$invoice->code} créée. Rapprochez les lignes avec les réceptions.",
            'redirect' => route('purchase.invoices.show', ['id' => $invoice->id]),
        ]);
    }

    public function reject(PdpIncomingInvoice $incoming)
    {
        $incoming->update(['status' => PdpIncomingInvoice::STATUS_REJECTED]);

        return response()->json(['message' => 'Facture entrante refusée.']);
    }
}
