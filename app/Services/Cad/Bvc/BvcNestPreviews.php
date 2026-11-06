<?php

namespace App\Services\Cad\Bvc;

use App\Models\File;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use Illuminate\Database\Eloquent\Model;

/**
 * Draws the nests of a BySoft job dropped in the GED of a quote or an order.
 *
 * The .bvc itself is download only. Each of its nests becomes an SVG filed
 * next to it — the sheet, the parts where they are cut and the residual sheet
 * kept for later — with the figures of the nest in the comment.
 *
 * Only on a quote or an order: on a line, an SVG in the vector role would be
 * taken as the drawing of the part (thumbnail, nesting page).
 */
class BvcNestPreviews
{
    public function __construct(private FileStorageService $files) {}

    public function supports(File $file, Model $entity): bool
    {
        return $file->extension === 'bvc'
            && ($entity instanceof Quotes || $entity instanceof Orders);
    }

    /**
     * File one SVG per nest on the entity.
     *
     * @return array<int, File>
     *
     * @throws \RuntimeException when the job is not readable
     */
    public function attach(string $path, File $source, Model $entity): array
    {
        $job = BvcJob::read($path);
        $created = [];

        foreach ($job->nests as $nest) {
            $svg = BvcSvg::nest($nest, $job->parts);

            if ($svg === null) {
                continue;
            }

            $file = $this->files->storeContents($svg, $nest['name'] . '.svg', 'image/svg+xml', [
                'comment' => $this->comment($job, $nest, $source),
                'hashtags' => ['imbrication', 'bvc'],
            ]);

            $this->files->attach($file, $entity, FileRole::VECTOR);

            $created[] = $file;
        }

        return $created;
    }

    /**
     * "S235 10mm — tôle 3000x1500 — 169 pièces — chute 26.6 % — coupe 1 h 36 min".
     *
     * @param  array<string, mixed>  $nest
     */
    private function comment(BvcJob $job, array $nest, File $source): string
    {
        $count = count($nest['placements']);

        $parts = array_filter([
            trim(($job->job['material'] ?? '') . ' ' . ($job->job['thickness'] !== null ? $this->number($job->job['thickness']) . 'mm' : '')),
            $nest['sheet'] !== null ? 'tôle ' . $this->number($nest['sheet']['width']) . 'x' . $this->number($nest['sheet']['height']) : null,
            $nest['multiplicity'] > 1 ? '× ' . $nest['multiplicity'] . ' tôles' : null,
            $count . ' pièce' . ($count > 1 ? 's' : ''),
            $nest['waste'] > 0 ? 'chute ' . $this->number($nest['waste'] * 100, 1) . ' %' : null,
            $nest['cutting_time'] > 0 ? 'coupe estimée ' . $this->duration($nest['cutting_time']) : null,
            $nest['residual_sheets'] !== []
                ? 'reste ' . implode(', ', array_map(fn (array $r) => $this->number($r['width']) . 'x' . $this->number($r['height']), $nest['residual_sheets']))
                : null,
        ]);

        return implode(' — ', $parts) . ' (depuis ' . $source->original_file_name . ')';
    }

    private function duration(float $seconds): string
    {
        $minutes = (int) round($seconds / 60);

        return $minutes >= 60
            ? intdiv($minutes, 60) . ' h ' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT)
            : max(1, $minutes) . ' min';
    }

    private function number(float $value, int $decimals = 2): string
    {
        return rtrim(rtrim(number_format($value, $decimals, '.', ''), '0'), '.');
    }
}
