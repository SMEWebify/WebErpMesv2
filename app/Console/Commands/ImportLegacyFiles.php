<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\Products\Products;
use App\Services\Files\FileKindResolver;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * One-shot migration from the pre-GED file layout to the unified storage.
 *
 * Three things happen, all idempotent:
 *  1. the documents sitting under public/ move to storage/app/private/legacy,
 *     which takes them out of the web root — until now anyone could download a
 *     customer drawing by guessing "{user_id}_{timestamp}.pdf";
 *  2. existing files rows get their disk/path/kind filled in;
 *  3. the products.drawing_file / stl_file / svg_file / picture columns become
 *     real files rows attached with a role, so the product page can drop its
 *     four dedicated upload forms.
 */
class ImportLegacyFiles extends Command
{
    protected $signature = 'wem:files:import
                            {--dry-run : Report what would change without writing anything}
                            {--skip-move : Leave the physical files under public/ and only create the database rows}';

    protected $description = 'Move public/ documents into private storage and turn the product CAD columns into attached files';

    /**
     * Legacy product columns and the role they become.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const PRODUCT_COLUMNS = [
        'drawing_file' => ['drawing', FileRole::PLAN],
        'stl_file' => ['stl', FileRole::MODEL_3D],
        'svg_file' => ['svg', FileRole::VECTOR],
        'picture' => ['images/products', FileRole::PHOTO],
    ];

    public function handle(FileStorageService $storage): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run — nothing will be written.');
        }

        $this->moveLegacyFolders($dryRun);
        $this->backfillFileRows($dryRun);
        $this->importProductColumns($storage, $dryRun);

        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * Move every legacy public folder under the private disk.
     */
    private function moveLegacyFolders(bool $dryRun): void
    {
        $this->newLine();
        $this->line('<comment>Moving public documents out of the web root</comment>');

        if ($this->option('skip-move')) {
            $this->line('  skipped (--skip-move)');

            return;
        }

        $disk = Storage::disk(config('files.disk'));
        $root = trim(config('files.legacy_root'), '/');

        foreach (config('files.legacy_folders') as $folder) {
            $source = public_path($folder);

            if (! is_dir($source)) {
                $this->line("  {$folder}: absent");

                continue;
            }

            $moved = 0;
            $skipped = 0;

            foreach (new \DirectoryIterator($source) as $entry) {
                if ($entry->isDot() || ! $entry->isFile()) {
                    continue;
                }

                $target = $root . '/' . $folder . '/' . $entry->getFilename();

                if ($disk->exists($target)) {
                    $skipped++;

                    continue;
                }

                if (! $dryRun) {
                    $disk->put($target, file_get_contents($entry->getPathname()));
                    unlink($entry->getPathname());
                }

                $moved++;
            }

            $this->line("  {$folder}: {$moved} moved, {$skipped} already present");
        }
    }

    /**
     * Point the existing files rows at their new location and fill in kind.
     */
    private function backfillFileRows(bool $dryRun): void
    {
        $this->newLine();
        $this->line('<comment>Backfilling files rows</comment>');

        $disk = config('files.disk');
        $root = trim(config('files.legacy_root'), '/');
        $updated = 0;
        $orphans = 0;

        File::whereNull('path')->orderBy('id')->chunkById(200, function ($files) use (
            $disk, $root, $dryRun, &$updated, &$orphans
        ) {
            foreach ($files as $file) {
                $found = null;

                foreach (config('files.legacy_folders') as $folder) {
                    $candidate = $root . '/' . $folder . '/' . basename($file->name);

                    if (Storage::disk($disk)->exists($candidate)) {
                        $found = $candidate;

                        break;
                    }
                }

                if ($found === null) {
                    $orphans++;

                    continue;
                }

                if (! $dryRun) {
                    $extension = FileKindResolver::extensionOf($file->original_file_name ?: $file->name);

                    $file->forceFill([
                        'disk' => $disk,
                        'path' => $found,
                        'kind' => FileKindResolver::fromExtension($extension),
                        'extension' => $extension,
                    ])->save();
                }

                $updated++;
            }
        });

        $this->line("  {$updated} rows relocated, {$orphans} without a matching file on disk");
    }

    /**
     * Turn the legacy product CAD columns into attached files.
     */
    private function importProductColumns(FileStorageService $storage, bool $dryRun): void
    {
        $this->newLine();
        $this->line('<comment>Importing product CAD columns</comment>');

        $disk = config('files.disk');
        $root = trim(config('files.legacy_root'), '/');
        $imported = 0;
        $missing = 0;

        $columns = array_keys(self::PRODUCT_COLUMNS);

        Products::where(function ($query) use ($columns) {
            foreach ($columns as $column) {
                $query->orWhereNotNull($column);
            }
        })->orderBy('id')->chunkById(100, function ($products) use (
            $storage, $disk, $root, $dryRun, &$imported, &$missing
        ) {
            foreach ($products as $product) {
                foreach (self::PRODUCT_COLUMNS as $column => [$folder, $role]) {
                    $name = $product->{$column};

                    if (blank($name)) {
                        continue;
                    }

                    $path = $root . '/' . $folder . '/' . basename($name);

                    if (! Storage::disk($disk)->exists($path)) {
                        $missing++;

                        continue;
                    }

                    // Already imported on a previous run.
                    if ($product->files()->where('files.name', $name)->exists()) {
                        continue;
                    }

                    if ($dryRun) {
                        $imported++;

                        continue;
                    }

                    $extension = FileKindResolver::extensionOf($name);

                    $file = File::create([
                        'user_id' => null,
                        'name' => basename($name),
                        'original_file_name' => basename($name),
                        'type' => Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream',
                        'kind' => FileKindResolver::fromExtension($extension),
                        'extension' => $extension,
                        'disk' => $disk,
                        'path' => $path,
                        'size' => Storage::disk($disk)->size($path),
                        'as_photo' => $role === FileRole::PHOTO,
                    ]);

                    $storage->attach($file, $product, $role, isPrimary: true);
                    $imported++;
                }
            }
        });

        $this->line("  {$imported} files attached, {$missing} columns pointing at a missing file");
    }
}
