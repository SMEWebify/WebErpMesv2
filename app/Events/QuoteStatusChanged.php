<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuoteStatusChanged
{
    use Dispatchable, SerializesModels;

    public $quoteId;
    public $newStatus;

    public function __construct($quoteId, $newStatus)
    {
        $this->quoteId = $quoteId;
        $this->newStatus = $newStatus;
    }
}
