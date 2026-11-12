<?php

namespace App\Services\Integrations\Nest2Bend;

use App\Models\Integrations\Nest2BendSetting;
use App\Services\Integrations\Nest2Bend\Contracts\Nest2BendGateway;
use App\Services\Integrations\Nest2Bend\Drivers\HttpNest2BendGateway;

/**
 * Construit le client NEST2BEND à partir de la liaison enregistrée.
 *
 * Lier une autre implémentation de cette classe dans le conteneur suffit pour simuler
 * NEST2BEND (par exemple /compte tant qu'il n'est pas livré).
 */
class Nest2BendGatewayFactory
{
    public function make(Nest2BendSetting $setting): Nest2BendGateway
    {
        return new HttpNest2BendGateway((string) $setting->base_url, (string) $setting->api_key);
    }
}
