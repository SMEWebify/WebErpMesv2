<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\Files\FileStorageService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves the documents that used to sit directly under public/ (public/file,
 * public/photo, public/drawing, public/stl, public/svg).
 *
 * Once wem:files:import has moved them under storage/app/private/legacy, these
 * routes take over the URLs the old links point to — so quote lines, order
 * lines, TaskStatuApp and the print views keep working, but behind
 * authentication instead of being downloadable by anyone who guesses the name.
 */
class LegacyFileController extends Controller
{
    public function __construct(private readonly FileStorageService $storage)
    {
    }

    /**
     * Stream a legacy file from the folder it used to live in.
     */
    public function serve(string $folder, string $filename): StreamedResponse
    {
        abort_unless(in_array($folder, config('files.legacy_folders'), true), 404);

        $located = $this->storage->locateLegacy($filename, $folder);

        // The legacy product columns are now a cache of the primary GED files
        // (refreshLegacyColumns), so /drawing/{name} may point at a file stored
        // under files/{yyyy}/{mm}: serve it as files.raw would.
        if ($located === null) {
            $file = File::query()
                ->where('name', basename($filename))
                ->whereNotNull('path')
                ->first();

            abort_if($file === null, 404);

            $this->authorize('view', $file);

            return $this->storage->response($file);
        }

        [$disk, $path] = $located;

        // Same header policy as the GED: these folders hold years of user
        // uploads, SVGs included, served from our own origin.
        return $this->storage->stream($disk, $path, basename($filename));
    }
}
