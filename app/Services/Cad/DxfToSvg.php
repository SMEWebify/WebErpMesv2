<?php

namespace App\Services\Cad;

/**
 * Aperçu SVG d'un DXF 2D, pour les documents imprimés (fiche suiveuse).
 *
 * Le navigateur dessine les DXF avec dxf-parser + three.js
 * (resources/js/lib/dxf/toThree.js) ; un PDF n'a pas ce moteur. On reprend ici
 * les mêmes entités — LINE, ARC, CIRCLE, LWPOLYLINE / POLYLINE (arrondis
 * compris), ELLIPSE, SPLINE et INSERT de bloc — aplaties en segments : DomPDF
 * (php-svg-lib) rend fidèlement des polylignes, beaucoup moins les arcs SVG
 * et les transformations imbriquées. Textes et cotes ne sont pas dessinés.
 */
class DxfToSvg
{
    private const ARC_SEGMENTS = 48;

    private const MAX_INSERT_DEPTH = 4;

    /** Garde-fou : un DXF de plan d'ensemble ne doit pas produire un PDF de 50 Mo. */
    private const MAX_POINTS = 60000;

    /** @var array<string, array<int, array>> entités de chaque bloc */
    private array $blocks = [];

    private int $points = 0;

    public function render(string $dxf): ?string
    {
        $this->points = 0;
        [$entities, $this->blocks] = $this->parse($dxf);

        $paths = [];

        foreach ($entities as $entity) {
            foreach ($this->flatten($entity, [0.0, 0.0], 1.0, 1.0, 0.0, 0) as $path) {
                $paths[] = $path;
            }
        }

        $paths = array_values(array_filter($paths, fn (array $path) => count($path) >= 2));

        if ($paths === []) {
            return null;
        }

        $xs = [];
        $ys = [];

        foreach ($paths as $path) {
            foreach ($path as [$x, $y]) {
                $xs[] = $x;
                $ys[] = $y;
            }
        }

        [$minX, $maxX, $minY, $maxY] = [min($xs), max($xs), min($ys), max($ys)];
        $width = max($maxX - $minX, 1e-6);
        $height = max($maxY - $minY, 1e-6);
        $margin = max($width, $height) * 0.03;
        $stroke = max($width, $height) / 250;

        $svgPaths = '';

        foreach ($paths as $path) {
            $d = '';

            foreach ($path as $i => [$x, $y]) {
                // Y du DXF vers le haut, Y du SVG vers le bas : on retourne ici
                // plutôt que par un transform, mal suivi par php-svg-lib.
                $d .= ($i === 0 ? 'M' : 'L') . $this->n($x - $minX + $margin) . ' ' . $this->n($maxY - $y + $margin);
            }

            $svgPaths .= '<path d="' . $d . '"/>';
        }

        $viewWidth = $this->n($width + 2 * $margin);
        $viewHeight = $this->n($height + 2 * $margin);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $viewWidth . ' ' . $viewHeight . '"'
            . ' width="' . $viewWidth . '" height="' . $viewHeight . '">'
            . '<g fill="none" stroke="#1f2937" stroke-width="' . $this->n($stroke) . '" stroke-linecap="round" stroke-linejoin="round">'
            . $svgPaths
            . '</g></svg>';
    }

