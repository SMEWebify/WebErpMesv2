<?php

namespace App\Services\Cad\Parsers;

use App\Services\Cad\BaseCadParser;
use App\Services\Files\FileRole;
use Illuminate\Http\UploadedFile;

/**
 * SVG — the size is taken from the root element.
 *
 * width/height with an explicit unit are authoritative; without one the value
 * is in user units, which the viewBox also expresses, and the CSS convention
 * of 96 pixels per inch turns them into millimetres.
 */
class SvgParser extends BaseCadParser
{
    /** The root element sits at the top of the document. */
    private const READ_BYTES = 65536;

    /** Millimetres per CSS unit. */
    private const UNITS = [
        'mm' => 1.0,
        'cm' => 10.0,
        'q' => 0.25,
        'in' => 25.4,
        'pt' => 25.4 / 72,
        'pc' => 25.4 / 6,
        'px' => 25.4 / 96,
        '' => 25.4 / 96,
    ];

    public static function extensions(): array
    {
        return ['svg'];
    }

    public function gedRole(): ?string
    {
        return FileRole::VECTOR;
    }

    public function parse(UploadedFile $file): array
    {
        $head = $this->head($file, self::READ_BYTES);

        if (preg_match('/<svg\b([^>]*)>/i', $head, $matches) !== 1) {
            throw new \RuntimeException('Fichier SVG non reconnu');
        }

        $attributes = $this->attributes($matches[1]);
        [$xSize, $ySize, $source] = $this->size($attributes);

        $code = $this->baseName($file);

        $dimensions = $xSize !== null && $ySize !== null
            ? $this->number($xSize) . 'x' . $this->number($ySize) . 'mm'
            : null;

        return $this->result([
            'code' => $code,
            'label' => $this->label([$code, $dimensions]),
            'x_size' => $xSize,
            'y_size' => $ySize,
            'extra' => $this->requirements([
                $this->requirement('Cotes', $source),
                $this->requirement('Source', 'SVG'),
            ]),
        ]);
    }

    /**
     * Width and height in millimetres, plus where they were read from.
     *
     * @param  array<string, string>  $attributes
     * @return array{0: ?float, 1: ?float, 2: ?string}
     */
    private function size(array $attributes): array
    {
        $width = $this->length($attributes['width'] ?? null);
        $height = $this->length($attributes['height'] ?? null);

        if ($width !== null && $height !== null) {
            return [$width, $height, 'attributs width/height'];
        }

        $viewBox = preg_split('/[\s,]+/', trim($attributes['viewbox'] ?? ''));

        if (count($viewBox) === 4 && is_numeric($viewBox[2]) && is_numeric($viewBox[3])) {
            $factor = self::UNITS['px'];

            return [
                round((float) $viewBox[2] * $factor, 3),
                round((float) $viewBox[3] * $factor, 3),
                'viewBox',
            ];
        }

        return [null, null, null];
    }

    /**
     * Convert a CSS length to millimetres. Percentages are relative to a
     * viewport we do not have, so they are ignored.
     */
    private function length(?string $value): ?float
    {
        if ($value === null || preg_match('/^\s*(-?[\d.]+)\s*([a-z%]*)\s*$/i', $value, $matches) !== 1) {
            return null;
        }

        $unit = mb_strtolower($matches[2]);

        if (! array_key_exists($unit, self::UNITS)) {
            return null;
        }

        $length = (float) $matches[1] * self::UNITS[$unit];

        return $length > 0 ? round($length, 3) : null;
    }

    /**
     * Attributes of the root element, keyed by lowercase name.
     *
     * @return array<string, string>
     */
    private function attributes(string $raw): array
    {
        preg_match_all('/([\w:-]+)\s*=\s*"([^"]*)"|([\w:-]+)\s*=\s*\'([^\']*)\'/', $raw, $matches, PREG_SET_ORDER);

        $attributes = [];

        foreach ($matches as $match) {
            $name = $match[1] !== '' ? $match[1] : ($match[3] ?? '');
            $value = $match[1] !== '' ? $match[2] : ($match[4] ?? '');

            if ($name !== '') {
                $attributes[mb_strtolower($name)] = $value;
            }
        }

        return $attributes;
    }
}
