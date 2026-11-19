<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\OrderLineDetails;

class OrderLinesTableSeeder extends Seeder
{
    /**
     * De 1 à 7 lignes par commande (environ 2000 pour 500 commandes), numérotées
     * 10, 20, 30… L'avancement de livraison et de facturation suit le statut de
     * la commande. Aucun BL ni facture n'est créé : seules les quantités le disent.
     *
     * @return void
     */
    public function run()
    {
        foreach (Orders::query()->get(['id', 'statu', 'validity_date']) as $order) {
            $count = random_int(1, 7);

            for ($i = 1; $i <= $count; $i++) {
                $line = OrderLines::factory()->create([
                    'orders_id' => $order->id,
                    'ordre' => $i * 10,
                    'internal_delay' => $order->validity_date,
                    'delivery_date' => $order->validity_date,
                ]);

                $this->applyProgress($line, (int) $order->statu, $i === 1);

                OrderLineDetails::factory()->create(['order_lines_id' => $line->id]);
            }
        }
    }

    /**
     * Quantités livrées / facturées de la ligne selon le statut de la commande.
     * Écrit sans événement : seules ces colonnes changent (pas de date, donc
     * rien à historiser).
     */
    private function applyProgress(OrderLines $line, int $orderStatu, bool $firstLine): void
    {
        $qty = (float) $line->qty;

        $delivered = match ($orderStatu) {
            3 => $qty,
            // Livrée en partie : la première ligne est livrée, les autres au hasard.
            4 => $firstLine ? $qty : fake()->randomElement([$qty, $qty, floor($qty / 2), 0]),
            default => 0,
        };
        // Seules les commandes entièrement livrées sont facturées.
        $invoiced = $orderStatu === 3 ? $qty : 0;

        $line->forceFill([
            'delivered_qty' => $delivered,
            'delivered_remaining_qty' => $qty - $delivered,
            'delivery_status' => $delivered <= 0 ? 1 : ($delivered < $qty ? 2 : 3),
            'invoiced_qty' => $invoiced,
            'invoiced_remaining_qty' => $qty - $invoiced,
            'invoice_status' => $invoiced <= 0 ? 1 : ($invoiced < $qty ? 2 : 3),
        ])->saveQuietly();
    }
}
