<?php

namespace App\Http\Controllers;

use App\Models\Workflow\Quotes;
use App\Services\Integrations\Signature\Exceptions\SignatureException;
use App\Services\Integrations\Signature\QuoteSignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Signature d'un devis depuis son lien public (sans authentification, sécurisé
 * par l'UUID du devis comme le reste de la page guest).
 */
class GuestQuoteSignatureController extends Controller
{
    public function __construct(private readonly QuoteSignatureService $service) {}

    public function start(string $uuid): RedirectResponse
    {
        $quote = Quotes::where('uuid', $uuid)->firstOrFail();

        try {
            $url = $this->service->start($quote, route('guest.quote.signature.return', ['uuid' => $uuid]));
        } catch (SignatureException $e) {
            Log::warning('QuoteSignature: start failed', ['quote' => $quote->id, 'error' => $e->getMessage()]);

            return redirect()->route('guest.quote.show', ['uuid' => $uuid])
                ->with('signature_error', __('esignature.flash_error'));
        }

        if ($url) {
            return redirect()->away($url);
        }

        return redirect()->route('guest.quote.show', ['uuid' => $uuid])
            ->with('signature_info', __('esignature.flash_email_sent', ['email' => $quote->contact?->mail]));
    }

    /**
     * Retour de DocuSign après la session de signature. Le paramètre `event` ne
     * sert qu'à choisir le message : l'état réel est relu auprès de DocuSign.
     */
    public function return(Request $request, string $uuid): RedirectResponse
    {
        $quote = Quotes::where('uuid', $uuid)->firstOrFail();
        $signature = $quote->signatures()->latest('id')->first();
        $redirect = redirect()->route('guest.quote.show', ['uuid' => $uuid]);

        if (! $signature) {
            return $redirect;
        }

        try {
            $signature = $this->service->sync($signature);
        } catch (SignatureException $e) {
            Log::warning('QuoteSignature: sync on return failed', ['signature' => $signature->id, 'error' => $e->getMessage()]);
        }

        if ($signature->isCompleted()) {
            return $redirect->with('signature_info', __('esignature.flash_signed'));
        }

        return match ((string) $request->query('event')) {
            'signing_complete' => $redirect->with('signature_info', __('esignature.flash_processing')),
            'decline'          => $redirect->with('signature_error', __('esignature.flash_declined')),
            default            => $redirect->with('signature_info', __('esignature.flash_cancelled')),
        };
    }
}
