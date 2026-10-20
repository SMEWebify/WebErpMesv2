<?php

namespace App\Services\Cad\Parsers;

use App\Services\Cad\CadMultiPartParser;
use Illuminate\Http\UploadedFile;

/**
 * RADAN .drg — a nest: one sheet and the parts cut from it.
 *
 * Only the parts are imported. Every SetupSheet/Parts/Part embeds a copy of
 * the attributes and the thumbnail of its .sym, so each one gives the line its
 * .sym would have given, without the .sym having to be reachable: the path it
 * points to is on the RADAN workstation, not on the server.
 *
 * Quantity of a part = parts per sheet (NumOff) × number of sheets.
 */
class DrgParser extends SymParser implements CadMultiPartParser
{
    private const ATTR_SHEET_COUNT = 137;

    public static function extensions(): array
    {
        return ['drg'];
    }

    /**
     * A nest is never a single line: the first part stands for the file
     * wherever only one line is expected.
     */
    public function parse(UploadedFile $file): array
    {
        return $this->parseParts($file)[0];
    }

    public function parseParts(UploadedFile $file): array
    {
        $xml = $this->loadRadanXml($file);
        $parts = $xml->SetupSheet->Parts->Part ?? null;

        if ($parts === null || count($parts) === 0) {
            throw new \RuntimeException('Aucune pièce dans cette imbrication');
        }

        $sheets = $this->sheetCount($xml);
        $nest = $this->baseName($file);
        $lines = [];

        foreach ($parts as $part) {
            $path = trim((string) $part->FilePath);
            // FilePath is a Windows path, which basename() does not split on Linux.
            $fileName = $path !== '' ? preg_replace('/^.*[\\\\\/]/', '', $path) : null;
            $perSheet = max(1, (int) ((string) $part->NumOff ?: 1));

            $line = $this->fromAttributes(
                $part->RadanAttributes,
                $fileName !== null ? pathinfo($fileName, PATHINFO_FILENAME) : $nest . '-' . (string) $part['num'],
                isset($part->Image) ? (string) $part->Image : null,
                'RADAN .drg ' . $nest,
            );

            $line['extra'][] = ['label' => 'Imbrication', 'value' => $perSheet . ' par tôle × ' . $sheets . ' tôle(s)'];
            $line['qty'] = $perSheet * $sheets;
            $line['cad_file'] = $fileName;

            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * Number of copies of the sheet to cut, from the nest attributes, else
     * from the machine setup sheet.
     */
    private function sheetCount(\SimpleXMLElement $xml): int
    {
        $count = $this->attributes($xml->RadanAttributes)[self::ATTR_SHEET_COUNT] ?? null;

        if ($count === null || $count === '') {
            $count = (string) ($xml->SetupSheet->Machines->Machine->SettingInformation->NumberSheets ?? '');
        }

        return max(1, (int) $count);
    }
}
