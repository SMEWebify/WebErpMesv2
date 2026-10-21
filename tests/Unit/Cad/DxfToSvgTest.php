<?php

namespace Tests\Unit\Cad;

use App\Services\Cad\DxfToSvg;
use PHPUnit\Framework\TestCase;

class DxfToSvgTest extends TestCase
{
    public function test_lines_circles_and_bulged_polylines_become_svg_paths(): void
    {
        $dxf = $this->dxf([
            ['LINE', [10 => 0, 20 => 0, 11 => 100, 21 => 0]],
            ['CIRCLE', [10 => 50, 20 => 25, 40 => 5]],
            // Rectangle 100 × 50 fermé, un côté en arc (bulge 1 = demi-cercle).
            ['LWPOLYLINE', [90 => 4, 70 => 1, 10 => 0, 20 => 0, '10b' => 100, '20b' => 0, '42b' => 1, '10c' => 100, '20c' => 50, '10d' => 0, '20d' => 50]],
            ['TEXT', [10 => 500, 20 => 500, 1 => 'ignoré']],
        ]);

        $svg = (new DxfToSvg())->render($dxf);

        $this->assertNotNull($svg);
        $this->assertSame(3, substr_count($svg, '<path '));
        // Le texte ne dessine rien : la boîte reste celle de la géométrie
        // (100 + le demi-cercle de rayon 25 en X, 50 en Y, plus 3 % de marge).
        preg_match('/viewBox="0 0 ([\d.]+) ([\d.]+)"/', $svg, $box);
        $this->assertEqualsWithDelta(125 + 2 * 3.75, (float) $box[1], 0.5);
        $this->assertEqualsWithDelta(50 + 2 * 3.75, (float) $box[2], 0.5);
    }

    public function test_block_inserts_are_placed_and_scaled(): void
    {
        $dxf = "0\nSECTION\n2\nBLOCKS\n0\nBLOCK\n2\nTROU\n0\nCIRCLE\n10\n0\n20\n0\n40\n1\n0\nENDBLK\n0\nENDSEC\n"
            . $this->dxf([
                ['LINE', [10 => 0, 20 => 0, 11 => 10, 21 => 0]],
                ['INSERT', [2 => 'TROU', 10 => 20, 20 => 0, 41 => 2, 42 => 2]],
            ]);

        $svg = (new DxfToSvg())->render($dxf);

        // De x = 0 (ligne) à x = 22 (trou de rayon 2 centré en 20).
        preg_match('/viewBox="0 0 ([\d.]+)/', $svg, $box);
        $this->assertEqualsWithDelta(22 * 1.06, (float) $box[1], 0.2);
    }

    public function test_a_file_without_geometry_gives_nothing(): void
    {
        $this->assertNull((new DxfToSvg())->render("0\nSECTION\n2\nENTITIES\n0\nENDSEC\n0\nEOF\n"));
        $this->assertNull((new DxfToSvg())->render('pas un dxf'));
    }

    private function dxf(array $entities): string
    {
        $out = "0\nSECTION\n2\nENTITIES\n";

        foreach ($entities as [$type, $pairs]) {
            $out .= "0\n{$type}\n";

            foreach ($pairs as $code => $value) {
                $out .= (int) $code . "\n{$value}\n";
            }
        }

        return $out . "0\nENDSEC\n0\nEOF\n";
    }
}
