<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderConfirmations;
use App\Models\Workflow\OrderConfirmationLines;
use App\Services\DocumentCodeGenerator;
use RuntimeException;

/**
 * Cycle de vie de l'ARC.
 *
 * Modèle « copie fidèle » : l'ARC est une photo de la commande. Tant qu'il est en
 * cours, on peut reprendre la photo ; une fois envoyé il est figé et toute
 * modification de la commande passe par un nouvel indice.
 *
 * Les lignes de commande restent la vérité opérationnelle : production, achats,
 * livraison et facturation ne lisent jamais les lignes d'ARC.
 */
class OrderConfirmationService
{
    protected $documentCodeGenerator;

    public function __construct(DocumentCodeGenerator $documentCodeGenerator)
    {
        $this->documentCodeGenerator = $documentCodeGenerator;
    }

    /**
     * Crée l'ARC en cours d'une commande, ou resynchronise celui qui existe déjà.
     *
     * Appelé quand la revue de commande est approuvée. S'il reste un ARC au statut
     * « en cours », il est repris depuis la commande plutôt que dupliqué : deux
     * brouillons concurrents pour une même commande n'ont pas de sens.
     *
     * @param Orders $order
     * @param int|null $userId Émetteur, à défaut le chargé d'affaires de la commande
     * @return OrderConfirmations
     */
    public function createFromOrder(Orders $order, ?int $userId = null): OrderConfirmations
    {
        return DB::transaction(function () use ($order, $userId) {
            $draft = OrderConfirmations::where('order_id', $order->id)
                ->where('statu', OrderConfirmations::STATUS_IN_PROGRESS)
                ->first();

            if ($draft) {
                $draft->update($this->headerAttributes($order, $userId));
                $this->copyLines($order, $draft);

                return $draft->refresh();
            }

            $lastConfirmation = OrderConfirmations::orderBy('id', 'desc')->first();

            $confirmation = OrderConfirmations::create(array_merge(
                $this->headerAttributes($order, $userId),
                [
                    'uuid'          => Str::uuid(),
                    'code'          => $this->documentCodeGenerator->generateDocumentCode('order-confirmation', $lastConfirmation?->id ?? 0),
                    'order_id'      => $order->id,
                    'revision'      => $this->nextRevision($order),
                    'statu'         => OrderConfirmations::STATUS_IN_PROGRESS,
                    'is_current'    => false,
                    'supersedes_id' => $this->currentFor($order)?->id,
                    'issued_at'     => now(),
                ]
            ));

            $this->copyLines($order, $confirmation);

            return $confirmation->refresh();
        });
    }

    /**
     * Fige l'ARC et le rend opposable.
     *
     * L'indice précédent bascule en « remplacé » et perd le drapeau courant : à
     * tout instant une commande a au plus un ARC qui fait foi.
     *
     * @param OrderConfirmations $confirmation
     * @return OrderConfirmations
     *
     * @throws RuntimeException si l'ARC a déjà été envoyé
     */
    public function send(OrderConfirmations $confirmation): OrderConfirmations
    {
        if (!$confirmation->isEditable()) {
            throw new RuntimeException("L'ARC {$confirmation->code} a déjà été envoyé.");
        }

        return DB::transaction(function () use ($confirmation) {
            OrderConfirmations::where('order_id', $confirmation->order_id)
                ->where('id', '!=', $confirmation->id)
                ->where('is_current', true)
                ->update([
                    'is_current' => false,
                    'statu'      => OrderConfirmations::STATUS_SUPERSEDED,
                ]);

            $confirmation->update([
                'statu'      => OrderConfirmations::STATUS_SENT,
                'is_current' => true,
                'sent_at'    => now(),
            ]);

            return $confirmation->refresh();
        });
    }

    /**
     * Enregistre l'acceptation du client.
     *
     * @param OrderConfirmations $confirmation
     * @return OrderConfirmations
     */
    public function markAccepted(OrderConfirmations $confirmation): OrderConfirmations
    {
        if ((int) $confirmation->statu !== OrderConfirmations::STATUS_SENT) {
            throw new RuntimeException("L'ARC {$confirmation->code} n'est pas au statut envoyé.");
        }

        $confirmation->update([
            'statu'                => OrderConfirmations::STATUS_ACCEPTED,
            'customer_accepted_at' => now(),
        ]);

        return $confirmation->refresh();
    }

    /**
     * Écarts entre la commande courante et l'ARC qui fait foi.
     *
     * C'est ce qui permet d'afficher « commande modifiée depuis l'ARC indice A,
     * avenant à émettre ». Retourne un tableau vide si aucun ARC n'engage encore
     * l'entreprise ou si la commande est conforme au dernier ARC envoyé.
     *
     * @param Orders $order
     * @return array{added: array, removed: array, modified: array}
     */
    public function diffWithOrder(Orders $order): array
    {
        $empty = ['added' => [], 'removed' => [], 'modified' => []];

        $current = $this->currentFor($order);

        if (!$current) {
            return $empty;
        }

        $confirmedLines = $current->OrderConfirmationLines->keyBy('order_line_id');
        $orderLines     = $order->OrderLines->keyBy('id');

        $added = $orderLines->keys()
            ->diff($confirmedLines->keys())
            ->map(fn ($id) => [
                'order_line_id' => $id,
                'label'         => $orderLines[$id]->label,
            ])->values()->all();

        // Lignes gelées dont la ligne de commande a disparu — order_line_id passe à
        // null quand la ligne est supprimée, ces lignes-là sont donc comptées aussi.
        $removed = $current->OrderConfirmationLines
            ->filter(fn ($line) => $line->order_line_id === null || !$orderLines->has($line->order_line_id))
            ->map(fn ($line) => [
                'order_line_id' => $line->order_line_id,
                'label'         => $line->label,
            ])->values()->all();

        $modified = [];

        foreach ($orderLines as $id => $orderLine) {
            $confirmedLine = $confirmedLines->get($id);

            if (!$confirmedLine) {
                continue;
            }

            $changes = $this->lineChanges($confirmedLine, $orderLine);

            if ($changes) {
                $modified[] = [
                    'order_line_id' => $id,
                    'label'         => $orderLine->label,
                    'changes'       => $changes,
                ];
            }
        }

        return ['added' => $added, 'removed' => $removed, 'modified' => $modified];
    }

