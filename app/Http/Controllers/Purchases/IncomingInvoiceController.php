<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Integrations\PdpIncomingInvoice;
use App\Services\Integrations\Pdp\Contracts\PdpDirectoryGateway;
use App\Services\Integrations\Pdp\Enums\PdpOutgoingStatus;
use App\Services\Integrations\Pdp\PdpManager;
use App\Services\Integrations\Pdp\PdpIncomingInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
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
                // Rapprochement : l'écran « en attente de facturation », filtré
                // sur le fournisseur et nourri du document reçu. C'est la voie
                // normale — les lignes facturées doivent correspondre à des
                // réceptions, pas être recopiées depuis le document du vendeur.
                'reconcile_url'   => $r->status === PdpIncomingInvoice::STATUS_RECEIVED && $r->supplier_company_id
                    ? route('purchases.wainting.invoice', ['companies_id' => $r->supplier_company_id, 'incoming_id' => $r->id])
                    : null,
                // Repli pour les factures sans commande ni réception (frais,
                // abonnements) : crée l'en-tête seul, à compléter à la main.
                'convert_url'     => $r->status === PdpIncomingInvoice::STATUS_RECEIVED
                    ? route('purchases.incoming.convert', $r->id) : null,
                // Déclaration de statut : seulement pour les documents venus
                // d'une plateforme, un dépôt manuel n'ayant aucun destinataire.
                'status_url'      => $this->service->canReportStatus($r)
                    ? route('purchases.incoming.status', $r->id) : null,
                'reject_url'      => in_array($r->status, [PdpIncomingInvoice::STATUS_RECEIVED, PdpIncomingInvoice::STATUS_SUPPLIER_UNMATCHED])
                    ? route('purchases.incoming.reject', $r->id) : null,
            ]),
            'meta' => [
                'total'        => $rows->total(),
                'current_page' => $rows->currentPage(),
                'last_page'    => $rows->lastPage(),
            ],
            'directory' => $this->directoryStatus(),
        ]);
    }

    /**
     * L'entreprise est-elle joignable ?
     *
     * Sans ligne d'annuaire ouverte, aucun fournisseur ne peut lui adresser de
     * facture — et rien ne le signale : la boîte de réception reste vide,
     * exactement comme si personne n'avait encore facturé. Ce contrôle lève
     * cette ambiguïté, qui est le pire des deux mondes à l'approche de
     * l'obligation de réception.
     *
     * L'ouverture, elle, se fait dans l'interface de la plateforme : c'est
     * l'identité du client vis-à-vis de sa PDP, WEM n'a pas à la dupliquer.
     *
     * Mise en cache : la réponse ne change qu'au rythme d'une démarche
     * administrative, inutile d'interroger la plateforme à chaque affichage.
     */
    private function directoryStatus(): ?array
    {
        $gateway = app(PdpManager::class)->driver();

        if (! $gateway->isEnabled() || ! $gateway instanceof PdpDirectoryGateway) {
            return null;
        }

        return Cache::remember("pdp:directory:{$gateway->key()}", now()->addHour(), function () use ($gateway) {
            try {
                // Les lignes `_replyto` sont techniques : elles reçoivent les
                // messages de cycle de vie, jamais de factures. Les compter
                // laisserait croire à tort que l'entreprise est joignable.
                $entries = array_filter($gateway->listEntries(), fn (array $e) => ! $e['is_replyto']);

                return ['reachable' => $entries !== [], 'count' => count($entries)];
            } catch (\Throwable $e) {
                Log::warning('IncomingInvoice: directory check failed', ['error' => $e->getMessage()]);

                return null;
            }
        });
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

    /**
     * Déclare un statut au fournisseur via la plateforme.
     *
     * Obligation de l'acheteur dans la réforme : prise en charge, approbation,
     * refus et transmission du paiement doivent remonter au fournisseur — et à
     * l'administration, qui en déduit l'exigibilité de la TVA sur les services.
     */
    public function reportStatus(Request $request, PdpIncomingInvoice $incoming)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_column(PdpOutgoingStatus::cases(), 'value'))],
            // Code motif AFNOR (MDT-113) ; la liste figure dans XP Z12-012.
            'reason' => 'nullable|string|max:20',
            'note'   => 'nullable|string|max:900',
        ]);

        $status = PdpOutgoingStatus::from($validated['status']);

        if ($status->requiresReason() && blank($validated['note'] ?? null) && blank($validated['reason'] ?? null)) {
            return response()->json([
                'message' => "Un motif est obligatoire pour déclarer « {$status->label()} » : "
                    . 'sans lui, le fournisseur ne peut pas corriger sa facture.',
            ], 422);
        }

        try {
            $this->service->reportStatus(
                $incoming,
                $status,
                $validated['reason'] ?? null,
                $validated['note'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Statut « {$status->label()} » déclaré au fournisseur.",
        ]);
    }
}
