<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\Planning\Task;
use App\Models\Workflow\OrderLines;
use App\Services\Files\FileKindResolver;
use App\Services\Files\FileRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Attach randomly generated SVG shapes to order lines so the nesting page has
 * something to imbricate locally without needing real CAD uploads.
 *
 * Local dev tooling only: does not run in production.
 */
class SeedNestingSvgs extends Command
{
    protected $signature = 'wem:nesting:seed-svg
        {--count=20 : Maximum number of order lines to equip}
        {--force : Attach even when the line already carries a vector file}';

    protected $description = 'Generate demo SVG geometries and attach them to order lines that have a nesting service';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Refusing to run in production.');
            return self::FAILURE;
        }

        $count = max(1, (int) $this->option('count'));
        $force = (bool) $this->option('force');

        // Lines with an is_nesting task, on active orders
        $lineIds = Task::query()
            ->join('methods_services', 'tasks.methods_services_id', '=', 'methods_services.id')
            ->join('order_lines', 'order_lines.id', '=', 'tasks.order_lines_id')
            ->join('orders', 'orders.id', '=', 'order_lines.orders_id')
            ->where('methods_services.is_nesting', true)
            ->whereIn('orders.statu', [1, 2])
            ->pluck('tasks.order_lines_id')
            ->unique()
            ->values();

        if (! $force) {
            $alreadyEquipped = DB::table('fileables')
                ->join('files', 'files.id', '=', 'fileables.file_id')
                ->where('fileables.fileable_type', OrderLines::class)
                ->whereIn('files.kind', [FileKindResolver::KIND_VECTOR, FileKindResolver::KIND_CAD2D])
                ->pluck('fileables.fileable_id')
                ->unique();

            $lineIds = $lineIds->diff($alreadyEquipped)->values();
        }

        if ($lineIds->isEmpty()) {
            $this->warn('No candidate order lines. Use --force to overwrite lines that already have a vector file.');
            return self::SUCCESS;
        }

        $picked = $lineIds->take($count);

        $this->info(sprintf('Equipping %d order line(s) with generated SVGs...', $picked->count()));

        $disk      = config('files.disk');
        $directory = trim(config('files.root'), '/') . '/' . now()->format('Y/m');
        Storage::disk($disk)->makeDirectory($directory);

        $bar = $this->output->createProgressBar($picked->count());
        $bar->start();

        foreach ($picked as $lineId) {
            $line = OrderLines::find($lineId);
            if (! $line) {
                $bar->advance();
                continue;
            }

            [$svg, $width, $height] = $this->buildSvg();

            $storedName = Str::uuid()->toString() . '.svg';
            $path       = $directory . '/' . $storedName;

            Storage::disk($disk)->put($path, $svg);

            $file = File::create([
                'user_id'            => 1,
                'name'               => $storedName,
                'original_file_name' => sprintf('seed-line-%d-%dx%d.svg', $lineId, $width, $height),
                'type'               => 'image/svg+xml',
                'kind'               => FileKindResolver::KIND_VECTOR,
                'extension'          => 'svg',
                'disk'               => $disk,
                'path'               => $path,
                'size'               => strlen($svg),
                'comment'            => 'Généré par wem:nesting:seed-svg',
                'hashtags'           => ['seed', 'nesting'],
                'as_photo'           => false,
            ]);

            $line->files()->syncWithoutDetaching([
                $file->id => [
                    'role'       => FileRole::VECTOR,
                    'is_primary' => true,
                ],
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info(sprintf('Done. %d SVG file(s) generated under storage/app/%s.', $picked->count(), $directory));

        return self::SUCCESS;
    }

    /**
     * Build a small SVG that looks like a laser-cut plate: outer rectangle plus
     * a couple of holes. Dimensions vary so nesting groups look realistic.
     *
     * @return array{0: string, 1: int, 2: int}
     */
    private function buildSvg(): array
    {
        // Realistic sheet-metal footprints, in millimetres
        $sizes = [
            [80, 60], [120, 80], [150, 100], [200, 100], [90, 90],
            [250, 60], [180, 120], [140, 140], [300, 80], [110, 55],
            [220, 160], [60, 60], [400, 30], [95, 75], [70, 40],
        ];
        [$w, $h] = $sizes[array_rand($sizes)];

        $holes = [];
        $holeCount = random_int(0, 3);
        for ($i = 0; $i < $holeCount; $i++) {
            $cx = random_int((int) ($w * 0.15), (int) ($w * 0.85));
            $cy = random_int((int) ($h * 0.15), (int) ($h * 0.85));
            $r  = random_int(3, (int) min($w, $h) / 6);
            $holes[] = "<circle cx=\"$cx\" cy=\"$cy\" r=\"$r\" fill=\"none\" stroke=\"black\" stroke-width=\"0.5\"/>";
        }

        $body = implode("\n    ", $holes);

        $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 $w $h" width="{$w}mm" height="{$h}mm">
    <rect x="0" y="0" width="$w" height="$h" fill="none" stroke="black" stroke-width="0.8"/>
    $body
</svg>
SVG;

        return [$svg, $w, $h];
    }
}
