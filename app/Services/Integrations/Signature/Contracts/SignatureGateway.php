<?php

namespace App\Services\Integrations\Signature\Contracts;

use App\Services\Integrations\Signature\Data\EnvelopeState;
use App\Services\Integrations\Signature\Data\SignatureRequest;
use App\Services\Integrations\Signature\Exceptions\SignatureException;
use Illuminate\Http\Request;

/**
 * Contrat d'un prestataire de signature électronique.
 *
 * Même logique que la PDP : QuoteSignatureService ne connaît que ce contrat, un
 * second prestataire (Yousign…) s'ajoute par un driver sans toucher au devis.
 * Toutes les méthodes lèvent SignatureException avec un message lisible par
 * l'utilisateur quand le prestataire refuse.
 */
interface SignatureGateway
{
    public function key(): string;

    /**
     * Envoie l'enveloppe et renvoie son identifiant chez le prestataire.
     *
     * @throws SignatureException
     */
    public function send(SignatureRequest $request): string;

    /**
     * URL de signature intégrée, à usage unique et de courte durée : à obtenir
     * au moment où le client clique, jamais à stocker.
     *
     * @throws SignatureException
     */
    public function signingUrl(string $envelopeId, string $signerName, string $signerEmail, string $clientUserId, string $returnUrl): string;

    /**
     * @throws SignatureException
     */
    public function status(string $envelopeId): EnvelopeState;

    /**
     * Document signé (toutes les pièces de l'enveloppe en un PDF).
     *
     * @throws SignatureException
     */
    public function signedDocument(string $envelopeId): string;

    /**
     * Certificat de signature (journal de preuve), ou null si le prestataire n'en fournit pas.
     *
     * @throws SignatureException
     */
    public function certificate(string $envelopeId): ?string;

    /**
     * @throws SignatureException
     */
    public function cancel(string $envelopeId, string $reason): void;

    /**
     * Authentifie une notification entrante et renvoie l'identifiant d'enveloppe
     * qu'elle concerne (null si elle n'en porte pas). La notification ne sert qu'à
     * déclencher une relecture par status() : son contenu n'est jamais cru.
     *
     * @throws SignatureException signature invalide
     */
    public function parseWebhook(Request $request): ?string;

    /**
     * Vérifie les identifiants et renvoie le nom du compte connecté.
     *
     * @throws SignatureException
     */
    public function testConnection(): string;
}
