<?php

namespace App\Services\Integrations\Signature\Exceptions;

use RuntimeException;

/**
 * Refus du prestataire ou configuration inutilisable. Le message est destiné à
 * l'utilisateur (en français) ; consentUrl est renseignée quand DocuSign attend
 * le consentement unique de l'administrateur à l'authentification JWT.
 */
class SignatureException extends RuntimeException
{
    public ?string $consentUrl = null;

    public static function consentRequired(string $consentUrl): self
    {
        $e = new self("DocuSign attend votre consentement : ouvrez le lien d'autorisation affiché dans l'écran de configuration, connectez-vous avec l'utilisateur API puis acceptez.");
        $e->consentUrl = $consentUrl;

        return $e;
    }
}
