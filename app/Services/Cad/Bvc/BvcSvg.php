<?php

namespace App\Services\Cad\Bvc;

/**
 * Draws the parts and the nests of a BvcJob as SVG, in millimetres.
 *
 * CAD draws Y upwards and SVG downwards: every drawing is mirrored on its
 * bounding box so that it reads the right way up, which also flips the sweep
 * flag of every arc.
 */
class BvcSvg
{
    /**
     * Contour of one part, normalised to the origin — the shape the nesting
     * page and the line thumbnail read.
     *
     * @param  array<string, mixed>  $part  part of BvcJob::$parts, measured
     */
    public static function part(array $part): ?string
    {
        if (empty($part['contours']) || empty($part['bbox'])) {
            return null;
        }

        $bbox = $part['bbox'];
        $width = $bbox[2] - $bbox[0];
        $height = $bbox[3] - $bbox[1];

        if ($width <= 0 || $height <= 0) {
            return null;
        }

        $paths = array_map(
            fn (array $contour) => '<path d="' . self::pathData($contour, $bbox) . '"/>',
            $part['contours'],
        );

        // A stroke sized off the part stays visible on a small bracket without
        // swallowing the contour of a large panel.
        $stroke = self::number(max(0.2, min(1.0, max($width, $height) / 400)));

        return self::document($width, $height,
            '    <g fill="none" stroke="#000" stroke-width="' . $stroke . '">' . "\n"
            . '        ' . implode("\n        ", $paths) . "\n"
            . '    </g>');
    }

    /**
     * Whole sheet of one nest: sheet outline, every placed part with its
     * reference, the residual sheet kept for later and its separation cut.
     *
     * @param  array<string, mixed>  $nest  nest of BvcJob::$nests
     * @param  array<int, array<string, mixed>>  $parts  BvcJob::$parts
     */
    public static function nest(array $nest, array $parts): ?string
    {
        $sheet = $nest['sheet'] ?? null;

        if ($sheet === null || $sheet['width'] <= 0 || $sheet['height'] <= 0) {
            return null;
        }

        $box = [0.0, 0.0, $sheet['width'], $sheet['height']];
        $x = fn (float $value) => self::number($value);
        $y = fn (float $value) => self::number($sheet['height'] - $value);

        $elements = [sprintf(
            '<rect class="sheet" x="0" y="0" width="%s" height="%s"/>',
            $x($sheet['width']), $x($sheet['height']),
        )];

        foreach ($nest['residual_sheets'] as $residual) {
            $elements[] = sprintf(
                '<rect class="residual" x="%s" y="%s" width="%s" height="%s"/>',
                $x($residual['x']), $y($residual['y'] + $residual['height']),
                $x($residual['width']), $x($residual['height']),
            );
        }

        foreach ($nest['placements'] as $placement) {
            $part = $parts[$placement['part_id']] ?? null;

            if ($part === null || empty($part['contours'])) {
                continue;
            }

            // Holes are drawn inside the exterior path, so evenodd leaves them empty.
            $data = [];

            foreach ($part['contours'] as $contour) {
                $data[] = self::pathData(BvcGeometry::place($contour, $placement['x'], $placement['y'], $placement['rotation']), $box);
            }

            $elements[] = '<path class="part" d="' . implode(' ', $data) . '"/>';

            if (! empty($part['bbox'])) {
                [$cx, $cy] = self::center($part['bbox'], $placement);
                $elements[] = sprintf('<text x="%s" y="%s">%s</text>', $x($cx), $y($cy), htmlspecialchars($part['reference'], ENT_XML1));
            }
        }

        foreach ($nest['residual_cuts'] as $cut) {
            foreach ($cut['contours'] as $contour) {
                $elements[] = '<path class="cut" d="' . self::pathData(BvcGeometry::place($contour, $cut['x'], $cut['y'], $cut['rotation']), $box) . '"/>';
            }
        }

        // Labels scale with the sheet so they stay legible once the drawing is
        // shrunk to fit a screen.
        $font = self::number(max(8, $sheet['width'] / 150));
        $stroke = self::number(max(0.5, $sheet['width'] / 1500));

        $style = '.sheet{fill:#eceae4;stroke:#555;stroke-width:' . $stroke . '}'
            . '.residual{fill:#dbe9d4;stroke:#3a7d2c;stroke-width:' . $stroke . ';stroke-dasharray:' . self::number($stroke * 8) . '}'
            . '.part{fill:#4a7fb5;fill-rule:evenodd;stroke:#1d3c5c;stroke-width:' . $stroke . '}'
            . '.cut{fill:none;stroke:#3a7d2c;stroke-width:' . self::number($stroke * 2) . '}'
            . 'text{font-family:sans-serif;font-size:' . $font . 'px;fill:#fff;text-anchor:middle;dominant-baseline:middle}';

        return self::document($sheet['width'], $sheet['height'],
            '    <style>' . $style . '</style>' . "\n"
            . '    ' . implode("\n    ", $elements));
    }

    /**
     * Path data of a contour, mirrored on $box.
     *
     * @param  array<string, mixed>  $contour
     * @param  array<int, float>  $box
     */
    private static function pathData(array $contour, array $box): string
    {
        $point = fn (array $p) => self::number($p[0] - $box[0]) . ' ' . self::number($box[3] - $p[1]);

        $data = 'M ' . $point($contour['start']);
        $from = $contour['start'];

        foreach ($contour['segments'] as $segment) {
            $arc = BvcGeometry::arc($from, $segment['to'], $segment['sweep']);

            $data .= $arc === null
                ? ' L ' . $point($segment['to'])
                : sprintf(
                    ' A %1$s %1$s 0 %2$d %3$d %4$s',
                    self::number($arc['radius']),
                    abs($segment['sweep']) > 180 ? 1 : 0,
                    // Mirroring reverses the orientation, so the sweep flag flips.
                    $segment['sweep'] > 0 ? 0 : 1,
                    $point($segment['to']),
                );

            $from = $segment['to'];
        }

        return $data . (($contour['closed'] ?? false) ? ' Z' : '');
    }

    /**
     * Centre of the part bounding box once placed on the sheet.
     *
     * @param  array<int, float>  $bbox
     * @param  array<string, mixed>  $placement
     * @return array{0: float, 1: float}
     */
    private static function center(array $bbox, array $placement): array
    {
        $radians = deg2rad($placement['rotation']);
        $cx = ($bbox[0] + $bbox[2]) / 2;
        $cy = ($bbox[1] + $bbox[3]) / 2;

        return [
            $cx * cos($radians) - $cy * sin($radians) + $placement['x'],
            $cx * sin($radians) + $cy * cos($radians) + $placement['y'],
        ];
    }

    private static function document(float $width, float $height, string $body): string
    {
        return sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$s %2$s" width="%1$smm" height="%2$smm">' . "\n"
            . '%3$s' . "\n"
            . '</svg>' . "\n",
            self::number($width),
            self::number($height),
            $body,
        );
    }

    /**
     * Trim a float down to a readable string: 12.500 → "12.5", 12.000 → "12".
     */
    private static function number(float $value): string
    {
        $formatted = number_format($value, 3, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '-0' ? '0' : $formatted;
    }
}
