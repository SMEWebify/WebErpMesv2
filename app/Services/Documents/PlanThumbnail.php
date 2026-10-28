<?php

namespace App\Services\Documents;

use App\Models\File;
use App\Services\Files\FileKindResolver;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Vignette du plan principal d'une ligne, pour les PDF (data URI).
 *
 * dompdf ne lit ni un PDF ni une URL (enable_remote = false) : on n'imprime que
 * des octets d'image lus sur disque — la miniature rendue par le navigateur
 * (files.thumbnail_path), sinon le fichier lui-même s'il s'agit d'une image.
 * Sans l'une ni l'autre, pas de vignette : rien n'est converti côté serveur.
 *
 * Plan cherché sur la ligne, puis sur son article ; le plan marqué principal
 * d'abord, à défaut le premier plan rattaché.
 */
class PlanThumbnail
{
    private const MAX_BYTES = 3 * 1024 * 1024;

    public function __construct(private readonly FileStorageService $files) {}

    public function forLine(object $line): ?string
    {
        foreach ($this->owners($line) as $owner) {
            $plan = $this->plan($owner->files ?? collect());
            if ($plan && ($uri = $this->dataUri($plan))) {
                return $uri;
            }
        }

        return null;
    }

    /**
     * Ligne de devis / commande, ou ligne de commande d'une ligne d'AR, puis l'article.
     */
    private function owners(object $line): array
    {
        $source = method_exists($line, 'files') ? $line : ($line->OrderLine ?? null);

        return array_values(array_filter([$source, $source?->Product]));
    }

    private function plan(Collection $files): ?File
    {
        $plans = $files->filter(fn (File $file) => ($file->pivot->role ?? null) === FileRole::PLAN);

        return $plans->first(fn (File $file) => (bool) $file->pivot->is_primary) ?? $plans->first();
    }

    private function dataUri(File $file): ?string
    {
        $bytes = null;

        if (filled($file->thumbnail_path)) {
            $disk  = config('files.disk');
            $bytes = Storage::disk($disk)->exists($file->thumbnail_path) ? Storage::disk($disk)->get($file->thumbnail_path) : null;
        }

        if ($bytes === null && $file->kind === FileKindResolver::KIND_IMAGE && (int) $file->size <= self::MAX_BYTES) {
            $located = $this->files->locate($file);
            $bytes   = $located ? Storage::disk($located[0])->get($located[1]) : null;
        }

        return $bytes ? $this->encode($bytes) : null;
    }

    /**
     * PNG / JPEG / GIF tels quels ; WebP converti en PNG si GD le permet.
     * Le type est relu dans les octets, jamais dans l'extension.
     */
    private function encode(string $bytes): ?string
    {
        $info = @getimagesizefromstring($bytes);
        if ($info === false) {
            return null;
        }

        $mime = match ($info[2]) {
            IMAGETYPE_PNG  => 'image/png',
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_GIF  => 'image/gif',
            default        => null,
        };

        if ($mime === null && $info[2] === IMAGETYPE_WEBP && function_exists('imagecreatefromstring')) {
            $image = @imagecreatefromstring($bytes);
            if ($image !== false) {
                ob_start();
                imagepng($image);
                $bytes = (string) ob_get_clean();
                $mime  = 'image/png';
            }
        }

        return $mime ? 'data:' . $mime . ';base64,' . base64_encode($bytes) : null;
    }
}
