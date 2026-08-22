<?php

namespace App\Services\Integrations\Pdp;

use App\Events\InvoiceStatusChanged;
use App\Models\Integrations\PdpInvoiceSubmission;
use App\Models\Workflow\Invoices;
use App\Services\Integrations\Pdp\Data\PdpWebhookEvent;
use App\Services\Integrations\Pdp\Enums\PdpLifecycle;
use Illuminate\Support\Facades\Log;

/**
 * Orchestration agnostique du dépôt de factures vers une PDP : persistance du
 * suivi (pdp_invoice_submissions), application du cycle de vie et répercussion
 * sur le statut interne WEM. Toute la logique spécifique fournisseur est
 * déléguée au driver (PdpGateway) résolu par le PdpManager.
 */
class PdpInvoiceService
{
    public function __construct(private PdpManager $manager) {}

    /**
     * Dépose une facture via le driver PDP par défaut et enregistre le suivi.
     *
     * @throws \Throwable en cas d'erreur API
     */
    public function submit(Invoices $invoice): PdpInvoiceSubmission
    {
        $gateway = $this->manager->driver();
        $result  = $gateway->submit($invoice);

        return PdpInvoiceSubmission::updateOrCreate(
            ['tenant_id' => $invoice->user_id, 'invoice_id' => $invoice->id],
            [
                'provider'         => $gateway->key(),
                'external_id'      => $result->externalId,
                'lifecycle_status' => $result->lifecycle->value,
                'submitted_at'     => now(),
                'rejection_reason' => null,
            ]
        );
    }

    /**
     * Interroge le statut d'une facture déposée et met à jour WEM si besoin.
     */
    public function poll(PdpInvoiceSubmission $submission): PdpInvoiceSubmission
    {
        if (! $submission->external_id) {
            return $submission;
        }

        $gateway = $this->manager->driver($submission->provider);
        $result  = $gateway->poll($submission->external_id, $submission->tenant_id);

        if ($result->lifecycle->value !== $submission->lifecycle_status) {
            $this->applyLifecycle($submission, $result->lifecycle, $result->rejectionReason);
        }

        return $submission->fresh();
    }

    /**
     * Traite un événement de cycle de vie normalisé (webhook vérifié par le
     * driver, ou lu dans le flux d'événements de la plateforme).
     *
     * @return bool false si l'événement ne concerne aucune facture connue de
     *              WEM — cas normal quand le compte de la plateforme porte des
     *              factures déposées par un autre outil. L'appelant peut ainsi
     *              rendre compte du traitement sans le surestimer.
     */
    public function handleWebhook(string $provider, PdpWebhookEvent $event): bool
    {
        $submission = PdpInvoiceSubmission::where('provider', $provider)
            ->where('external_id', $event->externalId)
            ->first();

        if (! $submission) {
            Log::warning('PdpInvoice: no submission found for webhook', [
                'provider'    => $provider,
                'external_id' => $event->externalId,
            ]);
            return false;
        }

        // Idempotence : on n'applique que si le statut change réellement.
        if ($event->lifecycle->value !== $submission->lifecycle_status) {
            $this->applyLifecycle($submission, $event->lifecycle, $event->rejectionReason);
        }

        return true;
    }

    /**
     * Applique un changement de cycle de vie : met à jour le suivi puis
     * répercute le statut interne WEM si la correspondance existe.
     */
    public function applyLifecycle(PdpInvoiceSubmission $submission, PdpLifecycle $lifecycle, ?string $rejectionReason = null): void
    {
        $updates = ['lifecycle_status' => $lifecycle->value];

        if ($lifecycle === PdpLifecycle::Acknowledged) {
            $updates['acknowledged_at'] = now();
        }
        if ($rejectionReason) {
            $updates['rejection_reason'] = $rejectionReason;
        }

        $submission->update($updates);

        $wemStatus = $lifecycle->toWemStatus();
        if ($wemStatus === null) {
            return;
        }

        $invoice = $submission->invoice;
        if ($invoice && $invoice->statu !== $wemStatus) {
            $invoice->update(['statu' => $wemStatus]);
            $invoice->invoiceLines()->update(['invoice_status' => $wemStatus]);
            event(new InvoiceStatusChanged($invoice, $wemStatus));

            Log::info('PdpInvoice: WEM status updated', [
                'invoice_id'       => $invoice->id,
                'provider'         => $submission->provider,
                'lifecycle_status' => $lifecycle->value,
                'wem_status'       => $wemStatus,
            ]);
        }
    }
}
