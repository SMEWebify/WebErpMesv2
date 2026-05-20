<?php

namespace App\Events;

use App\Models\Workflow\Deliverys;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryStatusChanged
{
    use Dispatchable, SerializesModels;

    public $delivery;
    public $newStatus;

    public function __construct(Deliverys $delivery, int $newStatus)
    {
        $this->delivery  = $delivery;
        $this->newStatus = $newStatus;
    }
}
