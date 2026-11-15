<?php

namespace App\Services\Quotes;

use App\Enums\SalesLineType;
use App\Models\Accounting\AccountingVat;
use App\Models\Methods\MethodsUnits;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\ImportCsvService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use RuntimeException;

/**
 * Import d'un DPGF (ou BPU / DQE) d'appel d'offres en lignes de devis.
 *
 * - analyse() n'écrit rien : en-tête et colonnes détectés (N°, désignation,
 *   unité, quantité, PU, montant), puis une proposition par ligne du fichier ;
 * - create() enregistre les lignes relues par l'utilisateur, en fin de devis.
 *
 * Classement : titre (lot, chapitre, ligne numérotée sans quantité) → section,
 * « Total lot … » → sous-total, poste chiffrable → article, poste « PM / inclus /
 * sans objet » ou ligne descriptive → texte, totaux généraux / TVA / TTC → ignorés.
 * Les sections ne s'imbriquent pas : les niveaux du DPGF sont aplatis, le
 * numéro du titre est gardé dans son libellé.
 */
class DpgfImporter
{
    public const FIELDS = ['code', 'label', 'unit', 'qty', 'price', 'amount'];
    public const IGNORE = 'ignore';
    public const TYPES = ['article', 'section', 'subtotal', 'text', self::IGNORE];
    public const LABEL_MAX = 255;
    public const DESCRIPTION_MAX = 5000;

    /**
     * @param  array<int, array<int, string|null>>  $rows  lignes du fichier (index 0 = ligne 1), ligne vide = []
     * @param  array<string, int|null>|null  $columns  correspondance choisie par l'utilisateur, sinon détectée
     * @param  int|null  $headerRow  dernière ligne d'en-tête (n° de ligne du fichier, 0 = aucune), sinon détectée
     * @return array{header_row: int, columns: array<string, int|null>, headers: list<array{index: int, letter: string, header: string|null}>,
     *               sample: list<array{row: int, cells: list<string|null>}>, lines: list<array<string, mixed>>}
     */
    public function analyse(array $rows, ?array $columns = null, ?int $headerRow = null): array
    {
        $rows = array_slice($rows, 0, (int) config('dpgf.max_rows'), true);
        $width = $rows === [] ? 0 : max(array_map('count', $rows));

        if ($columns === null) {
            [$detectedRow, $columns] = $this->detectHeader($rows);
            $headerRow ??= $detectedRow;
        }
        $columns = $this->cleanColumns($columns, $width);
        $headerRow = max(0, (int) $headerRow);

        $lines = [];
        if ($columns['label'] !== null) {
            $units = $this->unitIndex();
            foreach ($rows as $index => $cells) {
                if ($index + 1 <= $headerRow || $cells === []) {
                    continue;
                }
                if ($line = $this->classify($cells, $columns, $units)) {
                    $lines[] = ['row' => $index + 1] + $line;
                }
            }
        }

        return [
            'header_row' => $headerRow,
            'columns' => $columns,
            'headers' => $this->headers($rows, $headerRow, $width),
            'sample' => $this->sample($rows, $width),
            'lines' => $lines,
        ];
    }

