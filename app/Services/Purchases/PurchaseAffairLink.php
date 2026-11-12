<?php

namespace App\Services\Purchases;

use App\Models\Planning\Task;
use App\Models\Purchases\Purchases;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use Illuminate\Validation\ValidationException;

/**
 * Rattachement d'un achat (commande d'achat ou demande de prix) à une affaire
 * sans passer par une tâche : opportunité, devis ou commande client.
 *
 * Un seul des trois est renseigné. Le formulaire envoie une valeur unique
 * « type:id » (`order:12`, `quote:7`, `opportunity:3`, vide = aucun).
 * Le dossier d'affaire (AffairScope) reprend les achats ainsi rattachés.
 */
class PurchaseAffairLink
{
    public const COLUMNS = [
        'opportunity' => 'opportunities_id',
        'quote'       => 'quotes_id',
        'order'       => 'orders_id',
    ];

    /** Nombre de documents proposés par type dans la liste. */
    private const OPTIONS_LIMIT = 300;

    /**
     * Colonnes à écrire pour une valeur « type:id », toutes à null si vide.
     *
     * @throws ValidationException valeur mal formée ou document introuvable
     */
    public function attributes(?string $value, string $field = 'affair_link'): array
    {
        $attributes = array_fill_keys(array_values(self::COLUMNS), null);

        if ($value === null || trim($value) === '') {
            return $attributes;
        }

        [$type, $id] = array_pad(explode(':', $value, 2), 2, null);
        $id = ctype_digit((string) $id) ? (int) $id : 0;

        if (! isset(self::COLUMNS[$type]) || $id <= 0 || ! $this->exists($type, $id)) {
            throw ValidationException::withMessages([$field => __('purchase_affair.invalid')]);
        }

        $attributes[self::COLUMNS[$type]] = $id;

        return $attributes;
    }

    /** Valeur « type:id » du document, vide s'il n'est pas rattaché. */
    public function value(Purchases|PurchasesQuotation $document): string
    {
        foreach (self::COLUMNS as $type => $column) {
            if ($document->{$column}) {
                return $type . ':' . $document->{$column};
            }
        }

        return '';
    }

    /**
     * Libellé et lien du document rattaché, null s'il n'y en a pas.
     *
     * @return array{type: string, label: string, url: string}|null
     */
    public function describe(Purchases|PurchasesQuotation $document): ?array
    {
        if ($document->orders_id && ($order = Orders::find($document->orders_id))) {
            return ['type' => 'order', 'label' => $order->code . ' - ' . $order->label, 'url' => route('orders.show', ['id' => $order->id])];
        }
        if ($document->quotes_id && ($quote = Quotes::withTemplates()->find($document->quotes_id))) {
            return ['type' => 'quote', 'label' => $quote->code . ' - ' . $quote->label, 'url' => route('quotes.show', ['id' => $quote->id])];
        }
        if ($document->opportunities_id && ($opportunity = Opportunities::find($document->opportunities_id))) {
            return ['type' => 'opportunity', 'label' => $opportunity->label, 'url' => route('opportunities.show', ['id' => $opportunity->id])];
        }

        return null;
    }

    /**
     * Rattachement déduit des tâches d'une demande d'achat : la commande (ou le
     * devis) quand toutes les tâches en viennent, sinon rien.
     *
     * @param int[] $taskIds
     */
    public function fromTasks(array $taskIds): array
    {
        $attributes = array_fill_keys(array_values(self::COLUMNS), null);
        if ($taskIds === []) {
            return $attributes;
        }

        $tasks = Task::query()
            ->whereIn('tasks.id', $taskIds)
            ->leftJoin('order_lines', 'order_lines.id', '=', 'tasks.order_lines_id')
            ->leftJoin('quote_lines', 'quote_lines.id', '=', 'tasks.quote_lines_id')
            ->toBase()
            ->get(['order_lines.orders_id', 'quote_lines.quotes_id']);

        $orders = $tasks->pluck('orders_id')->filter()->unique();
        $quotes = $tasks->pluck('quotes_id')->filter()->unique();

        if ($orders->count() === 1 && $tasks->every(fn ($t) => $t->orders_id)) {
            $attributes['orders_id'] = (int) $orders->first();
        } elseif ($quotes->count() === 1 && $tasks->every(fn ($t) => $t->quotes_id)) {
            $attributes['quotes_id'] = (int) $quotes->first();
        }

        return $attributes;
    }

    /**
     * Documents proposés dans la liste : commandes client actives, devis
     * ouverts / envoyés / gagnés, opportunités non perdues, plus le document
     * déjà rattaché s'il n'en fait plus partie.
     *
     * @return array<int, array{group: string, options: array<int, array{value: string, label: string}>}>
     */
    public function options(Purchases|PurchasesQuotation|null $document = null): array
    {
        $current = $document ? $this->value($document) : '';

        $orders = Orders::query()
            ->where('type', 1)
            ->whereNotIn('statu', [5, 6])
            ->orderByDesc('id')->limit(self::OPTIONS_LIMIT)
            ->get(['id', 'code', 'label'])
            ->map(fn ($o) => ['value' => 'order:' . $o->id, 'label' => $o->code . ' - ' . $o->label]);

        $quotes = Quotes::query()
            ->whereIn('statu', [1, 2, 3])
            ->orderByDesc('id')->limit(self::OPTIONS_LIMIT)
            ->get(['id', 'code', 'label'])
            ->map(fn ($q) => ['value' => 'quote:' . $q->id, 'label' => $q->code . ' - ' . $q->label]);

        $opportunities = Opportunities::query()
            ->where('statu', '!=', 5)
            ->orderByDesc('id')->limit(self::OPTIONS_LIMIT)
            ->get(['id', 'label'])
            ->map(fn ($o) => ['value' => 'opportunity:' . $o->id, 'label' => $o->label]);

        $groups = [
            'order'       => ['group' => __('purchase_affair.group_orders'), 'options' => $orders->all()],
            'quote'       => ['group' => __('purchase_affair.group_quotes'), 'options' => $quotes->all()],
            'opportunity' => ['group' => __('purchase_affair.group_opportunities'), 'options' => $opportunities->all()],
        ];

        // Le document rattaché reste sélectionnable même clos ou hors limite.
        if ($current !== '' && ($described = $this->describe($document))) {
            $present = collect($groups[$described['type']]['options'])->contains('value', $current);
            if (! $present) {
                array_unshift($groups[$described['type']]['options'], ['value' => $current, 'label' => $described['label']]);
            }
        }

        return array_values(array_filter($groups, fn ($g) => $g['options'] !== []));
    }

    private function exists(string $type, int $id): bool
    {
        return match ($type) {
            'order'       => Orders::whereKey($id)->exists(),
            'quote'       => Quotes::whereKey($id)->exists(),
            'opportunity' => Opportunities::whereKey($id)->exists(),
        };
    }
}
