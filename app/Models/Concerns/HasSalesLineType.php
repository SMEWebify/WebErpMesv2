<?php

namespace App\Models\Concerns;

use App\Enums\SalesLineType;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lignes de devis, de commande et d'ARC : type de ligne et options d'impression.
 *
 * Une ligne qui n'est pas un article est neutralisée à l'enregistrement
 * (quantité, prix et remise à zéro). Une somme SQL qui aurait oublié de filtrer
 * le type reste donc juste ; seuls les comptages ont besoin du scope articles().
 */
trait HasSalesLineType
{
    /** pdf_package : la section imprime son détail. */
    public const PACKAGE_NONE = 0;
    /** pdf_package : la section imprime un seul montant, sans quantité. */
    public const PACKAGE_AMOUNT = 1;
    /** pdf_package : la section imprime « 1 × unité » et son montant. */
    public const PACKAGE_UNIT = 2;

    public static function bootHasSalesLineType(): void
    {
        static::saving(function ($line) {
            if (!$line->exists && empty($line->line_type)) {
                $line->line_type = SalesLineType::Article->value;
            }

            // Ligne chargée par un select partiel : son type est inconnu, on n'y touche pas.
            if (!array_key_exists('line_type', $line->getAttributes())) {
                return;
            }

            if ($line->isArticle()) {
                $line->pdf_package = self::PACKAGE_NONE;

                return;
            }

            $line->qty           = 0;
            $line->selling_price = 0;
            $line->discount      = 0;
            $line->hide_on_pdf   = false;

            if ($line->line_type !== SalesLineType::Section->value) {
                $line->pdf_package = self::PACKAGE_NONE;
            }

            $line->neutralizePresentationLine();
        });
    }

    /** Point d'extension : champs propres au modèle à neutraliser (logistique côté commande). */
    protected function neutralizePresentationLine(): void {}

    public function lineType(): SalesLineType
    {
        return SalesLineType::tryFrom((string) ($this->line_type ?? '')) ?? SalesLineType::Article;
    }

    public function isArticle(): bool
    {
        return $this->lineType()->isArticle();
    }

    public function scopeArticles(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('line_type'), SalesLineType::Article->value);
    }
}
