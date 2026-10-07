<?php

namespace App\Services\Documents;

use App\Enums\SalesLineType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Mise en page imprimée des lignes d'un devis, d'une commande ou d'un ARC :
 * sections, sous-totaux, textes, articles masqués et ouvrages au forfait.
 *
 * Deux sorties, pour deux générations de vues PDF :
 * - les lignes « imprimables » (articles visibles + une ligne synthétique par
 *   ouvrage au forfait), qui remplacent $Document->Lines. Une vue qui ne
 *   connaît pas les lignes de présentation — en particulier une vue
 *   print/custom non versionnée, déployée chez un client — imprime ainsi un
 *   document juste : rien de masqué n'apparaît et les montants concordent ;
 * - les rangées typées ($printRows) pour les vues qui savent afficher titres,
 *   sous-totaux et textes.
 *
 * Un document sans aucune ligne de présentation ni ligne masquée n'est pas
 * touché : les rangées valent null et les vues gardent leur rendu d'origine.
 *
 * Les montants sont les totaux HT nets des lignes, arrondis ligne par ligne
 * comme dans getSubTotal() des calculateurs. Les totaux du document, eux,
 * restent ceux des calculateurs : une ligne masquée y est toujours comptée.
 */
class SalesPrintLayout
{
    /**
     * @return array{lines: Collection, rows: ?array}
     */
    public function build(iterable $lines): array
    {
        $lines = collect($lines)->values();

        if (!$this->needsLayout($lines)) {
            return ['lines' => $lines, 'rows' => null];
        }

        $sectionTotals = $this->sectionTotals($lines);
        $rows          = [];
        $printable     = collect();
        $section       = null;   // section en cours
        $runningTotal  = 0.0;    // articles depuis le dernier sous-total, la section ou le début du document

        foreach ($lines as $index => $line) {
            switch ($line->lineType()) {
                case SalesLineType::Section:
                    // Un forfait sans aucune ligne s'imprimerait à 0 € : on garde le seul titre.
                    $section      = [
                        'line'    => $line,
                        'package' => $this->isPackage($line) && $this->sectionLines($lines, $index)->isNotEmpty(),
                    ];
                    $runningTotal = 0.0;

                    if ($section['package']) {
                        $package    = $this->packageLine($line, $sectionTotals[$index], $this->sectionLines($lines, $index));
                        $rows[]     = $this->row('package', $package, $sectionTotals[$index]);
                        $printable->push($package);
                    } else {
                        $rows[] = $this->row('section', $line, $sectionTotals[$index]);
                    }
                    break;

                case SalesLineType::Subtotal:
                    if ($section['package'] ?? false) {
                        break; // le forfait imprime déjà son montant
                    }
                    $rows[] = $this->row('subtotal', $line, round($runningTotal, 2), $this->subtotalLabel($line, $section['line'] ?? null));
                    $runningTotal = 0.0; // le sous-total suivant repart d'ici
                    break;

                case SalesLineType::Text:
                    $rows[] = $this->row('text', $line);
                    break;

                default:
                    $runningTotal += $this->lineNet($line);

                    if ($line->hide_on_pdf || ($section['package'] ?? false)) {
                        break;
                    }
                    $rows[] = $this->row('article', $line, $this->lineNet($line));
                    $printable->push($line);
            }
        }

        return ['lines' => $printable, 'rows' => $rows];
    }

    /**
     * Remplace $document->Lines (déjà exposé par l'appelant) par les lignes
     * imprimables et renvoie les rangées typées, ou null si le document
     * n'utilise aucune fonction de présentation.
     */
    public function apply(object $document): ?array
    {
        $layout = $this->build($document->Lines ?? []);

        if ($layout['rows'] !== null) {
            $document->Lines = $layout['lines'];
        }

        return $layout['rows'];
    }

    /**
     * Pour les pages web montrées au client (lien public, portail) : les
     * articles visibles restent des modèles, pour que la page garde son propre
     * rendu de ligne (statuts de livraison, de facturation…) ; sections,
     * sous-totaux, textes et forfaits sont des rangées typées. Null si le
     * document n'utilise aucune fonction de présentation.
     */
    public function webRows(iterable $lines): ?array
    {
        $rows = $this->build($lines)['rows'];

        return $rows === null
            ? null
            : array_map(fn (array $row) => $row['type'] === 'article' ? $row['line'] : $row, $rows);
    }

    public function needsLayout(Collection $lines): bool
    {
        return $lines->contains(fn ($line) => !$line->isArticle() || $line->hide_on_pdf);
    }

    /**
     * Total HT net d'une ligne, arrondi comme dans les calculateurs.
     */
    public function lineNet(Model $line): float
    {
        if (!$line->isArticle()) {
            return 0.0;
        }

        return round((float) $line->qty * (float) $line->selling_price * (1 - ((float) ($line->discount ?? 0)) / 100), 2);
    }

    /**
     * Montant de chaque section : somme de ses articles, masqués compris.
     *
     * @return array<int, float> indexé par la position de la section
     */
    private function sectionTotals(Collection $lines): array
    {
        $totals = [];
        foreach ($lines as $index => $line) {
            if ($line->lineType() === SalesLineType::Section) {
                $totals[$index] = round($this->sectionLines($lines, $index)->sum(fn ($l) => $this->lineNet($l)), 2);
            }
        }

        return $totals;
    }

    /**
     * Articles d'une section : de son titre jusqu'à la section suivante.
     */
    private function sectionLines(Collection $lines, int $sectionIndex): Collection
    {
        $articles = collect();
        foreach ($lines->slice($sectionIndex + 1) as $line) {
            if ($line->lineType() === SalesLineType::Section) {
                break;
            }
            if ($line->isArticle()) {
                $articles->push($line);
            }
        }

        return $articles;
    }

    private function isPackage(Model $section): bool
    {
        return (int) $section->pdf_package > 0;
    }

    /**
     * Ligne synthétique, jamais enregistrée, qui porte l'ouvrage au forfait
     * pour les vues qui ne connaissent que des articles.
     */
    private function packageLine(Model $section, float $amount, Collection $articles): Model
    {
        $package = $section->replicate();
        $package->forceFill([
            'line_type'     => SalesLineType::Article->value,
            'qty'           => 1,
            'selling_price' => $amount,
            'discount'      => 0,
            'hide_on_pdf'   => false,
            'delivery_date' => $articles->pluck('delivery_date')->filter()->max(),
        ]);
        if (in_array('use_calculated_price', $package->getFillable(), true)) {
            $package->use_calculated_price = false;
        }

        foreach (['QuoteLineDetails', 'OrderLineDetails'] as $relation) {
            if (method_exists($package, $relation)) {
                $package->setRelation($relation, null);
            }
        }
        if (method_exists($section, 'Unit')) {
            $package->setRelation('Unit', $section->Unit);
        }

        return $package;
    }

    private function subtotalLabel(Model $line, ?Model $section): string
    {
        if (filled($line->label)) {
            return $line->label;
        }

        return $section
            ? __('general_content.sub_total_trans_key') . ' ' . $section->label
            : __('general_content.sub_total_trans_key');
    }

    private function row(string $type, Model $line, ?float $amount = null, ?string $label = null): array
    {
        return [
            'type'   => $type,
            'line'   => $line,
            'label'  => $label ?? $line->label,
            'amount' => $amount,
            // forfait « montant seul » : ni quantité ni unité imprimées
            'show_qty' => $type !== 'package' || (int) $line->pdf_package !== 1,
        ];
    }
}
