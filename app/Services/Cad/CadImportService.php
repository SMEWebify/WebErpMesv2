<?php

namespace App\Services\Cad;

use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

/**
 * Entry point of the drag-and-drop import: reads a CAD file into line fields
 * and files the source document on the line.
 *
 * Quote lines and order lines run the exact same flow, only the models differ,
 * which is why the loop itself stays in the controllers.
 */
class CadImportService
{
    public function __construct(private FileStorageService $files) {}

    /**
     * Whether the feature is switched on for this instance.
     */
    public function isEnabled(): bool
    {
        return (bool) config('cad.line_import');
    }

    /**
     * Read an upload into the fields of a line.
     *
     * The returned array is the parser output plus the GED role the source
     * file has to be stored under, null when it is not worth keeping.
     *
     * @throws \RuntimeException on an unsupported or unreadable file
     */
    public function parse(UploadedFile $file): array
    {
        $extension = mb_strtolower($file->getClientOriginalExtension());
        $parser = CadParserFactory::for($extension);

        if ($parser === null) {
            throw new \RuntimeException('Format non pris en charge (' . CadParserFactory::accept() . ')');
        }

        return $parser->parse($file) + ['ged_role' => $parser->gedRole()];
    }

    /**
     * File the source document on the line, plus the contour the parser had to
     * redraw when the format is readable by nothing else.
     *
     * @param  array<string, mixed>  $data  output of parse()
     */
    public function attachToGed(UploadedFile $file, Model $line, array $data): void
    {
        if (($data['ged_role'] ?? null) !== null) {
            $stored = $this->files->store($file, ['hashtags' => ['import-cao']]);

            $this->files->attach($stored, $line, $data['ged_role'], isPrimary: true);
        }

        if (($data['derived_svg'] ?? null) !== null) {
            $this->attachDerivedSvg($data['derived_svg'], $file, $line);
        }
    }

    /**
     * Store a generated SVG as a document of its own.
     *
     * The nesting page only looks at cad2d and vector files, so this is what
     * makes a format it cannot read imbricable all the same.
     */
    private function attachDerivedSvg(string $svg, UploadedFile $source, Model $line): void
    {
        $path = tempnam(sys_get_temp_dir(), 'cadsvg');

        if ($path === false) {
            return;
        }

        try {
            file_put_contents($path, $svg);

            $name = pathinfo($source->getClientOriginalName(), PATHINFO_FILENAME) . '.svg';
            $upload = new UploadedFile($path, $name, 'image/svg+xml', null, true);

            $stored = $this->files->store($upload, [
                'hashtags' => ['import-cao', 'imbrication'],
                'comment' => 'Contour généré depuis ' . $source->getClientOriginalName(),
            ]);

            $this->files->attach($stored, $line, FileRole::VECTOR, isPrimary: true);
        } finally {
            // store() copies the upload through a stream rather than moving it.
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
