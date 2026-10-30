<?php

namespace App\Console\Commands;

use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Workflow\OrderLineCalculatedPrice;
use Illuminate\Console\Command;

/**
 * Liste les lignes de commande encore en prix calculé (`use_calculated_price`).
 *
 * Leur prix est relu sur la gamme à chaque lecture : une modification de la gamme
 * le change, même sur une commande validée. Elles ne sont **pas** modifiées par la
 * mise en place du prix figé (OrderLineCalculatedPrice) : cette commande ne fait
 * que les signaler, avec le prix stocké et le prix calculé du moment. Pour figer
 * une ligne, désactiver le prix calculé depuis l'écran des lignes de commande.
 *
 * Lecture seule : aucune écriture, quelle que soit l'option.
 */
class ListCalculatedPriceOrderLines extends Command
{
    protected $signature = 'wem:orders:calculated-price-lines {--all : Include lines of orders still in preparation}';

    protected $description = 'List order lines still using the calculated price (read-only, nothing is changed)';

    public function handle(): int
    {
        $rows = [];

        OrderLines::query()
            ->where('use_calculated_price', true)
            ->with(['order:id,code,statu,review_decision'])
            ->orderBy('orders_id')
            ->orderBy('ordre')
            ->chunkById(200, function ($lines) use (&$rows) {
                foreach ($lines as $line) {
                    /** @var Orders|null $order */
                    $order = $line->order;
                    $inPreparation = $order && OrderLineCalculatedPrice::isInPreparation($order);

                    if ($inPreparation && ! $this->option('all')) {
                        continue;
                    }

                    $stored = (float) ($line->getRawOriginal('selling_price') ?? 0);
                    $computed = round((float) $line->selling_price, 3);

                    $rows[] = [
                        $order?->code ?? '#' . $line->orders_id,
                        $order?->statu,
                        $order?->review_decision ?? '-',
                        $line->code ?: '#' . $line->id,
                        number_format($stored, 3, '.', ''),
                        number_format($computed, 3, '.', ''),
                        abs($stored - $computed) < 0.0005 ? '' : '≠',
                        OrderLineCalculatedPrice::isShipped($line) ? 'oui' : '',
                    ];
                }
            });

        if ($rows === []) {
            $this->info('Aucune ligne de commande validée en prix calculé.');

            return self::SUCCESS;
        }

        $this->warn(count($rows) . ' ligne(s) en prix calculé : leur prix suit la gamme. Rien n\'a été modifié.');
        $this->table(
            ['Commande', 'Statut', 'Revue', 'Ligne', 'Prix stocké', 'Prix calculé', 'Écart', 'Livrée/facturée'],
            $rows
        );
        $this->line('Pour figer une ligne : désactiver « prix calculé » dans l\'écran des lignes de la commande.');

        return self::SUCCESS;
    }
}
