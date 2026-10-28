<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\Quality\QualityDerogationSignature;
use App\Models\Workflow\QuoteSignature;
use App\Services\Integrations\Signature\DerogationSignatureService;
use App\Services\Integrations\Signature\Exceptions\SignatureException;
use App\Services\Integrations\Signature\QuoteSignatureService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Notification du prestataire de signature (DocuSign Connect).
 *
 * Elle ne fait que déclencher QuoteSignatureService::sync(), qui relit
 * l'enveloppe par l'API authentifiée : un appel forgé ne peut au pire que
 * provoquer une relecture, jamais marquer un devis comme signé.
 */
class ESignatureWebhookController extends Controller
{
    public function __construct(
        private readonly QuoteSignatureService $service,
        private readonly DerogationSignatureService $derogations,
    ) {}

    public function handle(Request $request): Response
    {
        if (! $this->service->setting()?->isComplete()) {
            return response('Not configured', 404);
        }

        try {
            $envelopeId = $this->service->gateway()->parseWebhook($request);
        } catch (SignatureException $e) {
            Log::warning('ESignatureWebhook: invalid signature', ['ip' => $request->ip()]);

            return response('Unauthorized', 401);
        }

        $signature = $envelopeId ? QuoteSignature::where('envelope_id', $envelopeId)->first() : null;
        // Sinon, accord client sur une dérogation.
        $derogationSignature = ! $signature && $envelopeId
            ? QualityDerogationSignature::where('envelope_id', $envelopeId)->first()
            : null;

        // Enveloppe inconnue (envoyée hors WEM, ou document supprimé) → on acquitte.
        if (! $signature && ! $derogationSignature) {
            return response('OK', 200);
        }

        try {
            $signature
                ? $this->service->sync($signature)
                : $this->derogations->sync($derogationSignature);
        } catch (\Throwable $e) {
            Log::error('ESignatureWebhook: sync failed', ['envelope' => $envelopeId, 'error' => $e->getMessage()]);

            // 500 → DocuSign renverra la notification plus tard.
            return response('Internal Server Error', 500);
        }

        return response('OK', 200);
    }
}
