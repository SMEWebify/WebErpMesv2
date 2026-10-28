<?php

namespace App\Services\Integrations\Signature;

use App\Models\Quality\QualityDerogation;
use App\Models\Quality\QualityDerogationSignature;
use App\Models\User;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use App\Services\Integrations\Signature\Contracts\SignatureGateway;
use App\Services\Integrations\Signature\Data\SignatureRequest;
use App\Services\Integrations\Signature\Drivers\DocuSignGateway;
use App\Services\Integrations\Signature\Exceptions\SignatureException;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Accord du client sur une dérogation, signé par DocuSign.
 *
 * Toujours par e-mail DocuSign au contact de la dérogation : la boîte du contact
 * authentifie le signataire, ce que demande une preuve d'accord client (aucune
 * page publique, contrairement aux devis). Réutilise la configuration et le
 * driver de la signature des devis (QuoteSignatureService::gateway()).
 *
 * Signée → PDF signé et certificat dans la GED de la dérogation (rôle
 * `document_signe`, qui vaut preuve pour DerogationApproval), date, signataire
 * et réponse renseignés s'ils étaient vides. La dérogation n'est **pas** validée
 * d'office : la validation reste un acte de l'entreprise, sous permission.
 */
class DerogationSignatureService
{
    public function __construct(
        private readonly QuoteSignatureService $quoteSignatures,
        private readonly FileStorageService $files,
    ) {}

    /**
     * La configuration DocuSign est saisie. L'option « Proposer la signature sur
     * les devis publics » ne concerne que la page publique des devis.
     */
    public function isAvailable(): bool
    {
        return (bool) $this->quoteSignatures->setting()?->isComplete();
    }

    public function gateway(): SignatureGateway
    {
        return $this->quoteSignatures->gateway();
    }

    /**
     * Raison pour laquelle la demande ne peut pas partir, ou null si elle le peut.
     */
    public function blockingReason(QualityDerogation $derogation): ?string
    {
        if (! $this->isAvailable()) {
            return __('derogations.sign_blocked_unavailable');
        }
        if (! $derogation->isExternal()) {
            return __('derogations.sign_blocked_internal');
        }
        if (! in_array((int) $derogation->statu, QualityDerogation::OPEN, true)) {
            return __('derogations.sign_blocked_status');
        }
        if ($derogation->signatures()->where('status', QualityDerogationSignature::STATUS_COMPLETED)->exists()) {
            return __('derogations.sign_blocked_already_signed');
        }
        if (! filter_var($derogation->contact?->mail, FILTER_VALIDATE_EMAIL)) {
            return __('derogations.sign_blocked_no_contact');
        }

        return null;
    }

    /**
     * Envoie la demande au contact. Une enveloppe en attente sur le même contenu
     * est conservée (pas de second e-mail) ; sur un contenu modifié, elle est annulée.
     *
     * @throws SignatureException
     */
    public function send(QualityDerogation $derogation, ?User $sender): QualityDerogationSignature
    {
        if ($reason = $this->blockingReason($derogation)) {
            throw new SignatureException($reason);
        }

        $hash = $derogation->contentHash();
        $pending = $derogation->signatures()->pending()->latest('id')->first();

        if ($pending && $pending->content_hash === $hash) {
            return $pending;
        }

        $gateway = $this->gateway();

        if ($pending) {
            $this->void($pending, __('derogations.void_reason_changed'));
        }

        $contact = $derogation->contact;
        $signerName = trim(($contact->first_name ?? '') . ' ' . ($contact->name ?? '')) ?: (string) $contact->mail;

        $envelopeId = $gateway->send(new SignatureRequest(
            subject: __('derogations.envelope_subject', ['code' => $derogation->code, 'factory' => app('Factory')->name ?? config('app.name')]),
            documents: [
                ['name' => str_replace(['/', '\\'], '-', $derogation->code) . '.pdf', 'content' => $this->requestPdf($derogation)],
            ],
            signerName: $signerName,
            signerEmail: (string) $contact->mail,
            clientUserId: null,
            webhookUrl: $this->quoteSignatures->webhookUrl(),
        ));

        $signature = $derogation->signatures()->create([
            'provider'     => $gateway->key(),
            'envelope_id'  => $envelopeId,
            'status'       => QualityDerogationSignature::STATUS_SENT,
            'signer_name'  => $signerName,
            'signer_email' => (string) $contact->mail,
            'content_hash' => $hash,
            'sent_by'      => $sender?->id,
            'sent_at'      => now(),
        ]);

        if ((int) $derogation->statu === QualityDerogation::STATU_IN_PROGRESS) {
            $derogation->update(['statu' => QualityDerogation::STATU_WAITING_CUSTOMER]);
        }

        return $signature;
    }

