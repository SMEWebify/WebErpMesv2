<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Companies\Companies;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateCompanyStatusForOrder implements ShouldQueue
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
     * @param  \App\Events\OrderCreated  $event
     * @return void
     */
    public function handle(OrderCreated $event)
    {
        // statu_customer : 1 = Inactif, 2 = Actif, 3 = Prospect.
        // Une commande fait de la société un client actif.
        Companies::where('id', $event->order->companies_id)->update(['statu_customer' => 2]);
    }
}
