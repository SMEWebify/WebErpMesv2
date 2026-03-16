<?php

namespace App\Events;

use App\Models\Purchases\PurchaseReceipt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseReceiptCreated
{
    use Dispatchable, SerializesModels;

    public $purchaseReceipt;

    public function __construct(PurchaseReceipt $purchaseReceipt)
    {
        $this->purchaseReceipt = $purchaseReceipt;
    }
}
