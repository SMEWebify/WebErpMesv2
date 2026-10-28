<?php

namespace App\Services\Purchases;

use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseOrderQualityClause;
use App\Models\Purchases\PurchaseQualityClauseRule;
use App\Models\Purchases\Purchases;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Clauses qualité d'une commande d'achat.
 *
 * - Commande en cours (statu 1) : clauses recalculées à chaque lecture depuis la
 *   criticité des lignes et les règles actives, rien n'est écrit.
 * - Dès qu'elle sort de « en cours » : la copie est écrite une fois
 *   (purchase_order_quality_clauses) et datée (quality_clauses_frozen_at). Le PDF
 *   lit cette copie : modifier ou désactiver une clause du référentiel ne change
 *   plus la commande émise.
 * - Repassée « en cours » : la copie est effacée, la commande sera de nouveau figée
 *   à sa prochaine émission (ses lignes ont pu changer).
 *
 * Les commandes émises avant la fonctionnalité sont figées sans clause par la
 * migration : leur PDF ne change pas.
 */
class PurchaseQualityClauses
{
    /**
     * Clauses de la commande, dans l'ordre d'impression.
     *
     * @return Collection<int, PurchaseOrderQualityClause>
     */
    public function forOrder(Purchases $purchase): Collection
    {
        if ($purchase->quality_clauses_frozen_at !== null) {
            return $this->stored($purchase);
        }

        // Sortie de « en cours » par un chemin qui ne passe pas par le modèle.
        if ((int) $purchase->statu !== 1) {
            return $this->freeze($purchase);
        }

        return $this->applicable($purchase);
    }

    /**
     * Clauses que les règles actives donnent aux lignes de la commande, non enregistrées.
     * Deux requêtes : lignes, règles avec leur clause.
     *
     * @return Collection<int, PurchaseOrderQualityClause>
     */
    public function applicable(Purchases $purchase): Collection
    {
        $criticalities = PurchaseLines::query()
            ->where('purchases_id', $purchase->getKey())
            ->where(fn ($q) => $q->whereNotNull('certificate_type')
                ->orWhereNotNull('construction_code')
                ->orWhereNotNull('nuclear_level'))
            ->get(PurchaseCriticality::FIELDS)
            ->map(fn ($line) => PurchaseCriticality::of($line))
            ->unique(fn ($c) => implode('|', $c))
            ->values();

        if ($criticalities->isEmpty()) {
            return collect();
        }

        return PurchaseQualityClauseRule::query()
            ->with('clause')
            ->whereHas('clause', fn ($q) => $q->where('active', true))
            ->get()
            ->filter(fn (PurchaseQualityClauseRule $rule) => $criticalities->contains(fn ($c) => $rule->matches($c)))
            ->map->clause
            ->unique('id')
            ->sortBy([['ordre', 'asc'], ['code', 'asc']])
            ->values()
            ->map(fn ($clause) => new PurchaseOrderQualityClause([
                'purchases_id'               => $purchase->getKey(),
                'purchase_quality_clause_id' => $clause->id,
                'code'                       => $clause->code,
                'label'                      => $clause->label,
                'text_fr'                    => $clause->text_fr,
                'text_en'                    => $clause->text_en,
                'ordre'                      => $clause->ordre,
            ]));
    }

    /**
     * Écrit la copie des clauses applicables et date la commande. Sans effet sur une
     * commande déjà figée.
     *
     * N'enregistre jamais le modèle reçu (le PDF lui ajoute un attribut Lines) :
     * la date est écrite par une requête, puis reportée sur le modèle en mémoire.
     *
     * @return Collection<int, PurchaseOrderQualityClause>
     */
    public function freeze(Purchases $purchase): Collection
    {
        return DB::transaction(function () use ($purchase) {
            $frozenAt = Purchases::query()->whereKey($purchase->getKey())->lockForUpdate()->value('quality_clauses_frozen_at');

            if ($frozenAt === null) {
                $now = now();
                PurchaseOrderQualityClause::where('purchases_id', $purchase->getKey())->delete();
                $this->applicable($purchase)->each->save();
                Purchases::query()->whereKey($purchase->getKey())->update(['quality_clauses_frozen_at' => $now]);
                $frozenAt = $now;
            }

            $purchase->setAttribute('quality_clauses_frozen_at', $frozenAt);
            $purchase->syncOriginalAttribute('quality_clauses_frozen_at');

            return $this->stored($purchase);
        });
    }

    /** Commande rouverte : copie effacée, recalcul jusqu'à la prochaine émission. */
    public function unfreeze(Purchases $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            PurchaseOrderQualityClause::where('purchases_id', $purchase->getKey())->delete();
            Purchases::query()->whereKey($purchase->getKey())->update(['quality_clauses_frozen_at' => null]);
        });

        $purchase->setAttribute('quality_clauses_frozen_at', null);
        $purchase->syncOriginalAttribute('quality_clauses_frozen_at');
    }

    /**
     * Ajoute le bloc des clauses en fin de document : avant la dernière balise
     * </main>, sinon avant </body>. Sans clause, le HTML est rendu tel quel —
     * vue standard comme vue print/custom.
     */
    public function appendToHtml(string $html, Purchases $purchase): string
    {
        $clauses = $this->forOrder($purchase);

        if ($clauses->isEmpty()) {
            return $html;
        }

        $lines = PurchaseLines::query()
            ->where('purchases_id', $purchase->getKey())
            ->where(fn ($q) => $q->whereNotNull('certificate_type')
                ->orWhereNotNull('construction_code')
                ->orWhereNotNull('nuclear_level'))
            ->orderBy('ordre')
            ->get(array_merge(['id', 'ordre', 'code', 'label'], PurchaseCriticality::FIELDS));

        $block = view('print.partials.purchase-quality-clauses', compact('clauses', 'lines'))->render();

        foreach (['</main>', '</body>'] as $tag) {
            $position = strripos($html, $tag);
            if ($position !== false) {
                return substr($html, 0, $position) . $block . substr($html, $position);
            }
        }

        return $html . $block;
    }

    /** @return Collection<int, PurchaseOrderQualityClause> */
    private function stored(Purchases $purchase): Collection
    {
        return PurchaseOrderQualityClause::where('purchases_id', $purchase->getKey())
            ->orderBy('ordre')
            ->orderBy('code')
            ->get();
    }
}
