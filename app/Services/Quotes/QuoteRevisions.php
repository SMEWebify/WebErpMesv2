<?php

namespace App\Services\Quotes;

use App\Events\QuoteStatusChanged;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Indices (A, B, C…) et variantes d'un devis.
 *
 * Une famille est à plat : le devis référent (parent_quote_id null) et tous les
 * devis qui pointent vers lui, indices comme variantes. Le référent n'a pas
 * d'indice stocké : il vaut « A ».
 *
 * Une « ligne » de la famille regroupe un devis et ses indices successifs ; elle
 * se reconnaît au code de base (code sans le suffixe d'indice) :
 *   QT-0001, QT-0001-B, QT-0001-C          ligne du référent
 *   QT-0001-V1, QT-0001-V1-B               ligne de la variante V1
 */
class QuoteRevisions
{
    public const STATU_OPEN     = 1;
    public const STATU_SENT     = 2;
    public const STATU_WON      = 3;
    public const STATU_CLOSED   = 5;
    public const STATU_OBSOLETE = 6;

    /** Indice implicite d'un devis qui n'en porte pas. */
    public const FIRST_REVISION = 'A';

    public function __construct(private readonly QuoteDuplicator $duplicator) {}

    /**
     * Nouvel indice : copie complète via QuoteDuplicator, code de base suffixé de
     * l'indice suivant, et le devis d'origine passe en « Obsolète » s'il était
     * ouvert ou envoyé (un devis gagné ou perdu garde son statut).
     */
    public function newRevision(Quotes $source): Quotes
    {
        $this->assertNotTemplate($source);

        $newer = $this->newerRevision($source);
        if ($newer) {
            throw new QuoteRevisionRefused(__('quote_revisions.refused_superseded', ['code' => $newer->code]));
        }

        return DB::transaction(function () use ($source) {
            $base     = $this->baseCode($source);
            $revision = $this->effectiveRevision($source);
            do {
                $revision = str_increment($revision);
                $code     = $base . '-' . $revision;
            } while ($this->codeExists($code));

            $quote = $this->duplicator->duplicate($source, [
                'code'            => $code,
                'parent_quote_id' => $this->rootId($source),
                'revision'        => $revision,
                'variant_label'   => $source->variant_label,
            ]);

            if (in_array((int) $source->statu, [self::STATU_OPEN, self::STATU_SENT], true)) {
                $this->changeStatus($source, self::STATU_OBSOLETE);
            }

            return $quote;
        });
    }

    /**
     * Variante : copie complète rattachée au même référent, avec son libellé.
     * Elle reste ouverte en parallèle ; le devis d'origine n'est pas touché.
     */
    public function newVariant(Quotes $source, string $label): Quotes
    {
        $this->assertNotTemplate($source);

        return DB::transaction(function () use ($source, $label) {
            $root = $this->root($source);
            $n    = 0;
            do {
                $code = $this->baseCode($root) . '-V' . (++$n);
            } while ($this->codeExists($code));

            return $this->duplicator->duplicate($source, [
                'code'            => $code,
                'parent_quote_id' => $root->id,
                'revision'        => null,
                'variant_label'   => trim($label),
            ]);
        });
    }

    /**
     * Référent puis indices et variantes, dans l'ordre de création.
     */
    public function family(Quotes $quote): Collection
    {
        $rootId = $this->rootId($quote);

        return Quotes::query()
            ->where(fn ($q) => $q->whereKey($rootId)->orWhere('parent_quote_id', $rootId))
            ->orderBy('id')
            ->get();
    }

    /**
     * Autres devis de la famille encore ouverts ou envoyés : ceux qu'on propose de
     * clore quand un devis de la famille est gagné.
     */
    public function openSiblings(Quotes $quote): Collection
    {
        return $this->family($quote)
            ->reject(fn (Quotes $q) => $q->id === $quote->id)
            ->filter(fn (Quotes $q) => in_array((int) $q->statu, [self::STATU_OPEN, self::STATU_SENT], true))
            ->values();
    }

    /**
     * Clôt (statut « Clos ») les autres devis ouverts ou envoyés de la famille d'un
     * devis gagné. « Clos » plutôt que « Perdu » : aucun motif de perte n'est en
     * jeu et l'opportunité, gagnée par ce devis, ne doit pas repasser en perdue.
     */
    public function closeOpenSiblings(Quotes $won): Collection
    {
        if ((int) $won->statu !== self::STATU_WON) {
            throw new QuoteRevisionRefused(__('quote_revisions.refused_not_won'));
        }

        return DB::transaction(function () use ($won) {
            $siblings = $this->openSiblings($won);
            foreach ($siblings as $sibling) {
                $this->changeStatus($sibling, self::STATU_CLOSED);
            }

            return $siblings;
        });
    }

    /**
     * Indice plus récent de la même ligne, s'il en existe un : seul le dernier
     * indice se révise, sinon deux devis de la même ligne resteraient ouverts.
     */
    public function newerRevision(Quotes $quote): ?Quotes
    {
        $base    = $this->baseCode($quote);
        $current = $this->effectiveRevision($quote);

        return $this->family($quote)
            ->filter(fn (Quotes $q) => $q->id !== $quote->id && $this->baseCode($q) === $base)
            ->filter(fn (Quotes $q) => $this->compareRevisions($this->effectiveRevision($q), $current) > 0)
            ->sort(fn (Quotes $a, Quotes $b) => $this->compareRevisions($this->effectiveRevision($b), $this->effectiveRevision($a)))
            ->first();
    }

    public function effectiveRevision(Quotes $quote): string
    {
        return filled($quote->revision) ? $quote->revision : self::FIRST_REVISION;
    }

    /** Code sans le suffixe d'indice : QT-0001-C → QT-0001. */
    public function baseCode(Quotes $quote): string
    {
        $suffix = '-' . $quote->revision;

        return filled($quote->revision) && str_ends_with($quote->code, $suffix)
            ? substr($quote->code, 0, -strlen($suffix))
            : $quote->code;
    }

    private function rootId(Quotes $quote): int
    {
        return (int) ($quote->parent_quote_id ?: $quote->id);
    }

    private function root(Quotes $quote): Quotes
    {
        return $quote->parent_quote_id
            ? Quotes::withTrashed()->findOrFail($quote->parent_quote_id)
            : $quote;
    }

    /** Z < AA : la longueur d'abord, puis l'ordre alphabétique. */
    private function compareRevisions(string $a, string $b): int
    {
        return [strlen($a), $a] <=> [strlen($b), $b];
    }

    private function codeExists(string $code): bool
    {
        return Quotes::withTemplates()->withTrashed()->where('code', $code)->exists();
    }

    private function assertNotTemplate(Quotes $quote): void
    {
        if ($quote->is_template) {
            throw new QuoteRevisionRefused(__('quote_revisions.refused_template'));
        }
    }

    /** Même effet que le changement de statut de la fiche (QuotesController::changeStatusJson). */
    private function changeStatus(Quotes $quote, int $statu): void
    {
        $quote->statu = $statu;
        $quote->save();

        QuoteLines::where('quotes_id', $quote->id)
            ->where('statu', '<', $statu)
            ->update(['statu' => $statu]);

        event(new QuoteStatusChanged($quote, $statu));
    }
}