    /**
     * Après modification de la dérogation : une demande en attente qui ne
     * correspond plus au contenu est annulée, pour que le client ne signe pas
     * un texte dépassé.
     */
    public function voidIfOutdated(QualityDerogation $derogation): bool
    {
        $pending = $derogation->signatures()->pending()->latest('id')->first();

        if (! $pending || $pending->content_hash === $derogation->contentHash()) {
            return false;
        }

        try {
            $this->void($pending, __('derogations.void_reason_changed'));
        } catch (SignatureException $e) {
            Log::warning('DerogationSignature: void failed', ['signature' => $pending->id, 'error' => $e->getMessage()]);

            return false;
        }

        return true;
    }

    /**
     * Relit l'enveloppe chez le prestataire et applique ce qu'elle dit.
     *
     * @throws SignatureException
     */
    public function sync(QualityDerogationSignature $signature): QualityDerogationSignature
    {
        return Cache::lock('derogation-signature-sync-' . $signature->id, 120)->block(60, function () use ($signature) {
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

            $derogation = $signature->derogation;
            $hashtags = ['signature-derogation-' . $signature->id];
            $baseName = str_replace(['/', '\\'], '-', $derogation->code);

            $signed = $this->files->storeContents(
                $gateway->signedDocument($signature->envelope_id),
                $baseName . ' - ' . __('derogations.signed_suffix') . '.pdf',
                'application/pdf',
                ['comment' => __('derogations.signed_file_comment', ['name' => $signature->signer_name]), 'hashtags' => $hashtags],
            );
            $this->files->attach($signed, $derogation, FileRole::SIGNED, true);

            $certificate = null;
            if ($content = $gateway->certificate($signature->envelope_id)) {
                $certificate = $this->files->storeContents(
                    $content,
                    $baseName . ' - ' . __('esignature.certificate_suffix') . '.pdf',
                    'application/pdf',
                    ['comment' => __('esignature.certificate_file_comment'), 'hashtags' => $hashtags],
                );
                $this->files->attach($certificate, $derogation, FileRole::SIGNED);
            }

            $completedAt = $state->completedAt ?? now();

            $signature->update([
                'status'              => QualityDerogationSignature::STATUS_COMPLETED,
                'status_reason'       => null,
                'signed_file_id'      => $signed->id,
                'certificate_file_id' => $certificate?->id,
                'completed_at'        => $completedAt,
                'last_checked_at'     => now(),
            ]);

            // Preuves de la réponse : on complète, on n'écrase jamais une saisie.
            $derogation->customer_reply_date ??= $completedAt->toDateString();
            if (blank($derogation->customer_signatory)) {
                $derogation->customer_signatory = $signature->signer_name;
            }
            if (blank($derogation->reply)) {
                $derogation->reply = __('derogations.signed_reply', [
                    'name' => $signature->signer_name,
                    'date' => $completedAt->format('d/m/Y'),
                ]);
            }
            $derogation->save();

            return $signature;
        });
    }

    /**
     * @return array{checked: int, completed: int, failed: int}
     */
    public function syncPending(): array
    {
        $result = ['checked' => 0, 'completed' => 0, 'failed' => 0];

        if (! $this->isAvailable()) {
            return $result;
        }

        QualityDerogationSignature::pending()->orderBy('id')->each(function (QualityDerogationSignature $signature) use (&$result) {
            $result['checked']++;

            try {
                if ($this->sync($signature)->isCompleted()) {
                    $result['completed']++;
                }
            } catch (\Throwable $e) {
                $result['failed']++;
                Log::warning('DerogationSignature: sync failed', ['signature' => $signature->id, 'error' => $e->getMessage()]);
            }
        });

        return $result;
    }

    /**
     * @throws SignatureException
     */
    private function void(QualityDerogationSignature $pending, string $reason): void
    {
        $this->gateway()->cancel($pending->envelope_id, $reason);
        $pending->update(['status' => QualityDerogationSignature::STATUS_VOIDED, 'status_reason' => $reason]);
    }

    /**
     * Demande de dérogation à signer : ce qui est demandé, sur quelle pièce, et
     * le bloc d'accord où DocuSign accroche ses champs.
     */
    public function requestPdf(QualityDerogation $derogation): string
    {
        $derogation->loadMissing(['companie', 'contact', 'orderLine.order', 'QualityNonConformity']);

        return (string) PDF::loadView('print/derogation-request', [
            'Derogation' => $derogation,
            'Factory'    => app('Factory'),
            'anchorSign' => DocuSignGateway::ANCHOR_SIGN,
            'anchorName' => DocuSignGateway::ANCHOR_NAME,
            'anchorDate' => DocuSignGateway::ANCHOR_DATE,
        ])->output();
    }
}
