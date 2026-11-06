<?php

namespace Tests\Unit\Cad;

use App\Services\Cad\Bvc\BvcJob;
use App\Services\Cad\Bvc\BvcSvg;
use App\Services\Cad\CadParserFactory;
use App\Services\Cad\Parsers\BvcParser;
use App\Services\Cad\Parsers\SvgParser;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BvcParserTest extends TestCase
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

    public function test_factory_resolves_the_bvc_extension(): void
    {
        $this->assertInstanceOf(BvcParser::class, CadParserFactory::for('BVC'));
        $this->assertStringContainsString('.bvc', CadParserFactory::accept());
    }

    public function test_bvc_gives_one_line_per_part_with_the_job_quantity(): void
    {
        $parts = (new BvcParser())->parseParts($this->upload('JOB.bvc', BvcFixture::archive()));

        $this->assertCount(1, $parts);
        $line = $parts[0];

        // "-nb6" is the ordered quantity appended by BySoft, not part of the reference.
        $this->assertSame('P1', $line['code']);
        $this->assertSame(6, $line['qty']);
        $this->assertSame('S235', $line['material']);
        $this->assertSame(10.0, $line['thickness']);

        // Measured on the contour: PartWidth says 105 because it includes the lead-in.
        $this->assertSame(100.0, $line['x_size']);
        $this->assertSame(50.0, $line['y_size']);
        $this->assertSame('P1 - S235 10mm - 100x50mm', $line['label']);

        // 100x50 minus a hole of radius 10, steel.
        $this->assertSame(0.368, $line['weight']);
        $this->assertContains(['label' => 'Longueur de coupe', 'value' => '362.832 mm'], $line['extra']);
        $this->assertContains(['label' => 'Surface', 'value' => '4685.841 mm²'], $line['extra']);
        $this->assertContains(['label' => 'Contours intérieurs', 'value' => '1'], $line['extra']);
        $this->assertContains(['label' => 'Gaz', 'value' => 'O2'], $line['extra']);

        // Second nest is cut twice.
        $this->assertContains(['label' => 'Imbrication', 'value' => '2 sur 01, 4 sur 02'], $line['extra']);
    }

    public function test_bvc_redraws_the_part_without_its_lead_ins(): void
    {
        $svg = (new BvcParser())->parseParts($this->upload('JOB.bvc', BvcFixture::archive()))[0]['derived_svg'];

        $this->assertStringContainsString('viewBox="0 0 100 50"', $svg);

        // Hole: two half turns, started where the lead-in from the centre ends.
        $this->assertStringContainsString('<path d="M 60 25 A 10 10 0 0 0 40 25 A 10 10 0 0 0 60 25 Z"/>', $svg);

        // Exterior closed over the micro-joint, starting at the end of the lead-in
        // (0, 10) — mirrored to y = 40.
        $this->assertStringContainsString('<path d="M 0 40 L 0 50 L 100 50 L 100 0 L 0 0 L 0 39.5 L 0 40 Z"/>', $svg);

        $roundTrip = (new SvgParser())->parse($this->upload('P1.svg', $svg));
        $this->assertSame(100.0, $roundTrip['x_size']);
        $this->assertSame(50.0, $roundTrip['y_size']);
    }

    public function test_bvc_nest_places_the_parts_on_the_sheet(): void
    {
        $job = BvcJob::read($this->upload('JOB.bvc', BvcFixture::archive())->getRealPath());

        $this->assertCount(2, $job->nests);
        $nest = $job->nests[0];
        $this->assertSame('JOB-01', $nest['name']);
        $this->assertCount(2, $nest['placements']);
        $this->assertCount(1, $nest['residual_cuts']);

        $svg = BvcSvg::nest($nest, $job->parts);

        $this->assertStringContainsString('viewBox="0 0 3000 1500"', $svg);
        $this->assertStringContainsString('<rect class="residual" x="300" y="0" width="2700" height="1500"/>', $svg);

        // Copy at (10, 10), not turned: (0, 10) lands on (10, 20), y = 1480 once mirrored.
        $this->assertStringContainsString('M 10 1480 L 10 1490 L 110 1490', $svg);

        // Copy at (110, 70) turned half a turn: (0, 10) lands on (110, 60).
        $this->assertStringContainsString('M 110 1440 L 110 1430 L 10 1430', $svg);

        // The separation cut of the residual sheet stays open.
        $this->assertStringContainsString('<path class="cut" d="M 300 1500 L 300 0"/>', $svg);
        $this->assertStringContainsString('>P1</text>', $svg);
        $this->assertNotFalse(simplexml_load_string($svg));
    }

    public function test_bvc_rejects_a_file_that_is_not_an_archive(): void
    {
        $this->expectExceptionMessage('Fichier BVC illisible');

        (new BvcParser())->parseParts($this->upload('broken.bvc', 'not a zip'));
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
}
