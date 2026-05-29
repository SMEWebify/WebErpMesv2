<?php

namespace App\Services\Integrations\Pdp\Exceptions;

/**
 * Levée par un driver lorsqu'un webhook entrant a une signature invalide.
 * Le contrôleur la traduit en réponse HTTP 401.
 */
class PdpSignatureException extends \RuntimeException
{
}
