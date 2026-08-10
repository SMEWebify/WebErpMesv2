<?php

namespace App\Services\Cad;

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
     * Store the source file and attach it to the line it just created.
     *
     * Called after parsing, because storing moves the upload out of its
     * temporary path.
     */
    public function attachToGed(UploadedFile $file, Model $line, ?string $role): void
    {
        if ($role === null) {
            return;
        }

        $stored = $this->files->store($file, ['hashtags' => ['import-cao']]);

        $this->files->attach($stored, $line, $role, isPrimary: true);
    }
}