    /**
     * @param Orders $order
     * @return bool
     */
    public function hasDiverged(Orders $order): bool
    {
        $diff = $this->diffWithOrder($order);

        return (bool) ($diff['added'] || $diff['removed'] || $diff['modified']);
    }

    /**
     * ARC qui fait foi pour une commande.
     *
     * @param Orders $order
     * @return OrderConfirmations|null
     */
    public function currentFor(Orders $order): ?OrderConfirmations
    {
        return OrderConfirmations::where('order_id', $order->id)
            ->where('is_current', true)
            ->first();
    }

    /**
     * Champs d'en-tête recopiés depuis la commande.
     *
     * @param Orders $order
     * @param int|null $userId
     * @return array
     */
    private function headerAttributes(Orders $order, ?int $userId = null): array
    {
        return [
            'label'                            => $order->label,
            'customer_reference'               => $order->customer_reference,
            'companies_id'                     => $order->companies_id,
            'companies_contacts_id'            => $order->companies_contacts_id,
            'companies_addresses_id'           => $order->companies_addresses_id,
            'accounting_payment_conditions_id' => $order->accounting_payment_conditions_id,
            'accounting_payment_methods_id'    => $order->accounting_payment_methods_id,
            'accounting_deliveries_id'         => $order->accounting_deliveries_id,
            'validity_date'                    => $order->validity_date,
            'user_id'                          => $userId ?? $order->user_id,
        ];
    }

    /**
     * Recopie les lignes de commande en valeurs figées.
     *
     * selling_price passe par l'accesseur d'OrderLines : c'est le prix effectif,
     * calculé ou saisi, qui doit être gelé.
     *
     * @param Orders $order
     * @param OrderConfirmations $confirmation
     * @return void
     */
    private function copyLines(Orders $order, OrderConfirmations $confirmation): void
    {
        OrderConfirmationLines::where('order_confirmation_id', $confirmation->id)->delete();

        foreach ($order->OrderLines as $line) {
            OrderConfirmationLines::create([
                'order_confirmation_id' => $confirmation->id,
                'order_line_id'         => $line->id,
                'ordre'                 => $line->ordre,
                'code'                  => $line->code,
                'label'                 => $line->label,
                'qty'                   => $line->qty,
                'methods_units_id'      => $line->methods_units_id,
                'unit_label'            => optional($line->Unit)->label,
                'selling_price'         => $line->selling_price,
                'discount'              => $line->discount ?? 0,
                'accounting_vats_id'    => $line->accounting_vats_id,
                'vat_rate'              => optional($line->VAT)['rate'] ?? 0,
                'delivery_date'         => $line->delivery_date,
            ]);
        }
    }

    /**
     * Indice suivant pour une commande : A, B, C... puis AA au-delà de Z.
     *
     * @param Orders $order
     * @return string
     */
    private function nextRevision(Orders $order): string
    {
        $last = OrderConfirmations::withTrashed()
            ->where('order_id', $order->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$last) {
            return 'A';
        }

        $revision = $last->revision;
        $revision++; // L'incrément de chaîne PHP donne A→B et Z→AA

        return $revision;
    }

    /**
     * Champs contractuels qui ont bougé entre la ligne gelée et la ligne courante.
     *
     * @param \App\Models\Workflow\OrderConfirmationLines $confirmedLine
     * @param \App\Models\Workflow\OrderLines $orderLine
     * @return array
     */
    private function lineChanges($confirmedLine, $orderLine): array
    {
        $changes = [];

        $numericFields = [
            'qty'           => 'qty',
            'selling_price' => 'selling_price',
            'discount'      => 'discount',
        ];

        foreach ($numericFields as $field => $orderField) {
            $before = (float) $confirmedLine->{$field};
            $after  = (float) $orderLine->{$orderField};

            if (abs($before - $after) > 0.0001) {
                $changes[$field] = ['before' => $before, 'after' => $after];
            }
        }

        // delivery_date est casté côté ARC, brut côté commande : on normalise les deux.
        $beforeDate = $confirmedLine->delivery_date ? Carbon::parse($confirmedLine->delivery_date)->toDateString() : null;
        $afterDate  = $orderLine->delivery_date ? Carbon::parse($orderLine->delivery_date)->toDateString() : null;

        if ($beforeDate !== $afterDate) {
            $changes['delivery_date'] = ['before' => $beforeDate, 'after' => $afterDate];
        }

        if ((string) $confirmedLine->label !== (string) $orderLine->label) {
            $changes['label'] = ['before' => $confirmedLine->label, 'after' => $orderLine->label];
        }

        return $changes;
    }
}
