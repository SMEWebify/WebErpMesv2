<?php

namespace App\Listeners;

use App\Models\Workflow\Quotes;
use App\Events\QuoteStatusChanged;
use App\Models\Workflow\Opportunities;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateOpportunityStatus
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(QuoteStatusChanged $event)
    {
        $quote = Quotes::find($event->quoteId);

        if ($quote && $quote->opportunities_id) {
            $opportunity = Opportunities::find($quote->opportunities_id);

            if ($opportunity) {
                if ($event->newStatus == 3) { // Win
                    $opportunity->statu = 4; // Closed-won
                } elseif ($event->newStatus == 4) { // Lost
                    $opportunity->statu = 5; // Closed-lost
                }

                $opportunity->save();
            }
        }
    }
}
