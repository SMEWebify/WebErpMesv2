<?php

namespace App\Listeners;

use App\Events\QuoteCreated;
use App\Models\Companies\Companies;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateCompanyStatus implements ShouldQueue
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
     *
     * @param  \App\Events\QuoteCreated  $event
     * @return void
     */
    public function handle(QuoteCreated $event)
    {
        // statu_customer : 1 = Inactif, 2 = Actif, 3 = Prospect.
        // Un devis fait d'une société un prospect, sans rétrograder un client actif.
        Companies::where('id', $event->quote->companies_id)
            ->where(fn ($q) => $q->whereNull('statu_customer')->orWhere('statu_customer', '!=', 2))
            ->update(['statu_customer' => 3]);
    }
}