    /**
     * Lit les sections BLOCKS et ENTITIES en une liste d'entités, chacune
     * réduite à son type et à ses paires (code, valeur) dans l'ordre du fichier.
     *
     * @return array{0: array<int, array>, 1: array<string, array<int, array>>}
     */
    private function parse(string $dxf): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $dxf);
        $count = count($lines);

        $section = null;
        $entities = [];
        $blocks = [];
        $blockName = null;
        $current = null;
        $polyline = null;

        $flush = function () use (&$current, &$polyline, &$entities, &$blocks, &$blockName, &$section) {
            if ($current === null) {
                return;
            }

            $entity = $current;
            $current = null;

            // POLYLINE puis VERTEX… SEQEND : les sommets rejoignent la polyligne.
            if ($entity['type'] === 'VERTEX' && $polyline !== null) {
                $polyline['vertices'][] = $entity;

                return;
            }

            if ($entity['type'] === 'POLYLINE') {
                $polyline = $entity + ['vertices' => []];

                return;
            }

            if ($entity['type'] === 'SEQEND') {
                if ($polyline === null) {
                    return;
                }

                $entity = $polyline;
                $polyline = null;
            }

            if ($section === 'BLOCKS') {
                if ($blockName !== null && ! in_array($entity['type'], ['BLOCK', 'ENDBLK'], true)) {
                    $blocks[$blockName][] = $entity;
                }

                return;
            }

            $entities[] = $entity;
        };

        for ($i = 0; $i + 1 < $count; $i += 2) {
            $code = trim($lines[$i]);
            $value = trim($lines[$i + 1]);

            if (! is_numeric($code)) {
                continue;
            }

            $code = (int) $code;

            if ($code === 0) {
                $flush();

                if ($value === 'SECTION') {
                    $section = '?';
                    continue;
                }

                if ($value === 'ENDSEC') {
                    $section = null;
                    continue;
                }

                if ($section === 'ENTITIES' || $section === 'BLOCKS') {
                    $current = ['type' => $value, 'pairs' => []];

                    if ($value === 'ENDBLK') {
                        $blockName = null;
                    }
                }

                continue;
            }

            if ($section === '?' && $code === 2) {
                $section = $value;
                continue;
            }

            if ($current !== null) {
                $current['pairs'][] = [$code, $value];

                if ($current['type'] === 'BLOCK' && $code === 2) {
                    $blockName = $value;
                }
            }
        }

        $flush();

        return [$entities, $blocks];
    }

    /**
     * @return array<int, array<int, array{0: float, 1: float}>> polylignes dans le repère du dessin
     */
    private function flatten(array $entity, array $origin, float $sx, float $sy, float $rotation, int $depth): array
    {
        if ($this->points > self::MAX_POINTS) {
            return [];
        }

        $paths = match ($entity['type']) {
            'LINE' => [[[$this->f($entity, 10), $this->f($entity, 20)], [$this->f($entity, 11), $this->f($entity, 21)]]],
            'CIRCLE' => [$this->arc($this->f($entity, 10), $this->f($entity, 20), $this->f($entity, 40), 0, 2 * M_PI)],
            'ARC' => [$this->arc(
                $this->f($entity, 10),
                $this->f($entity, 20),
                $this->f($entity, 40),
                deg2rad($this->f($entity, 50)),
                deg2rad($this->f($entity, 51)),
            )],
            'LWPOLYLINE' => [$this->lwpolyline($entity)],
            'POLYLINE' => [$this->polyline($entity)],
            'ELLIPSE' => [$this->ellipse($entity)],
            'SPLINE' => [$this->spline($entity)],
            'INSERT' => $depth < self::MAX_INSERT_DEPTH ? $this->insert($entity, $depth) : [],
            default => [],
        };

        // Repère objet (OCS) en miroir : extrusion Z négative, l'X est inversé.
        if ($entity['type'] !== 'INSERT' && $this->f($entity, 230, 1.0) < 0) {
            $paths = array_map(fn ($path) => array_map(fn ($p) => [-$p[0], $p[1]], $path), $paths);
        }

        $cos = cos($rotation);
        $sin = sin($rotation);

        $transformed = [];

        foreach ($paths as $path) {
            $out = [];

            foreach ($path as [$x, $y]) {
                $x *= $sx;
                $y *= $sy;
                $out[] = [$origin[0] + $x * $cos - $y * $sin, $origin[1] + $x * $sin + $y * $cos];
            }

            $this->points += count($out);
            $transformed[] = $out;
        }

        return $transformed;
    }

    private function insert(array $entity, int $depth): array
    {
        $block = $this->blocks[$this->s($entity, 2)] ?? [];

        if ($block === []) {
            return [];
        }

        // Les entités du bloc sont déjà placées par l'appelant : on les rend en
        // coordonnées du bloc, puis on applique échelle, rotation et position.
        $scaleX = $this->f($entity, 41, 1.0) ?: 1.0;
        $scaleY = $this->f($entity, 42, 1.0) ?: 1.0;
        $rotation = deg2rad($this->f($entity, 50));
        $at = [$this->f($entity, 10), $this->f($entity, 20)];

        $paths = [];

        foreach ($block as $child) {
            foreach ($this->flatten($child, [0.0, 0.0], 1.0, 1.0, 0.0, $depth + 1) as $path) {
                $cos = cos($rotation);
                $sin = sin($rotation);
                $paths[] = array_map(fn ($p) => [
                    $at[0] + ($p[0] * $scaleX) * $cos - ($p[1] * $scaleY) * $sin,
                    $at[1] + ($p[0] * $scaleX) * $sin + ($p[1] * $scaleY) * $cos,
                ], $path);
            }
        }

        // Déjà transformés : l'appelant ne doit pas réappliquer l'insertion.
        return $paths;
    }

    private function lwpolyline(array $entity): array
    {
        $vertices = [];
        $vertex = null;

        foreach ($entity['pairs'] as [$code, $value]) {
            if ($code === 10) {
                if ($vertex !== null) {
                    $vertices[] = $vertex;
                }

                $vertex = ['x' => (float) $value, 'y' => 0.0, 'bulge' => 0.0];
            } elseif ($code === 20 && $vertex !== null) {
                $vertex['y'] = (float) $value;
            } elseif ($code === 42 && $vertex !== null) {
                $vertex['bulge'] = (float) $value;
            }
        }

        if ($vertex !== null) {
            $vertices[] = $vertex;
        }

        return $this->bulgedPath($vertices, ((int) $this->f($entity, 70)) & 1);
    }

    private function polyline(array $entity): array
    {
        $vertices = array_map(fn (array $vertex) => [
            'x' => $this->f($vertex, 10),
            'y' => $this->f($vertex, 20),
            'bulge' => $this->f($vertex, 42),
        ], $entity['vertices'] ?? []);

        return $this->bulgedPath($vertices, ((int) $this->f($entity, 70)) & 1);
    }

    /**
     * @param  array<int, array{x: float, y: float, bulge: float}>  $vertices
     */
    private function bulgedPath(array $vertices, int $closed): array
    {
        $count = count($vertices);

        if ($count === 0) {
            return [];
        }

        $points = [];
        $last = $closed ? $count : $count - 1;

        for ($i = 0; $i < $last; $i++) {
            $from = $vertices[$i];
            $to = $vertices[($i + 1) % $count];
            $points[] = [$from['x'], $from['y']];

            if (abs($from['bulge']) > 1e-9) {
                array_push($points, ...$this->bulgeArc($from, $to, $from['bulge']));
            }
        }

        $end = $closed ? $vertices[0] : $vertices[$count - 1];
        $points[] = [$end['x'], $end['y']];

        return $points;
    }

    /**
     * Points intermédiaires de l'arc qu'un « bulge » code entre deux sommets
     * (bulge = tan(angle inclus / 4), signé selon le sens).
     */
    private function bulgeArc(array $from, array $to, float $bulge): array
    {
        $dx = $to['x'] - $from['x'];
        $dy = $to['y'] - $from['y'];
        $chord = hypot($dx, $dy);

        if ($chord < 1e-12) {
            return [];
        }

        $angle = 4 * atan($bulge);
        $radius = $chord / (2 * sin(abs($angle) / 2));
        $mid = [($from['x'] + $to['x']) / 2, ($from['y'] + $to['y']) / 2];
        $sagitta = $radius * cos(abs($angle) / 2);
        $direction = $bulge > 0 ? 1 : -1;
        $center = [
            $mid[0] - $direction * $sagitta * $dy / $chord,
            $mid[1] + $direction * $sagitta * $dx / $chord,
        ];

        $start = atan2($from['y'] - $center[1], $from['x'] - $center[0]);
        $segments = max(4, (int) ceil(self::ARC_SEGMENTS * abs($angle) / (2 * M_PI)));
        $points = [];

        for ($i = 1; $i < $segments; $i++) {
            $a = $start + $angle * $i / $segments;
            $points[] = [$center[0] + $radius * cos($a), $center[1] + $radius * sin($a)];
        }

        return $points;
    }

    private function arc(float $cx, float $cy, float $radius, float $start, float $end): array
    {
        $sweep = $end - $start;

        while ($sweep <= 0) {
            $sweep += 2 * M_PI;
        }

        $segments = max(8, (int) ceil(self::ARC_SEGMENTS * $sweep / (2 * M_PI)));
        $points = [];

        for ($i = 0; $i <= $segments; $i++) {
            $a = $start + $sweep * $i / $segments;
            $points[] = [$cx + $radius * cos($a), $cy + $radius * sin($a)];
        }

        return $points;
    }

    private function ellipse(array $entity): array
    {
        $cx = $this->f($entity, 10);
        $cy = $this->f($entity, 20);
        $mx = $this->f($entity, 11);
        $my = $this->f($entity, 21);
        $ratio = $this->f($entity, 40, 1.0);
        $start = $this->f($entity, 41);
        $end = $this->f($entity, 42, 2 * M_PI);

        $major = hypot($mx, $my);
        $minor = $major * $ratio;
        $rotation = atan2($my, $mx);
        $sweep = $end - $start;

        while ($sweep <= 0) {
            $sweep += 2 * M_PI;
        }

        $segments = max(8, (int) ceil(self::ARC_SEGMENTS * $sweep / (2 * M_PI)));
        $points = [];

        for ($i = 0; $i <= $segments; $i++) {
            $t = $start + $sweep * $i / $segments;
            $x = $major * cos($t);
            $y = $minor * sin($t);
            $points[] = [
                $cx + $x * cos($rotation) - $y * sin($rotation),
                $cy + $x * sin($rotation) + $y * cos($rotation),
            ];
        }

        return $points;
    }

    /**
     * Spline : B-spline évaluée par de Boor sur ses points de contrôle et son
     * vecteur nodal ; à défaut, les points de passage reliés.
     */
    private function spline(array $entity): array
    {
        $degree = (int) $this->f($entity, 71, 3.0);
        $knots = [];
        $controls = [];
        $fits = [];

        foreach ($entity['pairs'] as [$code, $value]) {
            match ($code) {
                40 => $knots[] = (float) $value,
                10 => $controls[] = [(float) $value, 0.0],
                20 => $controls !== [] ? $controls[array_key_last($controls)][1] = (float) $value : null,
                11 => $fits[] = [(float) $value, 0.0],
                21 => $fits !== [] ? $fits[array_key_last($fits)][1] = (float) $value : null,
                default => null,
            };
        }

        $n = count($controls);

        if ($n <= $degree || count($knots) !== $n + $degree + 1) {
            return $fits !== [] ? $fits : $controls;
        }

        $low = $knots[$degree];
        $high = $knots[$n];
        $segments = 96;
        $points = [];

        for ($i = 0; $i <= $segments; $i++) {
            $t = $low + ($high - $low) * $i / $segments;
            $points[] = $this->deBoor($t, $degree, $knots, $controls);
        }

        return $points;
    }

    private function deBoor(float $t, int $degree, array $knots, array $controls): array
    {
        $n = count($controls);
        $k = $degree;

        while ($k < $n - 1 && $t >= $knots[$k + 1]) {
            $k++;
        }

        $d = [];

        for ($j = 0; $j <= $degree; $j++) {
            $d[$j] = $controls[$k - $degree + $j];
        }

        for ($r = 1; $r <= $degree; $r++) {
            for ($j = $degree; $j >= $r; $j--) {
                $i = $k - $degree + $j;
                $den = $knots[$i + $degree - $r + 1] - $knots[$i];
                $alpha = $den == 0.0 ? 0.0 : ($t - $knots[$i]) / $den;
                $d[$j] = [
                    (1 - $alpha) * $d[$j - 1][0] + $alpha * $d[$j][0],
                    (1 - $alpha) * $d[$j - 1][1] + $alpha * $d[$j][1],
                ];
            }
        }

        return $d[$degree];
    }

    private function f(array $entity, int $code, float $default = 0.0): float
    {
        foreach ($entity['pairs'] as [$c, $value]) {
            if ($c === $code) {
                return (float) $value;
            }
        }

        return $default;
    }

    private function s(array $entity, int $code): ?string
    {
        foreach ($entity['pairs'] as [$c, $value]) {
            if ($c === $code) {
                return $value;
            }
        }

        return null;
    }

    private function n(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }
}
