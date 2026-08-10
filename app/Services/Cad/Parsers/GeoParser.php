<?php

namespace App\Services\Cad\Parsers;

use App\Services\Cad\BaseCadParser;
use App\Services\Files\FileRole;
use Illuminate\Http\UploadedFile;

/**
 * RADAN / SigmaNEST .geo (format "geo" 1.03) — text, one value per line.
 *
 *   #~1   .. ##~~          file header
 *   #~11  .. ##~~          part information, positional fields
 *   #~30  .. #~TTINFO_END  KEY@VALUE attributes (absent from macro exports)
 *   #~31  .. ##~~          point table:  P / <n> / <x> <y> <z> / |~
 *   #~33  .. #~KONT_END    one block per profile, "<n> 24 <inner>" then
 *     #~331 .. ##~~        entities, each closed by |~ :
 *                            LIN / "1 0" / "<pStart> <pEnd>"
 *                            ARC / "1 0" / "<pCenter> <pStart> <pEnd>" / "<±1>"
 *                            CIR / "1 0" / "<pCenter>" / "<radius>"
 *
 * Entities carry no coordinates: they reference the point table by index, so
 * the geometry is only usable once that table has been read.
 *
 * The bounding box is exact rather than tessellated — an arc only leaves the
 * box of its endpoints at the cardinal points it sweeps through.
 */
class GeoParser extends BaseCadParser
{
    /** Angular tolerance when comparing arc angles. */
    private const EPSILON = 1e-9;

    public static function extensions(): array
    {
        return ['geo'];
    }

    public function gedRole(): ?string
    {
        return FileRole::PLAN;
    }

    public function parse(UploadedFile $file): array
    {
        $scan = $this->scan($file);

        if (! $scan['signature']) {
            throw new \RuntimeException('Fichier GEO non reconnu');
        }

        $meta = $scan['meta'];
        $code = $meta['ident'] !== '' ? $meta['ident'] : $this->baseName($file);

        [$xSize, $ySize] = $this->dimensions($scan['bbox']);
        $svg = $this->svg($scan['shapes'], $scan['bbox']);

        $dimensions = $xSize !== null && $ySize !== null
            ? $this->number($xSize) . 'x' . $this->number($ySize) . 'mm'
            : null;

        $materialPart = $meta['material'] !== ''
            ? $meta['material'] . ($meta['thickness'] !== null ? ' ' . $this->number($meta['thickness']) . 'mm' : '')
            : null;

        return $this->result([
            'code' => $code,
            'label' => $this->label([$code, $materialPart, $dimensions]),
            'material' => $meta['material'],
            'thickness' => $meta['thickness'],
            'x_size' => $xSize,
            'y_size' => $ySize,
            // The contour, redrawn as SVG: the nesting page only reads cad2d
            // and vector files, and no viewer renders a .geo.
            'derived_svg' => $svg,
            'extra' => $this->requirements([
                $this->requirement('Plan', $meta['drawing']),
                $this->requirement('Client', $meta['customer']),
                $this->requirement('Machine', $meta['machine']),
                $this->requirement('Commande', $meta['order']),
                $this->requirement('Longueur de coupe', $scan['cut_length'] > 0 ? $scan['cut_length'] : null, 'mm'),
                $this->requirement('Nombre de contours', $scan['profile_count'] ?: null),
                $this->requirement('Contours intérieurs', $scan['inner_count'] ?: null),
                $this->requirement('Source', 'RADAN .geo'),
            ]),
        ]);
    }

