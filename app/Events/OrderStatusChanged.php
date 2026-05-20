<?php

namespace App\Events;

use App\Models\Workflow\Orders;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged
{
    use Dispatchable, SerializesModels;

    public $order;
    public $newStatus;

    public function __construct(Orders $order, int $newStatus)
    {
        $this->order     = $order;
        $this->newStatus = $newStatus;
    }
}
