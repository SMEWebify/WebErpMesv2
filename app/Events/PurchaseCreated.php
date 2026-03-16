<?php

namespace App\Events;

use App\Models\Purchases\PurchasesQuotation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseCreated
{
    use Dispatchable, SerializesModels;

    public $purchasesQuotation;

    public function __construct(PurchasesQuotation $purchasesQuotation)
    {
        $this->purchasesQuotation = $purchasesQuotation;
    }
}
