<?php

namespace App\Listeners;

use App\Events\QuoteCreated;
use App\Models\Companies\Companies;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateCompanyStatus
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
        Companies::where('id', $event->quote->companies_id)->update(['statu_customer' => 2]);
    }
}
