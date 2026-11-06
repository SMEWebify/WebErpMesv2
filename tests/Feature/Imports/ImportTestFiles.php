<?php

namespace Tests\Feature\Imports;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Fichiers d'import fabriqués pour les tests : les mêmes lignes en CSV ;
 * UTF-8 avec BOM, en CSV , Windows-1252 et en XLSX.
 */
trait ImportTestFiles
{
    /** @param array<int, array<int, string|int|float|null>> $rows */
    protected function csvUtf8Bom(array $rows, string $name = 'import.csv'): UploadedFile
    {
        $lines = array_map(fn ($row) => implode(';', array_map(fn ($v) => (string) $v, $row)), $rows);

        return $this->uploaded("\xEF\xBB\xBF" . implode("\r\n", $lines) . "\r\n", $name);
    }

    protected function csvWindows1252(array $rows, string $name = 'import.csv'): UploadedFile
    {
        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($v) => (string) $v, $row), ',', '"', '\\');
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $this->uploaded(mb_convert_encoding($content, 'Windows-1252', 'UTF-8'), $name);
    }

    protected function xlsx(array $rows, string $name = 'import.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($rows as $r => $row) {
            foreach ($row as $c => $value) {
                if ($value === null) {
                    continue;
                }
                $coordinate = [$c + 1, $r + 1];
                // Les codes restent du texte (comme dans un export), les nombres des nombres.
                is_int($value) || is_float($value)
                    ? $sheet->setCellValue($coordinate, $value)
                    : $sheet->setCellValueExplicit($coordinate, (string) $value, DataType::TYPE_STRING);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, $name, null, null, true);
    }

    /** Les trois formats, pour les fournisseurs de données. */
    public static function formats(): array
    {
        return [
            'CSV ; UTF-8 avec BOM' => ['csvUtf8Bom'],
            'CSV , Windows-1252'   => ['csvWindows1252'],
            'XLSX'                 => ['xlsx'],
        ];
    }

    protected function uploaded(string $content, string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'imp');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }
}
