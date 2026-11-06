<?php

namespace App\Services\Cad\Parsers;

use App\Services\Cad\BaseCadParser;
use App\Services\Cad\Bvc\BvcJob;
use App\Services\Cad\Bvc\BvcSvg;
use App\Services\Cad\CadMultiPartParser;
use Illuminate\Http\UploadedFile;

/**
 * Bystronic BySoft .bvc — a cutting job: one or more nests and the parts cut
 * from them. See BvcJob for the format.
 *
 * Every part gives a line, with the quantity ordered in the job and its
 * contour redrawn as SVG: the .bvc holds no drawing a viewer could open, and
 * that SVG is what the nesting page and the line thumbnail read.
 *
 * The .bvc itself stays out of the lines: it is the job, not the drawing of a
 * part. Dropped in the GED of a quote or an order, it gets its nests drawn
 * instead (BvcNestPreviews).
 */
class BvcParser extends BaseCadParser implements CadMultiPartParser
{
    /**
     * Density in kg/mm³ by material family, matched on the material name.
     * Anything unrecognised is taken as steel, by far the most cut on a laser.
     */
    private const DENSITIES = [
        '/\b(alu|al\s?mg|almg|5754|5083|1050|6060|6082)/i' => 2.7e-6,
        '/\b(inox|304|316|1\.4\d{3})/i' => 7.93e-6,
        '/\b(cuivre|cu\b)/i' => 8.96e-6,
        '/\b(laiton|cuzn)/i' => 8.5e-6,
    ];

    private const STEEL_DENSITY = 7.85e-6;

    public static function extensions(): array
    {
        return ['bvc'];
    }

    /**
     * A job is never a single line: the first part stands for the file
     * wherever only one line is expected.
     */
    public function parse(UploadedFile $file): array
    {
        return $this->parseParts($file)[0];
    }

    public function parseParts(UploadedFile $file): array
    {
        $job = BvcJob::read($file->getRealPath());
        $meta = $job->job;
        $parts = array_filter($job->parts, fn (array $part) => $part['contours'] !== null);

        if ($parts === []) {
            throw new \RuntimeException('Aucune pièce dans ce job BySoft');
        }

        $materialPart = $meta['material'] !== null
            ? $meta['material'] . ($meta['thickness'] !== null ? ' ' . $this->number($meta['thickness']) . 'mm' : '')
            : null;

        $lines = [];

        foreach ($parts as $part) {
            $dimensions = $this->number($part['width']) . 'x' . $this->number($part['height']) . 'mm';

            $line = $this->result([
                'code' => $part['reference'],
                'label' => $this->label([$part['reference'], $materialPart, $dimensions]),
                'material' => $meta['material'],
                'thickness' => $meta['thickness'],
                'x_size' => $part['width'],
                'y_size' => $part['height'],
                'weight' => $meta['thickness'] !== null
                    ? round($part['area'] * $meta['thickness'] * $this->density($meta['material']), 3)
                    : null,
                'derived_svg' => BvcSvg::part($part),
                'extra' => $this->requirements([
                    $this->requirement('Longueur de coupe', $part['cut_length'], 'mm'),
                    $this->requirement('Surface', $part['area'], 'mm²'),
                    $this->requirement('Nombre de contours', $part['contour_count']),
                    $this->requirement('Contours intérieurs', $part['interior_count'] ?: null),
                    $this->requirement('Gaz', $meta['gas']),
                    $this->requirement('Imbrication', $this->nests($part['nests'], $meta['name'])),
                    $this->requirement('Source', 'BySoft .bvc ' . $meta['name']),
                ]),
            ]);

            $line['qty'] = max(1, $part['quantity']);

            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * "24 sur 01, 8 sur 02": how many copies each nest cuts, the nests being
     * named after the job with a sequence number.
     *
     * @param  array<int, array{nest: string, quantity: int}>  $nests
     */
    private function nests(array $nests, string $job): ?string
    {
        if ($nests === []) {
            return null;
        }

        return implode(', ', array_map(function (array $nest) use ($job) {
            $name = str_starts_with($nest['nest'], $job . '-')
                ? substr($nest['nest'], strlen($job) + 1)
                : $nest['nest'];

            return $nest['quantity'] . ' sur ' . $name;
        }, $nests));
    }

    private function density(?string $material): float
    {
        foreach (self::DENSITIES as $pattern => $density) {
            if ($material !== null && preg_match($pattern, $material)) {
                return $density;
            }
        }

        return self::STEEL_DENSITY;
    }
}
