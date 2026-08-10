<?php

namespace App\Services\Cad\Parsers;

use App\Services\Cad\BaseCadParser;
use App\Services\Files\FileRole;
use Illuminate\Http\UploadedFile;

/**
 * AutoCAD DXF — pairs of lines, a group code then its value.
 *
 * The overall size comes from $EXTMIN / $EXTMAX in the HEADER section, which
 * every CAD writes when it saves a drawing. Those extents are stale on files
 * exported by a script, so the ENTITIES coordinates are scanned as a fallback.
 *
 * A DXF holds no material nor thickness: sheet metal shops carry them on the
 * layer names, and there is no convention shared across customers to read.
 */
class DxfParser extends BaseCadParser
{
    /** AutoCAD writes 1e20 in the extents when it has never computed them. */
    private const UNSET_EXTENT = 1e19;

    /**
     * $INSUNITS values, in millimetres per unit.
     *
     * @var array<int, array{0: float, 1: string}>
     */
    private const UNITS = [
        1 => [25.4, 'in'],
        2 => [304.8, 'ft'],
        4 => [1.0, 'mm'],
        5 => [10.0, 'cm'],
        6 => [1000.0, 'm'],
        13 => [0.001, 'µm'],
        14 => [100.0, 'dm'],
    ];

    public static function extensions(): array
    {
        return ['dxf'];
    }

    public function gedRole(): ?string
    {
        return FileRole::PLAN;
    }

    public function parse(UploadedFile $file): array
    {
        if (str_starts_with($this->head($file, 22), 'AutoCAD Binary DXF')) {
            throw new \RuntimeException('DXF binaire non pris en charge, exportez en DXF ASCII');
        }

        $scan = $this->scan($file);

        if (! $scan['recognised']) {
            throw new \RuntimeException('Fichier DXF non reconnu');
        }

        [$factor, $unit] = self::UNITS[$scan['units']] ?? [1.0, 'mm'];

        $bbox = $scan['extents'] ?? $scan['entities'];
        $xSize = $ySize = null;

        if ($bbox !== null) {
            $xSize = round(($bbox[2] - $bbox[0]) * $factor, 3);
            $ySize = round(($bbox[3] - $bbox[1]) * $factor, 3);

            if ($xSize <= 0 && $ySize <= 0) {
                $xSize = $ySize = null;
            }
        }

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
                $this->requirement('Unité du fichier', $unit),
                $this->requirement(
                    'Cotes',
                    $scan['extents'] !== null ? 'extents du fichier' : ($bbox !== null ? 'calculées sur les entités' : null),
                ),
                $this->requirement('Source', 'DXF'),
            ]),
        ]);
    }

    /**
     * Read the header extents, the drawing units and, as a fallback, the span
     * of every coordinate found in the ENTITIES section.
     *
     * @return array{recognised: bool, extents: ?array<int, float>, entities: ?array<int, float>, units: int}
     */
    private function scan(UploadedFile $file): array
    {
        $handle = $this->open($file);

        $section = null;
        $expectSectionName = false;
        $variable = null;
        $recognised = false;

        $extents = ['$EXTMIN' => [], '$EXTMAX' => []];
        $units = 0;
        $xs = [];
        $ys = [];

        try {
            while (($rawCode = fgets($handle)) !== false) {
                $rawValue = fgets($handle);

                if ($rawValue === false) {
                    break;
                }

                $code = trim($rawCode);
                $value = trim($rawValue);

                if (! is_numeric($code)) {
                    continue;
                }

                $code = (int) $code;

                if ($code === 0 && $value === 'SECTION') {
                    $expectSectionName = true;
                    $recognised = true;
                    continue;
                }

                if ($code === 2 && $expectSectionName) {
                    $section = $value;
                    $expectSectionName = false;
                    continue;
                }

                if ($code === 0 && $value === 'ENDSEC') {
                    $section = null;
                    $variable = null;
                    continue;
                }

                if ($section === 'HEADER') {
                    if ($code === 9) {
                        $variable = $value;
                        continue;
                    }

                    if ($variable === '$INSUNITS' && $code === 70) {
                        $units = (int) $value;
                        continue;
                    }

                    if (isset($extents[$variable]) && ($code === 10 || $code === 20)) {
                        $extents[$variable][$code] = (float) $value;
                    }

                    continue;
                }

                if ($section === 'ENTITIES') {
                    // 10..18 and 20..28 are the X and Y of every vertex an
                    // entity can carry, whatever its type.
                    if ($code >= 10 && $code <= 18) {
                        $xs[] = (float) $value;
                    } elseif ($code >= 20 && $code <= 28) {
                        $ys[] = (float) $value;
                    }
                }
            }
        } finally {
            fclose($handle);
        }

        return [
            'recognised' => $recognised,
            'extents' => $this->extentsBox($extents),
            'entities' => $xs !== [] && $ys !== [] ? [min($xs), min($ys), max($xs), max($ys)] : null,
            'units' => $units,
        ];
    }

    /**
     * Turn the two header variables into a bounding box, discarding the
     * placeholder AutoCAD leaves when the extents were never computed.
     *
     * @param  array<string, array<int, float>>  $extents
     * @return array<int, float>|null
     */
    private function extentsBox(array $extents): ?array
    {
        $min = $extents['$EXTMIN'];
        $max = $extents['$EXTMAX'];

        if (! isset($min[10], $min[20], $max[10], $max[20])) {
            return null;
        }

        foreach ([$min[10], $min[20], $max[10], $max[20]] as $value) {
            if (! is_finite($value) || abs($value) > self::UNSET_EXTENT) {
                return null;
            }
        }

        if ($max[10] <= $min[10] && $max[20] <= $min[20]) {
            return null;
        }

        return [$min[10], $min[20], $max[10], $max[20]];
    }
}
