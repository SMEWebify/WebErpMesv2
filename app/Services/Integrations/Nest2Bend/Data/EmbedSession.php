<?php

namespace App\Services\Integrations\Nest2Bend\Data;

/**
 * Réponse 201 de POST /api/v1/embed-sessions.
 *
 * `url` porte un code à usage unique valable 60 s : il est renvoyé tel quel au navigateur
 * qui a cliqué, jamais stocké ni journalisé.
 */
final class EmbedSession
{
    /**
     * @param  list<string>  $remarques
     */
    public function __construct(
        public readonly string $sessionId,
        public readonly string $url,
        public readonly int $expiresIn,
        public readonly array $remarques = [],
    ) {}
}