    /**
     * Lignes relues par l'utilisateur, ajoutées en fin de devis.
     *
     * @param  list<array<string, mixed>>  $lines  déjà validées (contrôleur), sans les lignes ignorées
     * @return Collection<int, QuoteLines>
     */
    public function create(Quotes $quote, array $lines): Collection
    {
        $defaultVat = AccountingVat::getDefault();
        $defaultUnit = MethodsUnits::getDefault();

        if (! $defaultVat || ! $defaultUnit) {
            throw new RuntimeException(__('imports.no_line_defaults'));
        }

        return DB::transaction(function () use ($quote, $lines, $defaultVat, $defaultUnit) {
            $ordre = (int) (QuoteLines::where('quotes_id', $quote->id)->max('ordre') ?? 0);

            return collect($lines)->map(function (array $data) use ($quote, &$ordre, $defaultVat, $defaultUnit) {
                $type = SalesLineType::from($data['type']);
                $article = $type === SalesLineType::Article;

                // Ligne de présentation : quantité, prix et remise remis à zéro par HasSalesLineType.
                $line = QuoteLines::create([
                    'quotes_id' => $quote->id,
                    'ordre' => ++$ordre,
                    'line_type' => $type->value,
                    'code' => $article ? (string) ($data['code'] ?? '') : '',
                    'label' => (string) ($data['label'] ?? ''),
                    'qty' => $article ? ($data['qty'] ?? 0) : 0,
                    'methods_units_id' => ($article ? ($data['methods_units_id'] ?? null) : null) ?? $defaultUnit->id,
                    'selling_price' => $article ? ($data['selling_price'] ?? 0) : 0,
                    'discount' => 0,
                    'accounting_vats_id' => $defaultVat->id,
                    // Écrit explicitement : le défaut SQL ne revient pas dans le JSON renvoyé à l'écran.
                    'statu' => 1,
                ]);

                $description = $data['description'] ?? null;
                if ($article || $description !== null) {
                    QuoteLineDetails::create([
                        'quote_lines_id' => $line->id,
                        'external_comment' => $description,
                    ]);
                }

                return $line;
            });
        });
    }

    // -------------------------------------------------------------------------
    // En-tête et colonnes
    // -------------------------------------------------------------------------

    /**
     * Première ligne qui nomme la désignation et au moins deux autres colonnes.
     * Un en-tête sur deux lignes (« Prix » au-dessus de « unitaire | total »)
     * est lu en accolant chaque cellule à celle du dessous.
     *
     * @return array{0: int, 1: array<string, int|null>}
     */
    private function detectHeader(array $rows): array
    {
        $scanned = 0;
        foreach ($rows as $index => $cells) {
            if ($cells === []) {
                continue;
            }
            if (++$scanned > (int) config('dpgf.header_scan_rows')) {
                break;
            }

            $columns = $this->matchHeader($cells);
            if ($this->isHeader($columns)) {
                return [$index + 1, $columns];
            }

            $below = $rows[$index + 1] ?? [];
            if ($below !== []) {
                $merged = [];
                foreach (range(0, max(count($cells), count($below)) - 1) as $i) {
                    $merged[$i] = trim(($cells[$i] ?? '') . ' ' . ($below[$i] ?? ''));
                }
                $columns = $this->matchHeader($merged);
                if ($this->isHeader($columns)) {
                    return [$index + 2, $columns];
                }
            }
        }

        return [0, array_fill_keys(self::FIELDS, null)];
    }

    private function isHeader(array $columns): bool
    {
        $others = count(array_filter(array_diff_key($columns, ['label' => true]), fn ($c) => $c !== null));

        return $columns['label'] !== null && $others >= 2;
    }

    /**
     * Chaque cellule va au champ dont un nom correspond le mieux : nom exact
     * d'abord, sinon le plus long nom par lequel elle commence (« Prix total HT »
     * est un montant, pas un prix unitaire). Un champ prend une seule colonne.
     *
     * @return array<string, int|null>
     */
    private function matchHeader(array $cells): array
    {
        $columns = array_fill_keys(self::FIELDS, null);
        $best = [];

        foreach ($cells as $index => $value) {
            $header = ImportCsvService::normalizeHeader($value);
            if ($header === '') {
                continue;
            }

            $choice = null;
            $score = 0;
            foreach (config('dpgf.columns') as $field => $names) {
                foreach ($names as $name) {
                    $candidate = match (true) {
                        $header === $name => 1000 + strlen($name),
                        strlen($name) >= 3 && str_starts_with($header, $name . ' ') => strlen($name),
                        default => 0,
                    };
                    if ($candidate > $score) {
                        [$choice, $score] = [$field, $candidate];
                    }
                }
            }

            if ($choice !== null && $score > ($best[$choice] ?? 0)) {
                $columns[$choice] = $index;
                $best[$choice] = $score;
            }
        }

        return $columns;
    }

