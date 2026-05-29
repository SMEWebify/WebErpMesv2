<?php

namespace App\Services\Integrations\Pdp;

use App\Services\Integrations\Pdp\Contracts\PdpGateway;

/**
 * Registre et résolveur des drivers PDP. Le driver par défaut est défini par
 * config('services.pdp.default'). Pour brancher une nouvelle PDP, il suffit
 * d'enregistrer son driver via extend() (cf. AppServiceProvider).
 */
class PdpManager
{
    /** @var array<string, PdpGateway> */
    private array $drivers = [];

    public function extend(string $key, PdpGateway $gateway): void
    {
        $this->drivers[$key] = $gateway;
    }

    public function driver(?string $key = null): PdpGateway
    {
        $key ??= (string) config('services.pdp.default', 'qonto');

        if (! isset($this->drivers[$key])) {
            throw new \InvalidArgumentException("PDP driver [{$key}] not registered.");
        }

        return $this->drivers[$key];
    }

    /** Clés des drivers enregistrés. */
    public function available(): array
    {
        return array_keys($this->drivers);
    }

    /** Le driver (par défaut ou donné) est-il configuré/activé ? */
    public function isEnabled(?string $key = null): bool
    {
        try {
            return $this->driver($key)->isEnabled();
        } catch (\Throwable) {
            return false;
        }
    }
}
