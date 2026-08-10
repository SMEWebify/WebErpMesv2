<?php

namespace Tests\Unit;

use App\Services\Cad\CadParserFactory;
use App\Services\Cad\Parsers\DxfParser;
use App\Services\Cad\Parsers\GeoParser;
use App\Services\Cad\Parsers\StepParser;
use App\Services\Cad\Parsers\SvgParser;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CadParsersTest extends TestCase
{
    /** @var array<int, string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_factory_resolves_every_supported_extension(): void
    {
        $this->assertInstanceOf(DxfParser::class, CadParserFactory::for('dxf'));
        $this->assertInstanceOf(StepParser::class, CadParserFactory::for('STP'));
        $this->assertInstanceOf(SvgParser::class, CadParserFactory::for('.svg'));
        $this->assertInstanceOf(GeoParser::class, CadParserFactory::for('geo'));
        $this->assertNull(CadParserFactory::for('pdf'));
        $this->assertStringContainsString('.dxf', CadParserFactory::accept());
    }

    public function test_dxf_size_comes_from_the_header_extents(): void
    {
        $result = (new DxfParser())->parse($this->upload('part.dxf', $this->dxf()));

        $this->assertSame(100.0, $result['x_size']);
        $this->assertSame(50.0, $result['y_size']);
        $this->assertSame('part', $result['code']);
        $this->assertSame('part - 100x50mm', $result['label']);
        $this->assertContains(['label' => 'Unité du fichier', 'value' => 'mm'], $result['extra']);
    }

    public function test_dxf_falls_back_on_the_entity_coordinates(): void
    {
        $withoutExtents = preg_replace('/9\n\$EXTMIN.*?9\n\$EXTMAX\n10\n110\.0\n20\n70\.0\n30\n0\.0\n/s', '', $this->dxf());

        $result = (new DxfParser())->parse($this->upload('part.dxf', $withoutExtents));

        $this->assertSame(100.0, $result['x_size']);
        $this->assertSame(50.0, $result['y_size']);
        $this->assertContains(['label' => 'Cotes', 'value' => 'calculées sur les entités'], $result['extra']);
    }

    public function test_dxf_rejects_a_binary_file(): void
    {
        $this->expectExceptionMessage('DXF binaire');

        (new DxfParser())->parse($this->upload('part.dxf', "AutoCAD Binary DXF\r\n\x1a\x00"));
    }

    public function test_svg_prefers_the_explicit_dimensions(): void
    {
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="100mm" height="50mm" viewBox="0 0 283.46 141.73"></svg>';

        $result = (new SvgParser())->parse($this->upload('flasque.svg', $svg));

        $this->assertSame(100.0, $result['x_size']);
        $this->assertSame(50.0, $result['y_size']);
        $this->assertSame('flasque - 100x50mm', $result['label']);
    }

    public function test_svg_falls_back_on_the_viewbox_in_pixels(): void
    {
        $result = (new SvgParser())->parse($this->upload('logo.svg', '<svg viewBox="0 0 96 48"></svg>'));

        // 96 CSS pixels are one inch.
        $this->assertSame(25.4, $result['x_size']);
        $this->assertSame(12.7, $result['y_size']);
    }

    public function test_step_reads_the_product_name_and_the_schema(): void
    {
        $result = (new StepParser())->parse($this->upload('bracket.stp', $this->step()));

        $this->assertSame('bracket', $result['code']);
        $this->assertSame('bracket - BRACKET-01', $result['label']);
        $this->assertNull($result['x_size']);
        $this->assertContains(['label' => 'Schéma', 'value' => 'AUTOMOTIVE_DESIGN'], $result['extra']);
    }

    public function test_geo_reads_the_part_block_and_measures_the_geometry(): void
    {
        $result = (new GeoParser())->parse($this->upload('P-1234.geo', $this->geo()));

        $this->assertSame('P-1234', $result['code']);
        $this->assertSame('S235', $result['material']);
        $this->assertSame(3.0, $result['thickness']);
        $this->assertSame(200.0, $result['x_size']);
        $this->assertSame(100.0, $result['y_size']);
        $this->assertSame('P-1234 - S235 3mm - 200x100mm', $result['label']);

        // Rectangle 200x100 plus a hole of radius 10.
        $this->assertContains(
            ['label' => 'Longueur de coupe', 'value' => '662.832 mm'],
            $result['extra'],
        );
        $this->assertContains(['label' => 'Nombre de contours', 'value' => '2'], $result['extra']);
        $this->assertContains(['label' => 'Contours intérieurs', 'value' => '1'], $result['extra']);
        $this->assertContains(['label' => 'Client', 'value' => 'ACME'], $result['extra']);
    }

    public function test_geo_redraws_the_contour_as_an_svg_for_the_nesting_page(): void
    {
        $svg = (new GeoParser())->parse($this->upload('P-1234.geo', $this->geo()))['derived_svg'];

        $this->assertStringContainsString('viewBox="0 0 200 100"', $svg);
        $this->assertStringContainsString('width="200mm" height="100mm"', $svg);
        $this->assertStringContainsString('<circle cx="100" cy="50" r="10"/>', $svg);

        // CAD draws Y upwards, SVG downwards: the bottom edge lands on y=100.
        $this->assertStringContainsString('<line x1="0" y1="100" x2="200" y2="100"/>', $svg);
        $this->assertNotFalse(simplexml_load_string($svg));

        // What we write has to be readable by what reads SVG on the way back.
        $roundTrip = (new SvgParser())->parse($this->upload('P-1234.svg', $svg));
        $this->assertSame(200.0, $roundTrip['x_size']);
        $this->assertSame(100.0, $roundTrip['y_size']);
    }

    public function test_geo_arc_keeps_its_curvature_through_the_mirroring(): void
    {
        $result = (new GeoParser())->parse($this->upload('arc.geo', $this->arcGeo()));

        $this->assertSame(10.0, $result['x_size']);
        $this->assertSame(10.0, $result['y_size']);

        // Quarter of a circle of radius 10: pi * 10 / 2.
        $this->assertContains(['label' => 'Longueur de coupe', 'value' => '15.708 mm'], $result['extra']);

        // Mirroring reverses the orientation, hence a sweep flag of 0 for an
        // arc drawn counter-clockwise in the GEO.
        $this->assertStringContainsString('<path d="M 10 10 A 10 10 0 0 0 0 0"/>', $result['derived_svg']);
    }

    public function test_geo_attributes_block_overrides_the_positional_fields(): void
    {
        $geo = str_replace("#~31\n", "#~30\nIDENT@P-9999\nMAT@INOX 304\n#~TTINFO_END\n#~31\n", $this->geo());

        $result = (new GeoParser())->parse($this->upload('P-1234.geo', $geo));

        $this->assertSame('P-9999', $result['code']);
        $this->assertSame('INOX 304', $result['material']);
    }

    public function test_geo_rejects_a_file_that_is_not_a_geo(): void
    {
        $this->expectExceptionMessage('GEO non reconnu');

        (new GeoParser())->parse($this->upload('note.geo', "hello\nworld\n"));
    }

    /**
     * Write the content to a temporary file and wrap it as an upload.
     */
    private function upload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'cad');
        file_put_contents($path, $content);

        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $name, null, null, true);
    }

    private function dxf(): string
    {
        return <<<'DXF'
0
SECTION
2
HEADER
9
$INSUNITS
70
4
9
$EXTMIN
10
10.0
20
20.0
30
0.0
9
$EXTMAX
10
110.0
20
70.0
30
0.0
0
ENDSEC
0
SECTION
2
ENTITIES
0
LINE
8
0
10
10.0
20
20.0
11
110.0
21
70.0
0
ENDSEC
0
EOF

DXF;
    }

    private function step(): string
    {
        return <<<'STEP'
ISO-10303-21;
HEADER;
FILE_DESCRIPTION((''),'2;1');
FILE_NAME('C:\CAO\bracket.step','2024-01-01T00:00:00',(''),(''),'','','');
FILE_SCHEMA(('AUTOMOTIVE_DESIGN { 1 0 10303 214 1 1 1 1 }'));
ENDSEC;
DATA;
#1=PRODUCT('BRACKET-01','BRACKET-01','',(#2));
ENDSEC;
END-ISO-10303-21;

STEP;
    }

    /**
     * A 200x100 rectangle with a 10 mm radius hole in the middle.
     */
    private function geo(): string
    {
        return <<<'GEO'
#~1
1.03
##~~
#~11
P-1234
PLAN-77
ACME
JD
CMD-9
S235
3.0
##~~
#~31
P
1
0.0 0.0 0.0
|~
P
2
200.0 0.0 0.0
|~
P
3
200.0 100.0 0.0
|~
P
4
0.0 100.0 0.0
|~
P
5
100.0 50.0 0.0
|~
##~~
#~33
1 24 0
##~~
#~331
LIN
1 0
1 2
|~
LIN
1 0
2 3
|~
LIN
1 0
3 4
|~
LIN
1 0
4 1
|~
##~~
#~33
2 24 1
##~~
#~331
CIR
1 0
5
10.0
|~
##~~
#~KONT_END

GEO;
    }

    /**
     * A single quarter arc of radius 10, drawn counter-clockwise.
     */
    private function arcGeo(): string
    {
        return <<<'GEO'
#~1
1.03
##~~
#~31
P
1
0.0 0.0 0.0
|~
P
2
10.0 0.0 0.0
|~
P
3
0.0 10.0 0.0
|~
##~~
#~33
1 24 0
##~~
#~331
ARC
1 0
1 2 3
1
|~
##~~
#~KONT_END

GEO;
    }
}