    /** Colonnes connues seulement, dans la largeur du fichier, une colonne par champ. */
    private function cleanColumns(array $columns, int $width): array
    {
        $clean = [];
        $used = [];
        foreach (self::FIELDS as $field) {
            $index = $columns[$field] ?? null;
            $index = is_numeric($index) && (int) $index >= 0 && (int) $index < $width && ! isset($used[(int) $index]) ? (int) $index : null;
            if ($index !== null) {
                $used[$index] = true;
            }
            $clean[$field] = $index;
        }

        return $clean;
    }

    private function headers(array $rows, int $headerRow, int $width): array
    {
        $cells = $headerRow > 0 ? ($rows[$headerRow - 1] ?? []) : [];
        $above = $headerRow > 1 ? ($rows[$headerRow - 2] ?? []) : [];

        $headers = [];
        for ($i = 0; $i < $width; $i++) {
            $text = trim(($above[$i] ?? '') . ' ' . ($cells[$i] ?? ''));
            $headers[] = [
                'index' => $i,
                'letter' => Coordinate::stringFromColumnIndex($i + 1),
                'header' => $text !== '' ? mb_substr($text, 0, 60) : null,
            ];
        }

        return $headers;
    }

    /** Premières lignes non vides, pour choisir l'en-tête et les colonnes quand rien n'est détecté. */
    private function sample(array $rows, int $width): array
    {
        $sample = [];
        foreach ($rows as $index => $cells) {
            if ($cells === []) {
                continue;
            }
            $sample[] = [
                'row' => $index + 1,
                'cells' => array_map(fn ($c) => $c === null ? null : mb_substr(trim($c), 0, 80), array_pad($cells, $width, null)),
            ];
            if (count($sample) >= 15) {
                break;
            }
        }

        return $sample;
    }

    // -------------------------------------------------------------------------
    // Lignes
    // -------------------------------------------------------------------------

    /**
     * @param  array{ids: array<string, int>, lump: array<string, true>}  $units
     */
    private function classify(array $cells, array $columns, array $units): ?array
    {
        $cell = fn (string $field) => $columns[$field] === null ? '' : trim((string) ($cells[$columns[$field]] ?? ''));

        $code = preg_replace('/\s+/u', ' ', $cell('code'));
        $raw = $cell('label');
        $label = preg_replace('/\s+/u', ' ', $raw);
        $unitText = $cell('unit');
        $qtyText = $cell('qty');
        $priceText = $cell('price');
        $amountText = $cell('amount');

        // Titre écrit dans la colonne du numéro (cellules fusionnées).
        if ($label === '') {
            if ($code === '' || $qtyText !== '' || $priceText !== '') {
                return null;
            }
            [$label, $raw, $code] = [$code, $code, ''];
        }

        $key = ImportCsvService::normalizeHeader($label);
        $unitKey = self::unitKey($unitText);
        $qty = self::number($qtyText);
        $price = self::number($priceText);
        $amount = self::number($amountText);

        $line = [
            'type' => SalesLineType::Text->value,
            'code' => '',
            'label' => $label,
            'description' => null,
            'qty' => null,
            'methods_units_id' => null,
            'unit_text' => null,
            'selling_price' => null,
            'qty_missing' => false,
        ];

        if ($this->isGrandTotal($key)) {
            $line['type'] = self::IGNORE;
        } elseif (preg_match('/^(sous total|total|montant)\b/', $key)) {
            $line['type'] = SalesLineType::Subtotal->value;
        } elseif (($mention = $this->mention([$qtyText, $priceText, $amountText, $unitText])) !== null) {
            // Poste pour mémoire, inclus, sans objet : imprimé, jamais chiffré.
            $line['label'] = $this->withCode($code, $label) . ' — ' . $mention;
        } elseif ($qty !== null || $price !== null || $unitText !== '' || $amount !== null) {
            $line['type'] = SalesLineType::Article->value;
            $line['code'] = mb_substr($code, 0, 255);
            $line['methods_units_id'] = $units['ids'][$unitKey] ?? null;
            $line['unit_text'] = $line['methods_units_id'] === null && $unitText !== '' ? mb_substr($unitText, 0, 50) : null;

            if ($qty === null && (isset($units['lump'][$unitKey]) || ($price === null && $amount !== null))) {
                $qty = 1.0;
            }
            $line['qty_missing'] = $qty === null;
            $line['qty'] = $qty ?? 0.0;
            // DPGF déjà chiffré sans prix unitaire : montant ÷ quantité.
            $line['selling_price'] = $price ?? ($amount !== null && $qty > 0 ? round($amount / $qty, 3) : null);
        } elseif ($code !== '' || $this->looksLikeTitle($label, $key)) {
            $line['type'] = SalesLineType::Section->value;
            $line['label'] = $this->withCode($code, $label);
        }

        // Désignation trop longue pour la ligne : texte complet dans la description longue.
        if (mb_strlen($line['label']) > self::LABEL_MAX) {
            $line['description'] = mb_substr($raw, 0, self::DESCRIPTION_MAX);
            $line['label'] = mb_substr($line['label'], 0, self::LABEL_MAX - 1) . '…';
        }

        return $line;
    }

