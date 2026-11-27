<?php

namespace App\Services\Workflow;

use App\Models\Accounting\AccountingVat;
use App\Models\Methods\MethodsUnits;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Dictée d'une commande : même brouillon que la dictée de devis
 * (QuoteDictation::propose, rien n'est écrit), lignes validées créées en fin
 * de commande et marquées ai_proposed_at.
 *
 * Seulement sur une commande client ouverte (statut 1) qui n'a encore aucun
 * ARC : une fois l'ARC préparé, une ligne ajoutée doit passer par un avenant.
 */
class OrderDictation
{
    /** Motif de refus affichable, null si la dictée est permise. */
    public function refusal(Orders $order): ?string
    {
        if ((int) $order->type === 2) {
            return __('quote_dictation.order_refused_internal');
        }
        if ((int) $order->statu !== 1) {
            return __('quote_dictation.order_refused_status');
        }
        if ($order->OrderConfirmations()->exists()) {
            return __('quote_dictation.order_refused_confirmation');
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $lines  déjà validées (contrôleur)
     * @return Collection<int, OrderLines>
     */
    public function create(Orders $order, array $lines): Collection
    {
        [$defaultVat, $defaultUnit] = $this->defaults();

        return DB::transaction(function () use ($order, $lines, $defaultVat, $defaultUnit) {
            $ordre = (int) (OrderLines::where('orders_id', $order->id)->max('ordre') ?? 0);
            $now = now();

            return collect($lines)->map(function (array $data) use ($order, &$ordre, $defaultVat, $defaultUnit, $now) {
                $line = new OrderLines([
                    'orders_id' => $order->id,
                    'ordre' => ++$ordre,
                    'code' => '',
                    'label' => $data['label'],
                    'qty' => $data['qty'],
                    'delivered_remaining_qty' => $data['qty'],
                    'invoiced_remaining_qty' => $data['qty'],
                    'methods_units_id' => $data['methods_units_id'] ?? $defaultUnit->id,
                    'selling_price' => $data['selling_price'] ?? 0,
                    'discount' => 0,
                    'accounting_vats_id' => $defaultVat->id,
                ]);
                $line->ai_proposed_at = $now;
                $line->save();

                OrderLineDetails::create([
                    'order_lines_id' => $line->id,
                    'material' => $data['material'] ?? null,
                    'internal_comment' => $data['internal_comment'] ?? null,
                ]);

                return $line;
            });
        });
    }

    /** Montant TTC ajouté à la commande (plafond d'encours), TVA par défaut. */
    public function ttc(array $lines): float
    {
        [$defaultVat] = $this->defaults();
        $rate = (float) $defaultVat->rate;

        return collect($lines)->sum(fn (array $l) => (float) $l['qty'] * (float) ($l['selling_price'] ?? 0)) * (1 + $rate / 100);
    }

    /** @return array{0: AccountingVat, 1: MethodsUnits} */
    private function defaults(): array
    {
        $defaultVat = AccountingVat::getDefault();
        $defaultUnit = MethodsUnits::getDefault();

        if (! $defaultVat || ! $defaultUnit) {
            throw new RuntimeException(__('imports.no_line_defaults'));
        }

        return [$defaultVat, $defaultUnit];
    }
}
