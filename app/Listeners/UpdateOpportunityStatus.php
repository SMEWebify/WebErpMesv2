<?php

namespace App\Listeners;

use App\Events\QuoteStatusChanged;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\Opportunities;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateOpportunityStatus implements ShouldQueue
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
        $quote = $event->quote;

        if ($quote && $quote->opportunities_id) {
            $opportunity = Opportunities::find($quote->opportunities_id);

            if ($opportunity) {
                if ($event->newStatus == 3) { // Win
                    $opportunity->statu = 4; // Closed-won
                } elseif ($event->newStatus == 4) { // Lost
                    $opportunity->statu = 5; // Closed-lost

                    // Le motif saisi sur le devis vaut pour l'opportunité, sauf si elle a déjà le sien.
                    if ($opportunity->loss_reason_id === null) {
                        $lost = Quotes::whereKey($quote->id)->first(['loss_reason_id', 'loss_comment']);
                        $opportunity->loss_reason_id = $lost?->loss_reason_id;
                        $opportunity->loss_comment   = $opportunity->loss_comment ?? $lost?->loss_comment;
                    }
                }

                $opportunity->save();
            }
        }
    }
}
