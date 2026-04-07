<?php

namespace App\Events;

use App\Models\Workflow\Quotes;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuoteCreated
{
    use Dispatchable, SerializesModels;

    public $quote;

    public function __construct(Quotes $quote)
    {
        $this->quote = $quote;
    }
}
