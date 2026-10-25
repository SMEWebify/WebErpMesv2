<?php

namespace App\Services\Files;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Miniatures (fichiers de la GED, plans de contrôle bullés).
 *
 * Le serveur n'a aucun moteur PDF : la miniature est dessinée par le navigateur
 * (pdf.js) puis déposée ici. Seule une petite image PNG / WebP / JPEG est
 * acceptée, et son type est relu dans les octets, jamais dans la déclaration
 * du navigateur.
 */
class ThumbnailStore
{
    public const MAX_SIDE = 480;
    public const MAX_BYTES = 300 * 1024;

    private const TYPES = [
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_JPEG => 'jpg',
    ];

    /**
     * Valide l'image et l'enregistre sous thumbnails/{scope}/{id}.{ext}.
     * Renvoie le chemin à stocker sur l'entité.
     */
    public function store(UploadedFile $image, string $scope, int $id, ?string $previousPath = null): string
    {
        $bytes = (string) file_get_contents($image->getRealPath());
        $info = @getimagesizefromstring($bytes);

        if ($info === false
            || ! isset(self::TYPES[$info[2]])
            || strlen($bytes) > self::MAX_BYTES
            || $info[0] > self::MAX_SIDE
            || $info[1] > self::MAX_SIDE) {
            throw ValidationException::withMessages([
                'thumbnail' => __('inspection.thumbnail_invalid', ['max' => self::MAX_SIDE]),
            ]);
        }

        $disk = config('files.disk');
        $path = 'private/thumbnails/' . $scope . '/' . $id . '-' . substr(sha1($bytes), 0, 8) . '.' . self::TYPES[$info[2]];

        Storage::disk($disk)->put($path, $bytes);

        if ($previousPath !== null && $previousPath !== $path) {
            Storage::disk($disk)->delete($previousPath);
        }

        return $path;
    }

    public function response(?string $path): StreamedResponse
    {
        $disk = config('files.disk');

        abort_if(blank($path) || ! Storage::disk($disk)->exists($path), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = ['png' => 'image/png', 'webp' => 'image/webp', 'jpg' => 'image/jpeg'][$extension] ?? 'application/octet-stream';

        return Storage::disk($disk)->response($path, basename($path), [
            'Content-Type'           => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, max-age=86400',
        ], 'inline');
    }

    public function delete(?string $path): void
    {
        if (filled($path)) {
            Storage::disk(config('files.disk'))->delete($path);
        }
    }
}
