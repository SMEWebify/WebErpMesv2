<?php

namespace App\Services\Integrations;

use App\Models\Integrations\QontoConnection;
use App\Models\Integrations\QontoInvoiceMapping;
use App\Models\Workflow\Invoices;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gère le dépôt des factures vers Qonto et la mise à jour des statuts
 * en conformité avec la réforme de facturation électronique.
 *
 * Cycle de vie Qonto → WEM :
 *   pending          → en attente de traitement (non encore soumis)
 *   submitted        → déposé chez Qonto, en attente d'accusé
 *   acknowledged     → accusé réception Qonto (facture prise en compte)
 *   rejected         → rejeté par Qonto (format / données invalides)
 *   refused          → refusé par le destinataire
 *   accepted         → accepté par le destinataire
 *   paid             → marquée payée côté Qonto
 */
class QontoInvoiceSyncService
{
    // Statuts lifecycle Qonto
    public const LIFECYCLE_PENDING      = 'pending';
    public const LIFECYCLE_SUBMITTED    = 'submitted';
    public const LIFECYCLE_ACKNOWLEDGED = 'acknowledged';
    public const LIFECYCLE_REJECTED     = 'rejected';
    public const LIFECYCLE_REFUSED      = 'refused';
    public const LIFECYCLE_ACCEPTED     = 'accepted';
    public const LIFECYCLE_PAID         = 'paid';

    // Mapping lifecycle Qonto → statu WEM (invoices.statu)
    public const LIFECYCLE_TO_WEM_STATUS = [
        self::LIFECYCLE_PENDING      => null,  // pas de changement
        self::LIFECYCLE_SUBMITTED    => 2,     // Envoyée
        self::LIFECYCLE_ACKNOWLEDGED => 2,     // Envoyée
        self::LIFECYCLE_REJECTED     => 1,     // Retour en brouillon
        self::LIFECYCLE_REFUSED      => 4,     // Impayée
        self::LIFECYCLE_ACCEPTED     => 3,     // En attente de paiement
        self::LIFECYCLE_PAID         => 5,     // Payée
    ];

    public function __construct(private QontoConnectionService $connectionService) {}

    /**
     * Soumet une facture vers l'API Qonto (format Factur-X / PDF-A3).
     * Retourne le mapping mis à jour.
     *
     * @throws \Throwable en cas d'erreur API
     */
    public function submit(Invoices $invoice): QontoInvoiceMapping
    {
        $tenantId  = $invoice->user_id;
        $token     = $this->connectionService->getValidToken($tenantId);
        $baseUrl   = rtrim(config('services.qonto.api_base_url', 'https://thirdparty.qonto.com/v2'), '/');

        // Génération du PDF Factur-X en mémoire
        $pdfContent = $this->generateFactureXContent($invoice);

        $response = Http::withToken($token)
            ->attach('file', $pdfContent, $invoice->code . '.pdf', ['Content-Type' => 'application/pdf'])
            ->post("{$baseUrl}/invoices", [
                'invoice_number'   => $invoice->code,
                'issue_date'       => $invoice->created_at->format('Y-m-d'),
                'due_date'         => $invoice->due_date,
                'currency'         => 'EUR',
                'client_id'        => $this->resolveQontoClientId($tenantId, $invoice->companies_id),
            ])
            ->throw()
            ->json();

        $qontoInvoiceId = $response['invoice']['id'] ?? $response['id'] ?? null;

        $mapping = QontoInvoiceMapping::updateOrCreate(
            ['tenant_id' => $tenantId, 'invoice_id' => $invoice->id],
            [
                'qonto_invoice_id' => $qontoInvoiceId,
                'lifecycle_status' => self::LIFECYCLE_SUBMITTED,
                'submitted_at'     => now(),
                'rejection_reason' => null,
            ]
        );

        Log::info('QontoInvoice: submitted', [
            'invoice_id'       => $invoice->id,
            'qonto_invoice_id' => $qontoInvoiceId,
        ]);

        return $mapping;
    }

    /**
     * Récupère le statut d'une facture déposée et met à jour WEM.
     */
    public function poll(QontoInvoiceMapping $mapping): QontoInvoiceMapping
    {
        if (! $mapping->qonto_invoice_id) {
            return $mapping;
        }

        $token   = $this->connectionService->getValidToken($mapping->tenant_id);
        $baseUrl = rtrim(config('services.qonto.api_base_url', 'https://thirdparty.qonto.com/v2'), '/');

        $response = Http::withToken($token)
            ->get("{$baseUrl}/invoices/{$mapping->qonto_invoice_id}")
            ->throw()
            ->json();

        $lifecycleStatus = $response['invoice']['status'] ?? $response['status'] ?? null;

        if ($lifecycleStatus && $lifecycleStatus !== $mapping->lifecycle_status) {
            $this->applyLifecycle($mapping, $lifecycleStatus, $response);
        }

        return $mapping->fresh();
    }

    /**
     * Applique un changement de statut lifecycle reçu par webhook ou polling.
     */
    public function applyLifecycle(QontoInvoiceMapping $mapping, string $lifecycleStatus, array $payload = []): void
    {
        $updates = ['lifecycle_status' => $lifecycleStatus];

        if ($lifecycleStatus === self::LIFECYCLE_ACKNOWLEDGED) {
            $updates['acknowledged_at'] = now();
        }

        $rejectionReason = $payload['invoice']['rejection_reason']
            ?? $payload['rejection_reason']
            ?? null;

        if ($rejectionReason) {
            $updates['rejection_reason'] = $rejectionReason;
        }

        $mapping->update($updates);

        // Mise à jour du statut WEM si mapping défini
        $wemStatus = self::LIFECYCLE_TO_WEM_STATUS[$lifecycleStatus] ?? null;
        if ($wemStatus !== null) {
            $invoice = $mapping->invoice;
            if ($invoice && $invoice->statu !== $wemStatus) {
                $invoice->update(['statu' => $wemStatus]);
                $invoice->invoiceLines()->update(['invoice_status' => $wemStatus]);

                Log::info('QontoInvoice: WEM status updated', [
                    'invoice_id'       => $invoice->id,
                    'lifecycle_status' => $lifecycleStatus,
                    'wem_status'       => $wemStatus,
                ]);
            }
        }
    }

    /**
     * Génère le contenu binaire du PDF Factur-X pour une facture.
     * Réutilise la logique de PrintController::getInvoiceFactureX mais retourne le contenu.
     */
    private function generateFactureXContent(Invoices $invoice): string
    {
        // On appelle le contrôleur Print en interne pour réutiliser la logique Zugferd
        $controller = app(\App\Http\Controllers\PrintController::class);
        $response   = $controller->getInvoiceFactureX($invoice);

        // getInvoiceFactureX sauvegarde le fichier et retourne response()->file(...)
        // On lit le fichier généré
        $path = public_path('pdf/invoices/' . __('general_content.invoice_trans_key') . '-' . $invoice->id . '.pdf');

        if (! file_exists($path)) {
            throw new \RuntimeException("Factur-X PDF not found at {$path} for invoice #{$invoice->id}");
        }

        return file_get_contents($path);
    }

    /**
     * Résout l'ID Qonto du client WEM correspondant à une entreprise.
     */
    private function resolveQontoClientId(int $tenantId, int $companyId): ?string
    {
        return \App\Models\Integrations\QontoClientMapping::where('tenant_id', $tenantId)
            ->where('wem_client_id', $companyId)
            ->whereNotNull('qonto_client_id')
            ->value('qonto_client_id');
    }
}