    private function isGrandTotal(string $key): bool
    {
        return (bool) preg_match('/\bttc\b|^t ?v ?a\b|^net a payer|^recapitulatif|^(total|montant)( \w+)? general/', $key);
    }

    private function looksLikeTitle(string $label, string $key): bool
    {
        if (preg_match('/^(lot|chapitre|titre|tranche|partie)\b/', $key)) {
            return true;
        }

        // Titre en capitales : « SERRURERIE », « GARDE-CORPS EXTÉRIEURS ».
        return mb_strlen($label) <= 120 && preg_match('/\p{L}{3}/u', $label) && mb_strtoupper($label) === $label;
    }

    private function withCode(string $code, string $label): string
    {
        return $code === '' || str_starts_with($label, $code) ? $label : $code . ' ' . $label;
    }

    private function mention(array $texts): ?string
    {
        $mentions = config('dpgf.mentions');
        foreach ($texts as $text) {
            if ($text !== '' && in_array(ImportCsvService::normalizeHeader($text), $mentions, true)) {
                return $text;
            }
        }

        return null;
    }

    /**
     * Unités de l'ERP reconnues par code ou libellé, puis par leur groupe du
     * fichier de config (une unité « M2 - Mètre carré » reconnaît « m² »).
     *
     * @return array{ids: array<string, int>, lump: array<string, true>}
     */
    private function unitIndex(): array
    {
        $groups = config('dpgf.units');
        $ids = [];

        $units = MethodsUnits::query()->orderBy('id')->get(['id', 'code', 'label']);
        foreach ($units as $unit) {
            foreach ([$unit->code, $unit->label] as $name) {
                $ids[self::unitKey((string) $name)] ??= $unit->id;
            }
        }
        foreach ($units as $unit) {
            $names = [self::unitKey((string) $unit->code), self::unitKey((string) $unit->label)];
            foreach ($groups as $group) {
                if (array_intersect($names, $group)) {
                    foreach ($group as $alias) {
                        $ids[$alias] ??= $unit->id;
                    }
                }
            }
        }
        unset($ids['']);

        $lump = [];
        foreach ($groups as $group) {
            if (array_intersect(config('dpgf.lump_sum_units'), $group)) {
                $lump += array_fill_keys($group, true);
            }
        }

        return ['ids' => $ids, 'lump' => $lump];
    }

    public static function unitKey(string $text): string
    {
        return ImportCsvService::normalizeHeader(str_replace(['²', '³'], ['2', '3'], $text));
    }

    /** « 1 234,50 € », « 1.234,5 », « 12.5 » → nombre ; texte, vide ou négatif → null. */
    public static function number(string $text): ?float
    {
        $text = str_ireplace([' ', "\u{00A0}", "\u{202F}", '€', 'eur', 'ht'], '', $text);
        if ($text === '') {
            return null;
        }

        if (str_contains($text, ',') && str_contains($text, '.')) {
            $text = strrpos($text, ',') > strrpos($text, '.')
                ? str_replace(['.', ','], ['', '.'], $text)
                : str_replace(',', '', $text);
        } else {
            $text = str_replace(',', '.', $text);
        }

        return is_numeric($text) && (float) $text >= 0 ? round((float) $text, 4) : null;
    }
}
