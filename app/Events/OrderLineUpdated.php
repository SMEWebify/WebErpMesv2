<?php

namespace App\Events;

use App\Models\Workflow\OrderLines;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderLineUpdated
{
    use Dispatchable, SerializesModels;

    public $orderLine;

    public function __construct(OrderLines $orderLine)
    {
        $this->orderLine = $orderLine;
    }
}
