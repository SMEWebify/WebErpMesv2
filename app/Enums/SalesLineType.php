<?php

namespace App\Enums;

/**
 * Nature d'une ligne de devis, de commande ou d'ARC.
 *
 * Seul un article porte une quantité, un prix et une TVA. Les trois autres
 * types ne servent qu'à la mise en page du document : ils n'entrent dans
 * aucun total, ne se livrent pas, ne se facturent pas et n'atteignent jamais
 * le Factur-X.
 */
enum SalesLineType: string
{
    case Article  = 'article';
    case Section  = 'section';   // titre d'ouvrage
    case Subtotal = 'subtotal';  // calculé à l'impression, jamais saisi
    case Text     = 'text';      // texte libre

    public function isArticle(): bool
    {
        return $this === self::Article;
    }

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
