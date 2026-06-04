<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Services\Integrations\Pdp\Exceptions\PdpSignatureException;
use App\Services\Integrations\Pdp\PdpInvoiceService;
use App\Services\Integrations\Pdp\PdpManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Point d'entrée des webhooks Qonto (cycle de vie des factures électroniques).
 *
 * La vérification de signature et la traduction des événements vers le
 * vocabulaire canonique sont déléguées au driver QontoGateway ; ce contrôleur
 * ne fait que résoudre le driver et confier l'événement à PdpInvoiceService.
 */
class QontoWebhookController extends Controller
{
    public function __construct(
        private PdpManager $manager,
        private PdpInvoiceService $invoiceService,
    ) {}

    public function handle(Request $request): Response
    {
        $gateway = $this->manager->driver('qonto');

        try {
            $event = $gateway->parseWebhook($request);
        } catch (PdpSignatureException $e) {
            Log::warning('QontoWebhook: invalid signature', ['ip' => $request->ip()]);
            return response('Unauthorized', 401);
        }

        // Événement non pertinent (autre type, payload incomplet) → on acquitte.
        if (! $event) {
            return response('OK', 200);
        }

        try {
            $this->invoiceService->handleWebhook($gateway->key(), $event);
        } catch (\Throwable $e) {
            Log::error('QontoWebhook: failed to apply lifecycle', [
                'external_id' => $event->externalId,
                'lifecycle'   => $event->lifecycle->value,
                'error'       => $e->getMessage(),
            ]);
            return response('Internal Server Error', 500);
        }

        return response('OK', 200);
    }
}
