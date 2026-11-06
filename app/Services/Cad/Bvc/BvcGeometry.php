<?php

namespace App\Services\Cad\Bvc;

/**
 * Measures and moves the contours read by BvcJob.
 *
 * A contour is a start point and segments, each a target point plus the swept
 * angle in degrees: 0 for a line, > 0 for a counter-clockwise arc. An arc
 * carries no centre, it is rebuilt from the chord and the swept angle.
 */
class BvcGeometry
{
    /** Below this, a swept angle or a chord is taken as zero. */
    private const EPSILON = 1e-9;

    /**
     * Size, area and cut length of a part from its contours.
     *
     * @param  array<int, array<string, mixed>>  $contours
     * @return array{bbox: ?array<int, float>, width: ?float, height: ?float, area: float,
     *               cut_length: float, contour_count: int, interior_count: int}
     */
    public static function measure(array $contours): array
    {
        $bbox = null;
        $exterior = 0.0;
        $holes = 0.0;
        $length = 0.0;
        $interiors = 0;

        foreach ($contours as $contour) {
            $length += self::length($contour);
            $area = abs(self::signedArea($contour));

            if ($contour['interior']) {
                $holes += $area;
                $interiors++;
            } else {
                $exterior += $area;
                $bbox = self::union($bbox, self::bbox($contour));
            }
        }

        return [
            'bbox' => $bbox,
            'width' => $bbox !== null ? round($bbox[2] - $bbox[0], 3) : null,
            'height' => $bbox !== null ? round($bbox[3] - $bbox[1], 3) : null,
            'area' => round(max(0.0, $exterior - $holes), 3),
            'cut_length' => round($length, 3),
            'contour_count' => count($contours),
            'interior_count' => $interiors,
        ];
    }

    /**
     * Same contour, turned about the origin then moved by (x, y).
     *
     * A rotation keeps every swept angle unchanged.
     *
     * @param  array<string, mixed>  $contour
     * @return array<string, mixed>
     */
    public static function place(array $contour, float $x, float $y, float $rotation): array
    {
        $radians = deg2rad($rotation);
        $cos = cos($radians);
        $sin = sin($radians);
        $move = fn (array $p): array => [$p[0] * $cos - $p[1] * $sin + $x, $p[0] * $sin + $p[1] * $cos + $y];

        $contour['start'] = $move($contour['start']);
        $contour['segments'] = array_map(
            fn (array $segment) => ['to' => $move($segment['to'])] + $segment,
            $contour['segments'],
        );

        return $contour;
    }

    /**
     * Centre and radius of the arc from $from to $to sweeping $sweep degrees,
     * or null for a straight segment.
     *
     * @param  array{0: float, 1: float}  $from
     * @param  array{0: float, 1: float}  $to
     * @return array{center: array{0: float, 1: float}, radius: float}|null
     */
    public static function arc(array $from, array $to, float $sweep): ?array
    {
        $chord = hypot($to[0] - $from[0], $to[1] - $from[1]);
        $theta = deg2rad($sweep);

        if (abs($theta) < self::EPSILON || $chord < self::EPSILON) {
            return null;
        }

        $radius = $chord / (2 * sin(abs($theta) / 2));

        // The centre sits on the bisector of the chord, on the left of it for a
        // counter-clockwise arc of less than half a turn.
        $apothem = sqrt(max(0.0, $radius * $radius - ($chord / 2) ** 2));
        $side = ($theta > 0) === (abs($theta) < M_PI) ? 1.0 : -1.0;
        $normal = [-($to[1] - $from[1]) / $chord, ($to[0] - $from[0]) / $chord];

        return [
            'center' => [
                ($from[0] + $to[0]) / 2 + $side * $apothem * $normal[0],
                ($from[1] + $to[1]) / 2 + $side * $apothem * $normal[1],
            ],
            'radius' => $radius,
        ];
    }

    /**
     * Exact bounding box: an arc leaves the box of its endpoints only at the
     * cardinal points it sweeps through.
     *
     * @param  array<string, mixed>  $contour
     * @return array<int, float>
     */
    public static function bbox(array $contour): array
    {
        $points = [$contour['start']];
        $from = $contour['start'];

        foreach ($contour['segments'] as $segment) {
            $points[] = $segment['to'];
            $arc = self::arc($from, $segment['to'], $segment['sweep']);

            if ($arc !== null) {
                $start = atan2($from[1] - $arc['center'][1], $from[0] - $arc['center'][0]);
                $theta = deg2rad($segment['sweep']);

                for ($quarter = -8; $quarter <= 8; $quarter++) {
                    $angle = $quarter * M_PI_2;
                    $swept = $theta > 0 ? $angle - $start : $start - $angle;

                    if ($swept > self::EPSILON && $swept < abs($theta) - self::EPSILON) {
                        $points[] = [
                            $arc['center'][0] + $arc['radius'] * cos($angle),
                            $arc['center'][1] + $arc['radius'] * sin($angle),
                        ];
                    }
                }
            }

            $from = $segment['to'];
        }

        $xs = array_column($points, 0);
        $ys = array_column($points, 1);

        return [min($xs), min($ys), max($xs), max($ys)];
    }

    /**
     * @param  array<int, float>|null  $bbox
     * @param  array<int, float>  $other
     * @return array<int, float>
     */
    public static function union(?array $bbox, array $other): array
    {
        if ($bbox === null) {
            return $other;
        }

        return [min($bbox[0], $other[0]), min($bbox[1], $other[1]), max($bbox[2], $other[2]), max($bbox[3], $other[3])];
    }

    /**
     * @param  array<string, mixed>  $contour
     */
    private static function length(array $contour): float
    {
        $length = 0.0;
        $from = $contour['start'];

        foreach ($contour['segments'] as $segment) {
            $arc = self::arc($from, $segment['to'], $segment['sweep']);

            $length += $arc !== null
                ? $arc['radius'] * abs(deg2rad($segment['sweep']))
                : hypot($segment['to'][0] - $from[0], $segment['to'][1] - $from[1]);

            $from = $segment['to'];
        }

        return $length;
    }

    /**
     * Shoelace over the vertices, plus the circular segment each arc adds to
     * the left of its chord (counter-clockwise) or takes away (clockwise).
     *
     * @param  array<string, mixed>  $contour
     */
    private static function signedArea(array $contour): float
    {
        $twice = 0.0;
        $segments = 0.0;
        $from = $contour['start'];

        foreach ($contour['segments'] as $segment) {
            $to = $segment['to'];
            $twice += $from[0] * $to[1] - $to[0] * $from[1];

            $arc = self::arc($from, $to, $segment['sweep']);

            if ($arc !== null) {
                $theta = abs(deg2rad($segment['sweep']));
                $segments += ($segment['sweep'] > 0 ? 1 : -1) * ($theta - sin($theta)) * $arc['radius'] ** 2 / 2;
            }

            $from = $to;
        }

        return $twice / 2 + $segments;
    }
}
