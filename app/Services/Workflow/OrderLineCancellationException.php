<?php

namespace App\Services\Workflow;

/**
 * Refus d'annuler une ligne de commande ou une ligne de BL (message affichable).
 */
class OrderLineCancellationException extends \RuntimeException
{
    public function __construct(string $message, private int $status = 422)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
