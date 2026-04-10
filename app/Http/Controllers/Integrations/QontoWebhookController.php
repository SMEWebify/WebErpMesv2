<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\Integrations\QontoInvoiceMapping;
use App\Services\Integrations\QontoInvoiceSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Reçoit les webhooks Qonto et met à jour le cycle de vie des factures.
 *
 * Qonto signe chaque requête avec un header X-Qonto-Signature-256 (HMAC-SHA256).
 * La clé secrète est configurée dans QONTO_WEBHOOK_SECRET.
 *
 * Événements traités :
 *   invoice.submitted     → lifecycle: submitted
 *   invoice.acknowledged  → lifecycle: acknowledged
 *   invoice.rejected      → lifecycle: rejected
 *   invoice.refused       → lifecycle: refused
 *   invoice.accepted      → lifecycle: accepted
 *   invoice.paid          → lifecycle: paid
 */
class QontoWebhookController extends Controller
{
    public function __construct(private QontoInvoiceSyncService $invoiceSyncService) {}

    public function handle(Request $request): Response
    {
        // Vérification de la signature HMAC
        if (! $this->verifySignature($request)) {
            Log::warning('QontoWebhook: invalid signature', ['ip' => $request->ip()]);
            return response('Unauthorized', 401);
        }

        $payload = $request->json()->all();
        $event   = $payload['event_type'] ?? null;

        Log::info('QontoWebhook: received', ['event' => $event]);

        if (! str_starts_with((string) $event, 'invoice.')) {
            // Événement non géré (ex: transaction, attachment...) — on acquitte sans traitement
            return response('OK', 200);
        }

        $qontoInvoiceId  = $payload['data']['id'] ?? $payload['invoice']['id'] ?? null;
        $lifecycleStatus = $this->eventToLifecycle($event);

        if (! $qontoInvoiceId || ! $lifecycleStatus) {
            Log::warning('QontoWebhook: unprocessable payload', ['event' => $event, 'payload' => $payload]);
            return response('Unprocessable', 422);
        }

        $mapping = QontoInvoiceMapping::where('qonto_invoice_id', $qontoInvoiceId)->first();

        if (! $mapping) {
            Log::warning('QontoWebhook: no mapping found', ['qonto_invoice_id' => $qontoInvoiceId]);
            // On acquitte quand même pour éviter les renvois Qonto
            return response('OK', 200);
        }

        try {
            $this->invoiceSyncService->applyLifecycle($mapping, $lifecycleStatus, $payload);
        } catch (\Throwable $e) {
            Log::error('QontoWebhook: failed to apply lifecycle', [
                'qonto_invoice_id' => $qontoInvoiceId,
                'lifecycle'        => $lifecycleStatus,
                'error'            => $e->getMessage(),
            ]);
            return response('Internal Server Error', 500);
        }

        return response('OK', 200);
    }

    private function verifySignature(Request $request): bool
    {
        $secret = config('services.qonto.webhook_secret', '');

        // Si pas de secret configuré → on laisse passer (dev/test) mais on log
        if ($secret === '') {
            Log::warning('QontoWebhook: QONTO_WEBHOOK_SECRET not configured, skipping signature check');
            return true;
        }

        $signature = $request->header('X-Qonto-Signature-256', '');
        $expected  = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    private function eventToLifecycle(string $event): ?string
    {
        return match ($event) {
            'invoice.submitted'    => QontoInvoiceSyncService::LIFECYCLE_SUBMITTED,
            'invoice.acknowledged' => QontoInvoiceSyncService::LIFECYCLE_ACKNOWLEDGED,
            'invoice.rejected'     => QontoInvoiceSyncService::LIFECYCLE_REJECTED,
            'invoice.refused'      => QontoInvoiceSyncService::LIFECYCLE_REFUSED,
            'invoice.accepted'     => QontoInvoiceSyncService::LIFECYCLE_ACCEPTED,
            'invoice.paid'         => QontoInvoiceSyncService::LIFECYCLE_PAID,
            default                => null,
        };
    }
}
