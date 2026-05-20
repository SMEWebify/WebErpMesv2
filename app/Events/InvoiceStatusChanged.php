<?php

namespace App\Events;

use App\Models\Workflow\Invoices;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceStatusChanged
{
    use Dispatchable, SerializesModels;

    public $invoice;
    public $newStatus;

    public function __construct(Invoices $invoice, int $newStatus)
    {
        $this->invoice   = $invoice;
        $this->newStatus = $newStatus;
    }
}
