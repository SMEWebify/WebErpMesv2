<?php

namespace App\Services\Integrations\Pdp\Drivers;

use App\Http\Controllers\PrintController;
use App\Models\Integrations\QontoClientMapping;
use App\Models\Workflow\Invoices;
use App\Services\Integrations\Pdp\Contracts\PdpGateway;
use App\Services\Integrations\Pdp\Data\PdpInvoiceResult;
use App\Services\Integrations\Pdp\Data\PdpWebhookEvent;
use App\Services\Integrations\Pdp\Enums\PdpLifecycle;
use App\Services\Integrations\Pdp\Exceptions\PdpSignatureException;
use App\Services\Integrations\QontoConnectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Driver PDP pour Qonto.
 *
 * Encapsule l'I/O HTTP (OAuth via QontoConnectionService), le dépôt de la
 * facture Factur-X, l'interrogation de statut, et la vérification/traduction
 * des webhooks signés HMAC-SHA256.
 */
class QontoGateway implements PdpGateway
{
    public function __construct(private QontoConnectionService $connectionService) {}

    public function key(): string
    {
        return 'qonto';
    }

    public function isEnabled(): bool
    {
        return QontoConnectionService::isEnabled();
    }

    public function submit(Invoices $invoice): PdpInvoiceResult
    {
        $tenantId = $invoice->user_id;
        $token    = $this->connectionService->getValidToken($tenantId);

        $pdfContent = $this->generateFactureXContent($invoice);

        $response = Http::withToken($token)
            ->attach('file', $pdfContent, $invoice->code . '.pdf', ['Content-Type' => 'application/pdf'])
            ->post("{$this->baseUrl()}/invoices", [
                'invoice_number' => $invoice->code,
                'issue_date'     => $invoice->created_at->format('Y-m-d'),
                'due_date'       => $invoice->due_date,
                'currency'       => 'EUR',
                'client_id'      => $this->resolveClientId($tenantId, $invoice->companies_id),
            ])
            ->throw()
            ->json();

        $externalId = $response['invoice']['id'] ?? $response['id'] ?? null;

        Log::info('QontoGateway: submitted', ['invoice_id' => $invoice->id, 'external_id' => $externalId]);

        return new PdpInvoiceResult($externalId, PdpLifecycle::Submitted, null, $response ?? []);
    }

    public function poll(string $externalId, int $tenantId): PdpInvoiceResult
    {
        $token = $this->connectionService->getValidToken($tenantId);

        $response = Http::withToken($token)
            ->get("{$this->baseUrl()}/invoices/{$externalId}")
            ->throw()
            ->json();

        $status    = $response['invoice']['status'] ?? $response['status'] ?? null;
        $lifecycle = $this->mapProviderStatus($status) ?? PdpLifecycle::Submitted;
        $reason    = $response['invoice']['rejection_reason'] ?? $response['rejection_reason'] ?? null;

        return new PdpInvoiceResult($externalId, $lifecycle, $reason, $response ?? []);
    }

    public function parseWebhook(Request $request): ?PdpWebhookEvent
    {
        if (! $this->verifySignature($request)) {
            throw new PdpSignatureException('Invalid Qonto webhook signature.');
        }

        $payload = $request->json()->all();
        $event   = (string) ($payload['event_type'] ?? '');

        // Événement non lié aux factures (transaction, attachment…) → à ignorer.
        if (! str_starts_with($event, 'invoice.')) {
            return null;
        }

        $externalId = $payload['data']['id'] ?? $payload['invoice']['id'] ?? null;
        $lifecycle  = $this->mapEvent($event);

        if (! $externalId || ! $lifecycle) {
            Log::warning('QontoGateway: unprocessable webhook', ['event' => $event]);
            return null;
        }

        $reason = $payload['invoice']['rejection_reason'] ?? $payload['rejection_reason'] ?? null;

        return new PdpWebhookEvent($externalId, $lifecycle, $reason, $payload);
    }

    private function baseUrl(): string
    {
        return rtrim(config('services.qonto.api_base_url', 'https://thirdparty.qonto.com/v2'), '/');
    }

    /** Traduit un statut renvoyé par l'API Qonto vers le vocabulaire canonique. */
    private function mapProviderStatus(?string $status): ?PdpLifecycle
    {
        return $status ? PdpLifecycle::tryFrom($status) : null;
    }

    /** Traduit un type d'événement webhook Qonto vers le vocabulaire canonique. */
    private function mapEvent(string $event): ?PdpLifecycle
    {
        return match ($event) {
            'invoice.submitted'    => PdpLifecycle::Submitted,
            'invoice.acknowledged' => PdpLifecycle::Acknowledged,
            'invoice.rejected'     => PdpLifecycle::Rejected,
            'invoice.refused'      => PdpLifecycle::Refused,
            'invoice.accepted'     => PdpLifecycle::Accepted,
            'invoice.paid'         => PdpLifecycle::Paid,
            default                => null,
        };
    }

    private function verifySignature(Request $request): bool
    {
        $secret = config('services.qonto.webhook_secret', '');

        if ($secret === '') {
            if (app()->isProduction()) {
                Log::error('QontoGateway: QONTO_WEBHOOK_SECRET not configured in production');
                return false;
            }
            Log::warning('QontoGateway: webhook secret not configured, skipping signature check (non-production)');
            return true;
        }

        $signature = $request->header('X-Qonto-Signature-256', '');
        $expected  = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Génère le contenu binaire du PDF Factur-X en réutilisant la logique Zugferd
     * de PrintController (qui écrit le fichier sur disque), puis le relit.
     */
    private function generateFactureXContent(Invoices $invoice): string
    {
        app(PrintController::class)->getInvoiceFactureX($invoice);

        $path = public_path('pdf/invoices/' . __('general_content.invoice_trans_key') . '-' . $invoice->id . '.pdf');

        if (! file_exists($path)) {
            throw new \RuntimeException("Factur-X PDF not found at {$path} for invoice #{$invoice->id}");
        }

        return file_get_contents($path);
    }

    /** Résout l'ID Qonto du client WEM correspondant à une entreprise. */
    private function resolveClientId(int $tenantId, int $companyId): ?string
    {
        return QontoClientMapping::where('tenant_id', $tenantId)
            ->where('wem_client_id', $companyId)
            ->whereNotNull('qonto_client_id')
            ->value('qonto_client_id');
    }
}
