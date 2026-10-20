<?php

namespace App\Services\Cad\Parsers;

use App\Services\Cad\BaseCadParser;
use App\Services\Cad\PdfDrawingReader;
use App\Services\Files\FileRole;
use Illuminate\Http\UploadedFile;

/**
 * PDF part drawing — the title block is read by the AI provider of the
 * instance (PdfDrawingReader), one line per file.
 *
 * What the AI read is a draft: the line says so in its requirements, and the
 * drawing stays attached to it for checking.
 */
class PdfParser extends BaseCadParser
{
    public function __construct(private readonly PdfDrawingReader $reader) {}

    public static function extensions(): array
    {
        return ['pdf'];
    }

    public function gedRole(): ?string
    {
        return FileRole::PLAN;
    }

    public function parse(UploadedFile $file): array
    {
        if (! str_starts_with($this->head($file, 1024), '%PDF-')) {
            throw new \RuntimeException('Fichier PDF non reconnu');
        }

        $data = $this->reader->read($file);

        $code = $data['code'] ?? $this->baseName($file);

        return [
            'qty' => $data['qty'],
        ] + $this->result([
            'code' => $code,
            'label' => $data['label'] ?? $code,
            'material' => $data['material'],
            'thickness' => $data['thickness'],
            'x_size' => $data['x_size'],
            'y_size' => $data['y_size'],
            'weight' => $data['weight'],
            'extra' => $this->requirements([
                $this->requirement('Indice', $data['revision']),
                $this->requirement('Finition', $data['finishing']),
                $this->requirement('Exigences', $data['notes']),
                $this->requirement('Source', 'PDF lu par IA — à vérifier'),
            ]),
        ]);
    }
}
