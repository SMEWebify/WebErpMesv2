<?php

namespace App\Services\Imports;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Lecture des fichiers d'import (CSV, texte, Excel) en lignes de cellules.
 *
 * - .xlsx / .xls : première feuille, valeurs calculées, dates rendues en Y-m-d ;
 * - .csv / .txt : séparateur détecté sur la première ligne (; puis tabulation
 *   puis ,), lignes de toute longueur, BOM retiré, UTF-16 converti ;
 * - toute cellule ressort en UTF-8 : un texte déjà UTF-8 est gardé tel quel,
 *   sinon il est lu comme du Windows-1252 (exports Excel français).
 *
 * Une ligne vide ressort en tableau vide, pour que les numéros de ligne
 * restent ceux du fichier.
 */
class SpreadsheetReader
{
    public const EXTENSIONS = ['csv', 'txt', 'xlsx', 'xls'];

    /**
     * @return array<int, array<int, string|null>>
     */
    public function read(string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?? pathinfo($path, PATHINFO_EXTENSION));

        $rows = in_array($extension, ['xlsx', 'xls'], true)
            ? $this->readSpreadsheet($path, $extension)
            : $this->readCsv($path);

        return array_map(fn (array $row) => $this->isBlank($row) ? [] : array_map([self::class, 'normalizeEncoding'], $row), $rows);
    }

    public static function normalizeEncoding(?string $value): ?string
    {
        if ($value === null || $value === '' || mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    }

    /**
     * Détecte le séparateur d'une ligne CSV : le premier de ; tabulation , qui
     * découpe la ligne en plusieurs colonnes. Le point-virgule passe en premier
     * pour que les fichiers d'avant (toujours lus au ;) le soient encore.
     */
    public static function detectDelimiter(string $line): string
    {
        foreach ([';', "\t", ','] as $delimiter) {
            if (count(str_getcsv($line, $delimiter, '"', '\\')) > 1) {
                return $delimiter;
            }
        }

        return ';';
    }

    private function readCsv(string $path): array
    {
        $content = (string) file_get_contents($path);

        if (str_starts_with($content, "\xFF\xFE")) {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($content, "\xFE\xFF")) {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
        } elseif (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $firstLine = strtok(ltrim($content, "\r\n"), "\r\n");
        $delimiter = self::detectDelimiter($firstLine === false ? '' : $firstLine);

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    private function readSpreadsheet(string $path, string $extension): array
    {
        $reader = IOFactory::createReader($extension === 'xls' ? 'Xls' : 'Xlsx');
        $sheet  = $reader->load($path)->getSheet(0);

        $lastRow    = $sheet->getHighestDataRow();
        $lastColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        $rows = [];
        for ($r = 1; $r <= $lastRow; $r++) {
            $row = [];
            for ($c = 1; $c <= $lastColumn; $c++) {
                $coordinate = Coordinate::stringFromColumnIndex($c) . $r;
                $row[] = $sheet->cellExists($coordinate) ? $this->cellValue($sheet->getCell($coordinate)) : null;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function cellValue(Cell $cell): ?string
    {
        try {
            $value = $cell->getCalculatedValue();
        } catch (\Throwable) {
            $value = $cell->getOldCalculatedValue() ?? $cell->getValue();
        }

        if ($value === null) {
            return null;
        }
        if ($value instanceof RichText) {
            return $value->getPlainText();
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            if (ExcelDate::isDateTime($cell)) {
                $date = ExcelDate::excelToDateTimeObject($value);

                return $date->format($date->format('H:i:s') === '00:00:00' ? 'Y-m-d' : 'Y-m-d H:i:s');
            }

            return self::numberToString($value);
        }

        return (string) $value;
    }

    /** 12.0 → "12", 0.1 + 0.2 → "0.3" : jamais de notation scientifique ni de séparateur de milliers. */
    private static function numberToString(int|float $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }
        if (floor($value) === $value && abs($value) < 1e15) {
            return number_format($value, 0, '.', '');
        }

        return rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');
    }

    private function isBlank(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
