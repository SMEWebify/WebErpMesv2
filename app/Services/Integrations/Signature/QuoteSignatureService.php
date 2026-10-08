<?php

namespace App\Services\Integrations\Signature;

use App\Models\Integrations\ESignatureSetting;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\QuoteSignature;
use App\Services\Documents\DocumentPdfService;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use App\Services\Integrations\Signature\Contracts\SignatureGateway;
use App\Services\Integrations\Signature\Data\SignatureRequest;
use App\Services\Integrations\Signature\Drivers\DocuSignGateway;
use App\Services\Integrations\Signature\Exceptions\SignatureException;
use App\Services\QuoteCalculatorService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

/**
 * Signature électronique d'un devis depuis sa page publique.
 *
 * Cycle : start() envoie une enveloppe (devis + page « Bon pour accord ») et
 * renvoie l'URL de signature ; sync() relit l'enveloppe chez le prestataire et,
 * une fois signée, range le PDF signé et le certificat dans la GED du devis puis
 * passe le devis en « Gagné ». sync() est appelé au retour du client, par la
 * notification du prestataire et par la tâche planifiée wem:esign:sync — rien
 * de ce que le navigateur raconte n'est cru sans cette relecture.
 */
class QuoteSignatureService
{
    public function __construct(
        private readonly DocumentPdfService $pdfService,
        private readonly FileStorageService $files,
    ) {}

    public function setting(): ?ESignatureSetting
    {
        return ESignatureSetting::current();
    }

    public function isAvailable(): bool
    {
        return (bool) $this->setting()?->isUsable();
    }

    public function gateway(): SignatureGateway
    {
        $setting = $this->setting();

        if (! $setting || ! $setting->isComplete()) {
            throw new SignatureException("La signature électronique n'est pas configurée.");
        }

        return match ($setting->provider) {
            'docusign' => new DocuSignGateway($setting),
            default    => throw new SignatureException("Prestataire de signature inconnu : {$setting->provider}."),
        };
    }

    /**
     * Raison pour laquelle le devis ne peut pas être signé, ou null s'il le peut.
     */
    public function blockingReason(Quotes $quote): ?string
    {
        if (! $this->isAvailable()) {
            return __('esignature.blocked_unavailable');
        }
        if ($quote->is_template) {
            return __('esignature.blocked_template');
        }
        if ($quote->signatures()->where('status', QuoteSignature::STATUS_COMPLETED)->exists()) {
            return __('esignature.blocked_already_signed');
        }
        // Seul un devis envoyé engage : brouillon, gagné, perdu, clos ou obsolète ne se signent pas.
        if ((int) $quote->statu !== 2) {
            return __('esignature.blocked_status');
        }
        if ($quote->validity_date && Carbon::parse($quote->validity_date)->endOfDay()->isPast()) {
            return __('esignature.blocked_expired');
        }
        if (! filter_var($quote->contact?->mail, FILTER_VALIDATE_EMAIL)) {
            return __('esignature.blocked_no_contact');
        }

        return null;
    }

    public function canBeSigned(Quotes $quote): bool
    {
        return $this->blockingReason($quote) === null;
    }

    /**
     * Envoie (ou reprend) l'enveloppe du devis.
     *
     * @return string|null URL de signature intégrée, null en mode e-mail
     *
     * @throws SignatureException
     */
    public function start(Quotes $quote, string $returnUrl): ?string
    {
        if ($reason = $this->blockingReason($quote)) {
            throw new SignatureException($reason);
        }

        $setting = $this->setting();
        $gateway = $this->gateway();
        $total = round((float) (new QuoteCalculatorService($quote))->getTotalPrice(), 2);

        $pending = $quote->signatures()->pending()->latest('id')->first();

        if ($pending && $this->isStillValid($pending, $total, $setting)) {
            return $pending->client_user_id
                ? $gateway->signingUrl($pending->envelope_id, $pending->signer_name, $pending->signer_email, $pending->client_user_id, $returnUrl)
                : null;
        }

        if ($pending) {
            // Le devis a changé depuis l'envoi (ou le mode de signature) : on ne
            // laisse pas signer un document qui n'est plus celui du devis.
            $gateway->cancel($pending->envelope_id, __('esignature.void_reason_changed'));
            $pending->update(['status' => QuoteSignature::STATUS_VOIDED, 'status_reason' => __('esignature.void_reason_changed')]);
        }

        $contact = $quote->contact;
        $signerName = trim(($contact->first_name ?? '') . ' ' . ($contact->name ?? '')) ?: (string) $contact->mail;
        $clientUserId = $setting->isEmbedded() ? (string) Str::uuid() : null;

        $request = new SignatureRequest(
            subject: __('esignature.envelope_subject', ['code' => $quote->code, 'factory' => app('Factory')->name ?? config('app.name')]),
            documents: [
                ['name' => $this->pdfService->fileName($quote), 'content' => $this->pdfService->render(Quotes::withTemplates()->findOrFail($quote->id))],
                ['name' => __('esignature.acceptance_title') . '.pdf', 'content' => $this->acceptancePage($quote, $total)],
            ],
            signerName: $signerName,
            signerEmail: (string) $contact->mail,
            clientUserId: $clientUserId,
            webhookUrl: $this->webhookUrl(),
        );

        $envelopeId = $gateway->send($request);

        $quote->signatures()->create([
            'provider'       => $gateway->key(),
            'envelope_id'    => $envelopeId,
            'signing_mode'   => $setting->signing_mode,
            'client_user_id' => $clientUserId,
            'status'         => QuoteSignature::STATUS_SENT,
            'signer_name'    => $signerName,
            'signer_email'   => (string) $contact->mail,
            'quote_total'    => $total,
            'sent_at'        => now(),
        ]);

        return $clientUserId
            ? $gateway->signingUrl($envelopeId, $signerName, (string) $contact->mail, $clientUserId, $returnUrl)
            : null;
    }

