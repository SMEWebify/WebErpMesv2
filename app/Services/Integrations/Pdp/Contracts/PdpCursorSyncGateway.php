<?php

namespace App\Services\Integrations\Pdp\Contracts;

use App\Services\Integrations\Pdp\Data\PdpWebhookEvent;

/**
 * Contrat OPTIONNEL pour les PDP qui n'émettent pas de webhooks et se
 * synchronisent par interrogation avec un curseur (cas de SUPER PDP).
 *
 * Le principe : la plateforme garantit des id atomiquement strictement
 * croissants et accepte un paramètre « à partir de tel id ». En mémorisant le
 * dernier id traité, on récupère l'exhaustivité des objets sans jamais en
 * sauter — garantie qu'un filtre par date de création ne donne pas.
 *
 * Lecture et validation du curseur sont **séparées** : la synchronisation lit
 * (fetch*), traite, puis n'avance le curseur (commit*) que sur ce qu'elle a
 * réellement traité. Un échec de traitement n'entraîne donc pas de perte.
 */
interface PdpCursorSyncGateway
{
    /**
     * Événements de cycle de vie survenus depuis le curseur, sur NOS factures
     * émises. Triés par id d'événement croissant ; `raw['id']` porte cet id.
     *
     * @return array<int, PdpWebhookEvent>
     */
    public function fetchEvents(int $tenantId): array;

    /** Valide la lecture du flux d'événements jusqu'à cet id inclus. */
    public function commitEvents(int $tenantId, int $lastEventId): void;

    /** Valide la lecture des factures entrantes jusqu'à cet id inclus. */
    public function commitInbound(int $tenantId, int $lastInvoiceId): void;
}
