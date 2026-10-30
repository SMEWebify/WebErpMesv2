<?php

namespace App\Services\Workflow;

class OrderLineCalculatedPriceException extends \RuntimeException
{
    public function __construct(string $message, private int $status = 422, private bool $reasonRequired = false)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function reasonRequired(): bool
    {
        return $this->reasonRequired;
    }
}
