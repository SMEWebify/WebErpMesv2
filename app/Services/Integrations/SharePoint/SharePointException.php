<?php

namespace App\Services\Integrations\SharePoint;

use RuntimeException;

/**
 * Échec d'un appel Microsoft Graph, avec le statut HTTP (0 = réseau) et le délai
 * demandé par Graph quand il limite le débit (429 / 503, en-tête Retry-After).
 */
class SharePointException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly ?int $retryAfter = null,
    ) {
        parent::__construct($message, $status);
    }

    /**
     * Vaut la peine de réessayer plus tard : réseau, limitation, panne côté Microsoft.
     */
    public function isTransient(): bool
    {
        return $this->status === 0 || $this->status === 429 || $this->status >= 500;
    }

    public function isNotFound(): bool
    {
        return $this->status === 404;
    }
}
