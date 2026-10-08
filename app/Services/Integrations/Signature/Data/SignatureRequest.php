<?php

namespace App\Services\Integrations\Signature\Data;

/**
 * Ce qu'on demande à signer, à qui, et comment être prévenu.
 */
final class SignatureRequest
{
    /**
     * @param  array<int, array{name: string, content: string}>  $documents  PDF dans l'ordre d'affichage
     * @param  string|null  $clientUserId  signature intégrée si renseigné, sinon envoi par e-mail du prestataire
     * @param  string|null  $webhookUrl  URL de notification, null si l'instance n'est pas joignable
     */
    public function __construct(
        public readonly string $subject,
        public readonly array $documents,
        public readonly string $signerName,
        public readonly string $signerEmail,
        public readonly ?string $clientUserId = null,
        public readonly ?string $webhookUrl = null,
    ) {}
}
