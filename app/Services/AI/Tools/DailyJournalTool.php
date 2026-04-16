<?php

namespace App\Services\AI\Tools;

use App\Models\Purchases\PurchaseReceipt;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\Orders;
use Carbon\Carbon;

/**
 * Outil Journal quotidien.
 *
 * Agrège en une seule requête les événements de la veille :
 *  - Nouvelles commandes clients reçues
 *  - Bons de livraison créés/expédiés
 *  - Réceptions d'achat enregistrées
 */
class DailyJournalTool
{
    /**
     * @param  string|null  $date  Date cible au format YYYY-MM-DD (défaut : hier)
     */
    public function fetch(?string $date = null): array
    {
        $target = $date
            ? Carbon::parse($date)->startOfDay()
            : Carbon::yesterday()->startOfDay();

        $end = $target->copy()->endOfDay();

        return [
            'date'     => $target->locale('fr')->isoFormat('dddd D MMMM YYYY'),
            'orders'   => $this->newOrders($target, $end),
            'deliverys' => $this->deliverys($target, $end),
            'receipts' => $this->receipts($target, $end),
        ];
    }

    // ─── Nouvelles commandes ───────────────────────────────────────────────────

    private function newOrders(Carbon $start, Carbon $end): array
    {
        $rows = Orders::with(['companie:id,label', 'contact:id,first_name,name'])
            ->select(['id', 'code', 'label', 'statu', 'companies_id', 'companies_contacts_id', 'validity_date', 'created_at'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        if ($rows->isEmpty()) {
            return ['count' => 0, 'items' => []];
        }

        return [
            'count' => $rows->count(),
            'items' => $rows->map(fn ($o) => [
                'code'           => $o->code,
                'label'          => $o->label,
                'client'         => $o->companie?->label,
                'contact'        => $o->contact
                    ? trim($o->contact->first_name . ' ' . $o->contact->name)
                    : null,
                'validity_date'  => $o->validity_date
                    ? Carbon::parse($o->validity_date)->format('d/m/Y')
                    : null,
                'url'            => url("/workflow/orders/{$o->id}"),
            ])->toArray(),
        ];
    }

    // ─── Bons de livraison ─────────────────────────────────────────────────────

    private function deliverys(Carbon $start, Carbon $end): array
    {
        $rows = Deliverys::with(['companie:id,label', 'Orders:id,code'])
            ->select(['id', 'code', 'label', 'statu', 'companies_id', 'order_id', 'created_at'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        if ($rows->isEmpty()) {
            return ['count' => 0, 'items' => []];
        }

        $statuts = [
            1 => 'Brouillon',
            2 => 'Expédié',
            3 => 'Livré',
            4 => 'Annulé',
        ];

        return [
            'count' => $rows->count(),
            'items' => $rows->map(fn ($d) => [
                'code'    => $d->code,
                'label'   => $d->label,
                'statut'  => $statuts[$d->statu] ?? "Statut {$d->statu}",
                'client'  => $d->companie?->label,
                'commande' => $d->Orders?->code,
                'url'     => url("/deliverys/{$d->id}"),
            ])->toArray(),
        ];
    }

    // ─── Réceptions d'achat ────────────────────────────────────────────────────

    private function receipts(Carbon $start, Carbon $end): array
    {
        $rows = PurchaseReceipt::with(['companie:id,label'])
            ->select(['id', 'code', 'label', 'statu', 'companies_id', 'delivery_note_number', 'created_at'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();

        if ($rows->isEmpty()) {
            return ['count' => 0, 'items' => []];
        }

        $statuts = [
            1 => 'En attente',
            2 => 'Reçu',
            3 => 'Contrôlé',
            4 => 'Litige',
        ];

        return [
            'count' => $rows->count(),
            'items' => $rows->map(fn ($r) => [
                'code'                => $r->code,
                'label'               => $r->label,
                'statut'              => $statuts[$r->statu] ?? "Statut {$r->statu}",
                'fournisseur'         => $r->companie?->label,
                'delivery_note_number' => $r->delivery_note_number,
                'url'                 => url("/purchases/receipt/{$r->id}"),
            ])->toArray(),
        ];
    }

    // ─── Définition JSON Schema pour Claude ───────────────────────────────────

    public static function definition(): array
    {
        return [
            'name'        => 'get_daily_journal',
            'description' => 'Génère le journal quotidien de l\'ERP : nouvelles commandes clients reçues, bons de livraison créés, et réceptions d\'achat enregistrées. Par défaut retourne les données de la veille.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'date' => [
                        'type'        => 'string',
                        'description' => 'Date cible au format YYYY-MM-DD. Omettez pour obtenir les données de la veille.',
                    ],
                ],
                'required' => [],
            ],
        ];
    }
}
