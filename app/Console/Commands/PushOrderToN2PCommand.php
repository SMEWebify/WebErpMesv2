<?php

namespace App\Console\Commands;

use App\Jobs\PushOrderToN2P;
use App\Models\Integrations\IntegrationEndpoint;
use App\Models\Workflow\Orders;
use App\Services\N2P\N2PPayloadBuilder;
use Illuminate\Console\Command;

class PushOrderToN2PCommand extends Command
{
    protected $signature = 'wem:n2p:push-order
        {orderId? : Order to push}
        {--open : Push every order already sent to Nest2Prod and not fully delivered}
        {--dry-run : With --open, list the orders without pushing}
        {--force : With --open, skip the confirmation}
        {--sync : Execute immediately without queue}';

    protected $description = 'Push an order (or every open order with --open) to Nest2Prod, with its tasks';

    /** Commande partiellement livrée : ses OF restants sont toujours au MES. */
    private const PARTLY_DELIVERED = 4;

    public function handle(): int
    {
        if ($this->option('open')) {
            return $this->pushOpenOrders();
        }

        $orderId = (int) $this->argument('orderId');

        if (!Orders::whereKey($orderId)->exists()) {
            $this->error("Order {$orderId} not found.");
            return self::FAILURE;
        }

        $this->push($orderId);

        return self::SUCCESS;
    }

    /**
     * Renvoi groupé (alignement des rangs seq avec le MES, docs/pointage-scan.md) :
     * commandes au statut qui déclenche l'envoi à N2P, ou partiellement livrées.
     * Une commande encore ouverte n'a jamais été envoyée : elle n'est pas poussée.
     */
    private function pushOpenOrders(): int
    {
        $endpoint = IntegrationEndpoint::query()->forSystem('n2p')->outbound()->active()->first();
        if (! $endpoint) {
            $this->error('No active Nest2Prod outbound endpoint.');
            return self::FAILURE;
        }

        $sentStatus = $this->statusCode($endpoint->meta(IntegrationEndpoint::META_STATUS_TRANSITION_TO));
        $orders = Orders::query()
            ->whereIn('statu', array_values(array_filter([$sentStatus, self::PARTLY_DELIVERED])))
            ->orderBy('id')
            ->get(['id', 'code', 'statu']);

        if ($orders->isEmpty()) {
            $this->info('No open order to push.');
            return self::SUCCESS;
        }

        $this->table(['id', 'code', 'statu'], $orders->map->only(['id', 'code', 'statu'])->all());

        if ($this->option('dry-run')) {
            $this->info("{$orders->count()} order(s) would be pushed.");
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(
            "Push {$orders->count()} order(s) with their tasks? Only once the MES keeps executed tasks on a resend.",
        )) {
            return self::FAILURE;
        }

        foreach ($orders as $order) {
            $this->push((int) $order->id);
        }

        return self::SUCCESS;
    }

    private function push(int $orderId): void
    {
        if ($this->option('sync')) {
            $this->info("Pushing order {$orderId} to Nest2Prod synchronously...");
            (new PushOrderToN2P($orderId))->handle(app(N2PPayloadBuilder::class));
        } else {
            PushOrderToN2P::dispatch($orderId);
            $this->info("Order {$orderId} queued for push.");
        }
    }

    /** Même lecture que OrdersObserver : code numérique ou libellé (IN_PROGRESS…). */
    private function statusCode(mixed $status): ?int
    {
        if (is_numeric($status)) {
            return (int) $status;
        }

        return [
            'OPEN' => 1,
            'IN_PROGRESS' => 2,
            'DELIVERED' => 3,
            'PARTLY_DELIVERED' => 4,
            'STOPPED' => 5,
            'CANCELED' => 6,
        ][strtoupper((string) $status)] ?? null;
    }
}
