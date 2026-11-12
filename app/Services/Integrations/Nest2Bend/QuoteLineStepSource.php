<?php

namespace App\Services\Integrations\Nest2Bend;

use App\Models\File;
use App\Models\Workflow\QuoteLines;
use App\Services\Files\FileStorageService;
use App\Services\Integrations\Nest2Bend\Exceptions\Nest2BendException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * « Pièce de l'ERP → pièce NEST2BEND » : seule conversion de l'intégration.
 *
 * L'ERP ne connaît pas la géométrie d'une pièce pliée (épaisseur et nombre de plis
 * seulement) : il envoie le fichier STEP de la GED, que NEST2BEND analyse. On cherche
 * d'abord dans la GED de la ligne de devis, puis dans celle de son article. Le fichier
 * part tel quel (base64 des octets bruts : il peut contenir du Latin-1).
 */
class QuoteLineStepSource
{
    public const EXTENSIONS = ['step', 'stp'];

    /** Même borne que NEST2BEND (20 Mo une fois décodé). */
    public const MAX_BYTES = 20 * 1024 * 1024;

    public function __construct(private readonly FileStorageService $storage) {}

    /**
     * Fichier STEP de la ligne, sinon de l'article : le principal d'abord, puis le plus récent.
     */
    public function find(QuoteLines $line): ?File
    {
        $line->loadMissing('Product');

        return $this->pick($line->files()->currentVersion()->get())
            ?? ($line->Product ? $this->pick($line->Product->files()->currentVersion()->get()) : null);
    }

    /**
     * Corps `step` de POST /api/v1/embed-sessions.
     *
     * @return array{nom: string, contenu_base64: string}
     *
     * @throws Nest2BendException fichier introuvable sur le disque ou trop volumineux (422)
     */
    public function payload(File $file): array
    {
        $location = $this->storage->locate($file);
        if ($location === null) {
            throw new Nest2BendException(Nest2BendException::INVALID, __('nest2bend.errors.step_unreadable'), 422);
        }

        [$disk, $path] = $location;
        if (Storage::disk($disk)->size($path) > self::MAX_BYTES) {
            throw new Nest2BendException(Nest2BendException::INVALID, __('nest2bend.errors.step_too_large'), 422);
        }

        return [
            'nom'            => $this->fileName($file),
            'contenu_base64' => base64_encode((string) Storage::disk($disk)->get($path)),
        ];
    }

    public static function isStep(?string $nameOrExtension): bool
    {
        $extension = strtolower(pathinfo((string) $nameOrExtension, PATHINFO_EXTENSION) ?: (string) $nameOrExtension);

        return in_array($extension, self::EXTENSIONS, true);
    }

    /**
     * @param  Collection<int, File>  $files
     */
    private function pick(Collection $files): ?File
    {
        return $files
            ->filter(fn (File $file) => self::isStep($file->extension) || self::isStep($file->original_file_name ?: $file->name))
            ->sortByDesc(fn (File $file) => [(int) (bool) $file->pivot?->is_primary, $file->id])
            ->first();
    }

    /** Nom envoyé à NEST2BEND : toujours terminé par .step ou .stp (contrat). */
    private function fileName(File $file): string
    {
        $name = basename((string) ($file->original_file_name ?: $file->name));

        if (! self::isStep(pathinfo($name, PATHINFO_EXTENSION))) {
            $name .= '.' . (self::isStep($file->extension) ? strtolower((string) $file->extension) : 'step');
        }

        return mb_substr($name, -200);
    }
}
