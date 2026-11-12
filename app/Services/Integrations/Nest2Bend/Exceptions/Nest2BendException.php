<?php

namespace App\Services\Integrations\Nest2Bend\Exceptions;

use App\Services\Integrations\Nest2Bend\Data\AccountState;
use RuntimeException;

/**
 * Réponse de NEST2BEND autre qu'un succès.
 *
 * Le message est destiné à l'utilisateur : celui de NEST2BEND pour une 422 (déjà traduit
 * selon `langue`), un texte de l'ERP sinon. Il ne contient jamais la clé API, les
 * en-têtes de la requête ni le contenu du fichier envoyé.
 */
class Nest2BendException extends RuntimeException
{
    /** 401 : clé absente, invalide ou révoquée. */
    public const UNAUTHORIZED = 'unauthorized';
    /** 403 : abonnement inactif. */
    public const INACTIVE = 'inactive';
    /** 404 : résultat d'une autre société ou purgé. */
    public const NOT_FOUND = 'not_found';
    /** 422 : validation (fichier refusé, origine non déclarée…). */
    public const INVALID = 'invalid';
    /** 503, autre 5xx, délai dépassé ou serveur injoignable. */
    public const UNAVAILABLE = 'unavailable';
    /** Réponse hors contrat (statut ou JSON inattendu). */
    public const UNEXPECTED = 'unexpected';

    /** 429 : quota de la clé dépassé (120 requêtes / min, tous utilisateurs de l'ERP confondus). */
    public const RATE_LIMITED = 'rate_limited';

    /**
     * @param  AccountState|null  $account  droits renvoyés avec un 403 (même format que /compte)
     */
    public function __construct(
        public readonly string $kind,
        string $message,
        public readonly ?int $status = null,
        public readonly array $errors = [],
        public readonly ?AccountState $account = null,
    ) {
        parent::__construct($message);
    }

    public function is(string $kind): bool
    {
        return $this->kind === $kind;
    }
}
