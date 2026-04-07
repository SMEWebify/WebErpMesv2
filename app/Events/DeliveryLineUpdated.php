<?php

namespace App\Events;

use App\Models\Workflow\DeliveryLines;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryLineUpdated
{
    use Dispatchable, SerializesModels;

    public $deliveryLine;

    public function __construct(DeliveryLines $deliveryLine)
    {
        $this->deliveryLine = $deliveryLine;
    }
}
