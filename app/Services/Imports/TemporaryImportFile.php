<?php

namespace App\Services\Imports;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fichier déposé gardé le temps d'un import en plusieurs étapes (aperçu puis
 * création) : stockage privé imports/{utilisateur}/{jeton}.{ext}, jamais lisible
 * par un autre utilisateur. Les fichiers de plus de config('imports.temporary_hours')
 * sont supprimés au dépôt suivant. Même rangement que l'assistant d'import.
 */
class TemporaryImportFile
{
    public function put(UploadedFile $file, int $userId): string
    {
        $this->purge($userId);

        $token = (string) Str::uuid();
        $file->storeAs($this->folder($userId), $token . '.' . strtolower($file->getClientOriginalExtension()), 'local');

        return $token;
    }

    /** Chemin relatif au disque local, null si le jeton est inconnu ou expiré. */
    public function find(string $token, int $userId): ?string
    {
        foreach (Storage::disk('local')->files($this->folder($userId)) as $path) {
            if (pathinfo($path, PATHINFO_FILENAME) === $token) {
                return $path;
            }
        }

        return null;
    }

    public function absolutePath(string $path): string
    {
        return Storage::disk('local')->path($path);
    }

    public function delete(string $path): void
    {
        Storage::disk('local')->delete($path);
    }

    private function folder(int $userId): string
    {
        return 'imports/' . $userId;
    }

    private function purge(int $userId): void
    {
        $limit = now()->subHours(config('imports.temporary_hours'))->getTimestamp();
        $disk = Storage::disk('local');
        foreach ($disk->files($this->folder($userId)) as $path) {
            if ($disk->lastModified($path) < $limit) {
                $disk->delete($path);
            }
        }
    }
}