    /**
     * Walk the file once, collecting the metadata blocks and the geometry.
     *
     * @return array{signature: bool, meta: array<string, mixed>, bbox: ?array<int, float>,
     *               shapes: array<int, array<string, mixed>>, cut_length: float,
     *               entity_count: int, profile_count: int, inner_count: int}
     */
    private function scan(UploadedFile $file): array
    {
        $handle = $this->open($file);

        $mode = 'idle';
        $signature = false;
        $partFields = [];
        $attributes = [];
        $points = [];
        $pendingIndex = null;
        $entity = null;
        $isInner = false;
        $declarationSeen = false;
        $profileOpen = false;

        $bbox = null;
        $shapes = [];
        $cutLength = 0.0;
        $entityCount = 0;
        $profileCount = 0;
        $innerCount = 0;

        try {
            while (($raw = fgets($handle)) !== false) {
                $line = trim($raw);

                switch ($line) {
                    case '#~1':
                        $signature = true;
                        $mode = 'idle';
                        continue 2;
                    case '#~11':
                        $mode = 'part';
                        continue 2;
                    case '#~30':
                        $mode = 'attributes';
                        continue 2;
                    case '#~31':
                        $signature = true;
                        $mode = 'points';
                        $pendingIndex = null;
                        continue 2;
                    case '#~33':
                        $mode = 'profile';
                        $isInner = false;
                        $declarationSeen = false;
                        continue 2;
                    case '#~331':
                        $mode = 'entities';
                        $profileOpen = true;
                        $entity = null;
                        continue 2;
                    case '##~~':
                    case '#~KONT_END':
                    case '#~TTINFO_END':
                    case '#~END':
                    case '#~EOF':
                        if ($mode === 'entities' && $profileOpen) {
                            $profileCount++;
                            $innerCount += $isInner ? 1 : 0;
                            $profileOpen = false;
                        }

                        // The profile header closes with ##~~ but the profile
                        // itself carries on into #~331, so that mode survives.
                        if ($mode !== 'profile') {
                            $mode = 'idle';
                        }
                        continue 2;
                }

                // Any other block marker just ends whatever was being read.
                if (str_starts_with($line, '#~')) {
                    $mode = 'idle';
                    continue;
                }

                switch ($mode) {
                    case 'part':
                        // Positional block: empty lines count as fields.
                        $partFields[] = $line;
                        break;

                    case 'attributes':
                        $at = strpos($line, '@');

                        if ($at !== false && $at > 0) {
                            $attributes[substr($line, 0, $at)] = substr($line, $at + 1);
                        }
                        break;

                    case 'points':
                        if ($line === 'P' || $line === '|~') {
                            $pendingIndex = null;
                            break;
                        }

                        if ($pendingIndex === null) {
                            $pendingIndex = is_numeric($line) ? (int) $line : null;
                            break;
                        }

                        $coordinates = preg_split('/\s+/', $line);

                        if (count($coordinates) >= 2 && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
                            $points[$pendingIndex] = [(float) $coordinates[0], (float) $coordinates[1]];
                        }

                        $pendingIndex = null;
                        break;

                    case 'profile':
                        // "<n> 24 <inner>" — 24 is the RADAN contour code.
                        if (! $declarationSeen && preg_match('/^(\d+)\s+(\d+)\s+(\d+)$/', $line, $matches)) {
                            $isInner = $matches[3] !== '0';
                            $declarationSeen = true;
                        }
                        break;

                    case 'entities':
                        if ($line === 'LIN' || $line === 'ARC' || $line === 'CIR') {
                            $entity = ['type' => $line, 'fields' => []];
                            break;
                        }

                        if ($line === '|~') {
                            $segment = $entity !== null ? $this->segment($entity, $points) : null;

                            if ($segment !== null) {
                                $entityCount++;
                                $cutLength += $segment['length'];
                                $bbox = $this->extend($bbox, $segment['bbox']);
                                $shapes[] = $segment['shape'];
                            }

                            $entity = null;
                            break;
                        }

                        if ($entity !== null) {
                            $entity['fields'][] = $line;
                        }
                        break;
                }
            }
        } finally {
            fclose($handle);
        }

        return [
            'signature' => $signature,
            'meta' => $this->meta($partFields, $attributes),
            'bbox' => $bbox,
            'shapes' => $shapes,
            'cut_length' => round($cutLength, 3),
            'entity_count' => $entityCount,
            'profile_count' => $profileCount,
            'inner_count' => $innerCount,
        ];
    }

