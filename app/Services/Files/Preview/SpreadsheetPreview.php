<?php

namespace App\Services\Files\Preview;

use App\Services\Imports\SpreadsheetReader;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Lecture d'un tableur (xlsx, xls, ods, csv) pour l'aperçu de la GED.
 *
 * Toutes les feuilles, valeurs affichées comme dans Excel (format de nombre et
 * de date de la cellule, résultat des formules), tronquées à max_rows ×
 * max_columns par feuille. Lecture seule : le fichier n'est jamais réécrit.
 */
class SpreadsheetPreview
{
    public function __construct(
        private readonly int $maxRows,
        private readonly int $maxColumns,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            max(1, (int) config('files.preview.max_rows', 500)),
            max(1, (int) config('files.preview.max_columns', 50)),
        );
    }

    /**
     * @return array{type: string, sheets: array<int, array{name: string, hidden: bool, rows: array<int, array<int, string>>, total_rows: int, total_columns: int, truncated: bool}>}
     */
    public function read(string $path, string $extension, ?string $name = null): array
    {
        $extension = strtolower($extension);

        $sheets = $extension === 'csv'
            ? [$this->csvSheet($path, $name)]
            : $this->workbookSheets($path, $extension);

        return ['type' => 'sheet', 'sheets' => $sheets];
    }

    /**
     * Le CSV passe par le lecteur des imports : séparateur, BOM et encodage
     * Windows-1252 y sont déjà traités.
     */
    private function csvSheet(string $path, ?string $name): array
    {
        $rows = (new SpreadsheetReader())->read($path, 'csv');

        $totalRows = count($rows);
        $totalColumns = $rows === [] ? 0 : max(array_map('count', $rows));
        $columns = min($totalColumns, $this->maxColumns);

        $kept = [];
        foreach (array_slice($rows, 0, $this->maxRows) as $row) {
            $line = [];
            for ($c = 0; $c < $columns; $c++) {
                $line[] = (string) ($row[$c] ?? '');
            }
            $kept[] = $line;
        }

        return $this->sheet(pathinfo((string) ($name ?? $path), PATHINFO_FILENAME) ?: 'CSV', false, $kept, $totalRows, $totalColumns);
    }

    private function workbookSheets(string $path, string $extension): array
    {
        $reader = IOFactory::createReader(match ($extension) {
            'xls' => 'Xls',
            'ods' => 'Ods',
            default => 'Xlsx',
        });

        // Les dimensions réelles sont lues avant le chargement, qui ne garde que
        // la zone affichée : un classeur de 100 000 lignes ne monte pas en mémoire.
        $dimensions = [];
        try {
            foreach ($reader->listWorksheetInfo($path) as $info) {
                $dimensions[$info['worksheetName']] = [(int) $info['totalRows'], (int) $info['totalColumns']];
            }
        } catch (\Throwable) {
            // Certains fichiers xls ne donnent pas leurs dimensions : on lira la zone chargée.
        }

        $reader->setReadFilter(new class($this->maxRows, $this->maxColumns) implements IReadFilter {
            public function __construct(private int $rows, private int $columns)
            {
            }

            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return $row <= $this->rows && Coordinate::columnIndexFromString($columnAddress) <= $this->columns;
            }
        });

        $workbook = $reader->load($path);

        $sheets = [];
        foreach ($workbook->getWorksheetIterator() as $worksheet) {
            [$totalRows, $totalColumns] = $dimensions[$worksheet->getTitle()] ?? [0, 0];
            $rows = $this->worksheetRows($worksheet);

            $sheets[] = $this->sheet(
                $worksheet->getTitle(),
                $worksheet->getSheetState() !== Worksheet::SHEETSTATE_VISIBLE,
                $rows,
                max($totalRows, count($rows)),
                max($totalColumns, $rows === [] ? 0 : count($rows[0])),
            );
        }

        $workbook->disconnectWorksheets();

        return $sheets;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function worksheetRows(Worksheet $worksheet): array
    {
        $lastRow = min($worksheet->getHighestDataRow(), $this->maxRows);
        $lastColumn = min(Coordinate::columnIndexFromString($worksheet->getHighestDataColumn()), $this->maxColumns);

        // Une feuille vide répond A1 : rien à montrer.
        if ($lastRow === 1 && $lastColumn === 1 && ! $worksheet->cellExists('A1')) {
            return [];
        }

        $rows = [];
        for ($r = 1; $r <= $lastRow; $r++) {
            $row = [];
            for ($c = 1; $c <= $lastColumn; $c++) {
                $coordinate = Coordinate::stringFromColumnIndex($c) . $r;
                $row[] = $worksheet->cellExists($coordinate) ? $this->display($worksheet->getCell($coordinate)) : '';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Valeur telle qu'Excel l'affiche. Une formule est d'abord lue dans le
     * résultat enregistré par Excel, recalculée seulement à défaut : une
     * référence vers une feuille hors de la zone lue donnerait un faux résultat.
     */
    private function display(Cell $cell): string
    {
        $value = $cell->getValue();

        if ($cell->isFormula()) {
            $value = $cell->getOldCalculatedValue();

            if ($value === null) {
                try {
                    $value = $cell->getCalculatedValue();
                } catch (\Throwable) {
                    return (string) $cell->getValue();
                }
            }
        }

        if ($value === null) {
            return '';
        }
        if ($value instanceof RichText) {
            return $value->getPlainText();
        }
        if (is_bool($value)) {
            // Comme Excel, dans la langue de l'utilisateur.
            return app()->getLocale() === 'fr' ? ($value ? 'VRAI' : 'FAUX') : ($value ? 'TRUE' : 'FALSE');
        }
        if (is_int($value) || is_float($value)) {
            try {
                $format = $cell->getStyle()->getNumberFormat()->getFormatCode();

                return (string) NumberFormat::toFormattedString($value, $format ?? NumberFormat::FORMAT_GENERAL);
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        return (string) $value;
    }

    private function sheet(string $name, bool $hidden, array $rows, int $totalRows, int $totalColumns): array
    {
        return [
            'name' => $name,
            'hidden' => $hidden,
            'rows' => $rows,
            'total_rows' => $totalRows,
            'total_columns' => $totalColumns,
            'truncated' => $totalRows > $this->maxRows || $totalColumns > $this->maxColumns,
        ];
    }
}
