<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Services\Files\FileStorageService;
use Illuminate\Support\Facades\Storage;
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

        abort_if($located === null, 404);

        [$disk, $path] = $located;

        return Storage::disk($disk)->response($path, basename($filename), [], 'inline');
    }
}
