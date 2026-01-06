<?php

namespace App\Console\Commands;

use App\Jobs\PushOrderToN2P;
use App\Models\Workflow\Orders;
use App\Services\N2P\N2PPayloadBuilder;
use App\Services\Settings\SettingsService;
use Illuminate\Console\Command;

class PushOrderToN2PCommand extends Command
{
    protected $signature = 'wem:n2p:push-order {orderId} {--sync : Execute immediately without queue}';

    protected $description = 'Push a specific order to Nest2Prod';

    public function handle(): int
    {
        $orderId = (int) $this->argument('orderId');

        if (!Orders::whereKey($orderId)->exists()) {
            $this->error("Order {$orderId} not found.");
            return self::FAILURE;
        }

        if ($this->option('sync')) {
            $this->info("Pushing order {$orderId} to Nest2Prod synchronously...");
            (new PushOrderToN2P($orderId))->handle(
                app(SettingsService::class),
                app(N2PPayloadBuilder::class)
            );
        } else {
            PushOrderToN2P::dispatch($orderId);
            $this->info("Order {$orderId} queued for push.");
        }

        return self::SUCCESS;
    }
}