    /**
     * Merge the positional part block with the KEY@VALUE attributes, which win
     * when the exporter wrote them.
     *
     * @param  array<int, string>  $fields
     * @param  array<string, string>  $attributes
     * @return array<string, mixed>
     */
    private function meta(array $fields, array $attributes): array
    {
        $meta = [
            'ident' => trim($fields[0] ?? ''),
            'drawing' => trim($fields[1] ?? ''),
            'customer' => trim($fields[2] ?? ''),
            'operator' => trim($fields[3] ?? ''),
            'order' => trim($fields[4] ?? ''),
            'material' => trim($fields[5] ?? ''),
            'machine' => '',
            'thickness' => null,
        ];

        $thickness = $fields[6] ?? null;

        if ($thickness !== null && is_numeric($thickness) && (float) $thickness > 0) {
            $meta['thickness'] = (float) $thickness;
        }

        $overrides = [
            'IDENT' => 'ident',
            'MAT' => 'material',
            'BEZCH' => 'drawing',
            'TKUND' => 'customer',
            'MASCH' => 'machine',
        ];

        foreach ($overrides as $key => $field) {
            $value = trim($attributes[$key] ?? '');

            if ($value !== '') {
                $meta[$field] = $value;
            }
        }

        return $meta;
    }

    /**
     * Length, bounding box and drawable shape of one entity, or null when it
     * references a point the table does not hold.
     *
     * The useful fields are read from the end: the prefix ("1 0", layer and
     * type) varies with the RADAN version and the export macro.
     *
     * @param  array{type: string, fields: array<int, string>}  $entity
     * @param  array<int, array<int, float>>  $points
     * @return array{length: float, bbox: array<int, float>, shape: array<string, mixed>}|null
     */
    private function segment(array $entity, array $points): ?array
    {
        $fields = array_values(array_filter($entity['fields'], fn (string $field) => $field !== ''));
        $count = count($fields);

        if ($count === 0) {
            return null;
        }

        $last = $fields[$count - 1];

        if ($entity['type'] === 'LIN') {
            $refs = $this->references($last);
            $start = $points[$refs[0] ?? -1] ?? null;
            $end = $points[$refs[1] ?? -1] ?? null;

            if ($start === null || $end === null) {
                return null;
            }

            return [
                'length' => hypot($end[0] - $start[0], $end[1] - $start[1]),
                'bbox' => $this->bounds([$start, $end]),
                'shape' => ['type' => 'line', 'from' => $start, 'to' => $end],
            ];
        }

        if ($entity['type'] === 'CIR') {
            $radius = (float) $last;
            $center = $count >= 2 ? ($points[(int) $fields[$count - 2]] ?? null) : null;

            if ($center === null || $radius <= 0) {
                return null;
            }

            return [
                'length' => 2 * M_PI * $radius,
                'bbox' => [$center[0] - $radius, $center[1] - $radius, $center[0] + $radius, $center[1] + $radius],
                'shape' => ['type' => 'circle', 'center' => $center, 'radius' => $radius],
            ];
        }

        if ($entity['type'] === 'ARC') {
            $refs = $this->references($fields[$count - 2] ?? '');
            $center = $points[$refs[0] ?? -1] ?? null;
            $start = $points[$refs[1] ?? -1] ?? null;
            $end = $points[$refs[2] ?? -1] ?? null;

            if ($center === null || $start === null || $end === null) {
                return null;
            }

            return $this->arc($center, $start, $end, (float) $last >= 0);
        }

        return null;
    }

    /**
     * Length, exact bounding box and drawable shape of an arc.
     *
     * @param  array<int, float>  $center
     * @param  array<int, float>  $start
     * @param  array<int, float>  $end
     * @return array{length: float, bbox: array<int, float>, shape: array<string, mixed>}
     */
    private function arc(array $center, array $start, array $end, bool $counterClockwise): array
    {
        $radius = hypot($start[0] - $center[0], $start[1] - $center[1]);
        $from = atan2($start[1] - $center[1], $start[0] - $center[0]);
        $to = atan2($end[1] - $center[1], $end[0] - $center[0]);

        if ($counterClockwise) {
            while ($to <= $from + self::EPSILON) {
                $to += 2 * M_PI;
            }
        } else {
            while ($to >= $from - self::EPSILON) {
                $to -= 2 * M_PI;
            }
        }

        $extremes = [$start, $end];
        $low = min($from, $to);
        $high = max($from, $to);

        // An arc reaches beyond its endpoints only where it crosses an axis.
        for ($quarter = -6; $quarter <= 6; $quarter++) {
            $angle = $quarter * M_PI_2;

            if ($angle >= $low - self::EPSILON && $angle <= $high + self::EPSILON) {
                $extremes[] = [$center[0] + $radius * cos($angle), $center[1] + $radius * sin($angle)];
            }
        }

        return [
            'length' => $radius * abs($to - $from),
            'bbox' => $this->bounds($extremes),
            'shape' => [
                'type' => 'arc',
                'from' => $start,
                'to' => $end,
                'radius' => $radius,
                'ccw' => $counterClockwise,
                'large' => abs($to - $from) > M_PI,
            ],
        ];
    }