    /**
     * Relit l'enveloppe chez le prestataire et applique ce qu'elle dit.
     *
     * Verrouillé par enveloppe : le retour du client et la notification arrivent
     * souvent à la même seconde, et sans verrou le PDF signé serait rangé deux fois.
     *
     * @throws SignatureException
     */
    public function sync(QuoteSignature $signature): QuoteSignature
    {
        return Cache::lock('quote-signature-sync-' . $signature->id, 120)->block(60, function () use ($signature) {
            $signature->refresh();

            if ($signature->isFinal()) {
                return $signature;
            }

            $gateway = $this->gateway();
            $state = $gateway->status($signature->envelope_id);

            if (! $state->isCompleted()) {
                $signature->update([
                    'status'          => $state->status,
                    'status_reason'   => $state->reason,
                    'last_checked_at' => now(),
                ]);

                return $signature;
            }

            $quote = $signature->quote;
            $hashtags = ['signature-' . $signature->id];

            $signed = $this->files->storeContents(
                $gateway->signedDocument($signature->envelope_id),
                str_replace(['/', '\\'], '-', $quote->code) . ' - ' . __('esignature.signed_suffix') . '.pdf',
                'application/pdf',
                ['comment' => __('esignature.signed_file_comment', ['name' => $signature->signer_name]), 'hashtags' => $hashtags],
            );
            $this->files->attach($signed, $quote, FileRole::SIGNED, true);

            $certificate = null;
            if ($content = $gateway->certificate($signature->envelope_id)) {
                $certificate = $this->files->storeContents(
                    $content,
                    str_replace(['/', '\\'], '-', $quote->code) . ' - ' . __('esignature.certificate_suffix') . '.pdf',
                    'application/pdf',
                    ['comment' => __('esignature.certificate_file_comment'), 'hashtags' => $hashtags],
                );
                $this->files->attach($certificate, $quote, FileRole::SIGNED);
            }

            $signature->update([
                'status'              => QuoteSignature::STATUS_COMPLETED,
                'status_reason'       => null,
                'signed_file_id'      => $signed->id,
                'certificate_file_id' => $certificate?->id,
                'completed_at'        => $state->completedAt ?? now(),
                'last_checked_at'     => now(),
            ]);

            // Ouvert ou envoyé → gagné. Un devis déjà clos à la main n'est pas rouvert.
            if (in_array((int) $quote->statu, [1, 2], true)) {
                $quote->update(['statu' => 3]);
            }

            return $signature;
        });
    }

    /**
     * Synchronise toutes les enveloppes en attente (tâche planifiée).
     *
     * @return array{checked: int, completed: int, failed: int}
     */
    public function syncPending(): array
    {
        $result = ['checked' => 0, 'completed' => 0, 'failed' => 0];

        if (! $this->setting()?->isComplete()) {
            return $result;
        }

        QuoteSignature::pending()->orderBy('id')->each(function (QuoteSignature $signature) use (&$result) {
            $result['checked']++;

            try {
                if ($this->sync($signature)->isCompleted()) {
                    $result['completed']++;
                }
            } catch (\Throwable $e) {
                $result['failed']++;
                Log::warning('QuoteSignature: sync failed', ['signature' => $signature->id, 'error' => $e->getMessage()]);
            }
        });

        return $result;
    }

    /**
     * URL de notification donnée au prestataire, uniquement si l'instance est
     * publiée en HTTPS : DocuSign refuse les autres, et une instance locale ne
     * serait de toute façon pas joignable. La tâche planifiée prend alors le relais.
     */
    public function webhookUrl(): ?string
    {
        $url = route('api.integrations.esignature.webhook');

        return str_starts_with($url, 'https://') ? $url : null;
    }

    private function isStillValid(QuoteSignature $pending, float $total, ESignatureSetting $setting): bool
    {
        return abs((float) $pending->quote_total - $total) < 0.005
            && $pending->signing_mode === $setting->signing_mode;
    }

    /**
     * Page « Bon pour accord » jointe au devis. Le PDF du devis n'est pas touché :
     * les champs de signature s'accrochent aux marqueurs de cette page seulement.
     */
    private function acceptancePage(Quotes $quote, float $total): string
    {
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $formattedTotal = str_replace(["\u{00A0}", "\u{202F}"], ' ', Number::currency($total, $currency, config('app.locale')));

        return (string) PDF::loadView('print/esignature-acceptance', [
            'Quote'          => $quote,
            'Factory'        => $factory,
            'formattedTotal' => $formattedTotal,
            'anchorSign'     => DocuSignGateway::ANCHOR_SIGN,
            'anchorName'     => DocuSignGateway::ANCHOR_NAME,
            'anchorDate'     => DocuSignGateway::ANCHOR_DATE,
        ])->output();
    }
}