    /**
     * Redraw the contour as an SVG, in millimetres and normalised to the origin.
     *
     * @param  array<int, array<string, mixed>>  $shapes
     * @param  array<int, float>|null  $bbox
     */
    private function svg(array $shapes, ?array $bbox): ?string
    {
        if ($shapes === [] || $bbox === null) {
            return null;
        }

        $width = $bbox[2] - $bbox[0];
        $height = $bbox[3] - $bbox[1];

        if ($width <= 0 || $height <= 0) {
            return null;
        }

        // CAD draws Y upwards and SVG downwards: mirroring keeps the part the
        // right way up instead of flipping it against its drawing.
        $x = fn (float $value): string => $this->number($value - $bbox[0], 3);
        $y = fn (float $value): string => $this->number($bbox[3] - $value, 3);
        $length = fn (float $value): string => $this->number($value, 3);

        $elements = [];

        foreach ($shapes as $shape) {
            $elements[] = match ($shape['type']) {
                'line' => sprintf(
                    '<line x1="%s" y1="%s" x2="%s" y2="%s"/>',
                    $x($shape['from'][0]), $y($shape['from'][1]),
                    $x($shape['to'][0]), $y($shape['to'][1]),
                ),
                'circle' => sprintf(
                    '<circle cx="%s" cy="%s" r="%s"/>',
                    $x($shape['center'][0]), $y($shape['center'][1]), $length($shape['radius']),
                ),
                // Mirroring reverses the orientation, so the sweep flag flips.
                'arc' => sprintf(
                    '<path d="M %s %s A %s %s 0 %d %d %s %s"/>',
                    $x($shape['from'][0]), $y($shape['from'][1]),
                    $length($shape['radius']), $length($shape['radius']),
                    $shape['large'] ? 1 : 0,
                    $shape['ccw'] ? 0 : 1,
                    $x($shape['to'][0]), $y($shape['to'][1]),
                ),
                default => '',
            };
        }

        $elements = array_filter($elements);

        if ($elements === []) {
            return null;
        }

        // A stroke sized off the part stays visible on a small bracket without
        // swallowing the contour of a large panel.
        $stroke = $this->number(max(0.2, min(1.0, max($width, $height) / 400)), 2);

        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$s %2$s" width="%1$smm" height="%2$smm">' . "\n"
            . '    <g fill="none" stroke="#000" stroke-width="%3$s">' . "\n"
            . '        %4$s' . "\n"
            . '    </g>' . "\n"
            . '</svg>' . "\n",
            $this->number($width, 3),
            $this->number($height, 3),
            $stroke,
            implode("\n        ", $elements),
        );
    }

    /**
     * Point indexes held by an entity field such as "12 47".
     *
     * @return array<int, int>
     */
    private function references(string $field): array
    {
        $parts = preg_split('/\s+/', trim($field)) ?: [];

        return array_map('intval', array_values(array_filter($parts, 'is_numeric')));
    }

    /**
     * @param  array<int, array<int, float>>  $points
     * @return array<int, float>
     */
    private function bounds(array $points): array
    {
        $xs = array_column($points, 0);
        $ys = array_column($points, 1);

        return [min($xs), min($ys), max($xs), max($ys)];
    }

    /**
     * @param  array<int, float>|null  $bbox
     * @param  array<int, float>  $other
     * @return array<int, float>
     */
    private function extend(?array $bbox, array $other): array
    {
        if ($bbox === null) {
            return $other;
        }

        return [
            min($bbox[0], $other[0]),
            min($bbox[1], $other[1]),
            max($bbox[2], $other[2]),
            max($bbox[3], $other[3]),
        ];
    }

    /**
     * @param  array<int, float>|null  $bbox
     * @return array{0: ?float, 1: ?float}
     */
    private function dimensions(?array $bbox): array
    {
        if ($bbox === null) {
            return [null, null];
        }

        $width = round($bbox[2] - $bbox[0], 3);
        $height = round($bbox[3] - $bbox[1], 3);

        return $width > 0 || $height > 0 ? [$width, $height] : [null, null];
    }
}
