<?php

namespace App\Services\Files;

use App\Models\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Fichier de la GED qui sert de vignette à une ligne de devis / commande quand
 * le détail de ligne n'a pas d'image (`picture`, posée par RadQuote, Nest4Quote
 * ou un .sym).
 *
 * Le serveur ne dessine rien : il désigne le fichier, le navigateur en rend la
 * miniature (files.thumbnail_path) et la dépose pour les affichages suivants.
 *
 * Fichier cherché sur la ligne, puis sur son article. Seuls les rôles de
 * géométrie comptent : la page d'un cahier des charges joint en « autre » ne
 * doit pas passer pour la pièce.
 */
class LineThumbnailFile
{
    private const ROLES = [FileRole::PLAN, FileRole::VECTOR, FileRole::MODEL_3D, FileRole::PHOTO];

    /** Types que le navigateur sait dessiner, du plus lisible en petit au plus lourd. */
    private const KINDS = [
        FileKindResolver::KIND_VECTOR,
        FileKindResolver::KIND_CAD2D,
        FileKindResolver::KIND_IMAGE,
        FileKindResolver::KIND_DOC,
        FileKindResolver::KIND_BREP,
    ];

    /** Colonnes lues, pour un chargement groupé (`files` => fn ($q) => $q->select(...)). */
    public const COLUMNS = ['files.id', 'files.kind', 'files.extension', 'files.original_file_name', 'files.name', 'files.size', 'files.thumbnail_path'];

    /**
     * Résumé du fichier retenu pour la ligne, ou null.
     */
    public static function forLine(Model $line): ?array
    {
        foreach ([$line, $line->Product ?? null] as $owner) {
            if ($owner && ($file = self::pick($owner->files ?? collect()))) {
                return self::summary($file);
            }
        }

        return null;
    }

    /**
     * Une miniature déjà rendue l'emporte (l'image ne change pas d'un affichage
     * à l'autre), puis le type le plus lisible, puis le fichier principal.
     */
    public static function pick(Collection $files): ?File
    {
        return $files
            ->filter(fn (File $file) => in_array($file->pivot->role ?? null, self::ROLES, true)
                && in_array($file->kind, self::KINDS, true))
            ->sortBy(fn (File $file) => [
                filled($file->thumbnail_path) ? 0 : 1,
                array_search($file->kind, self::KINDS, true),
                $file->pivot->is_primary ? 0 : 1,
                $file->id,
            ])
            ->first();
    }

    public static function summary(File $file): array
    {
        return [
            'id'                  => $file->id,
            'kind'                => $file->kind,
            'extension'           => $file->extension,
            'name'                => $file->original_file_name ?: $file->name,
            // Un STEP trop lourd n'est pas dessiné à l'affichage d'une liste.
            'size'                => (int) $file->size,
            'view_url'            => $file->view_url,
            'thumbnail_url'       => $file->thumbnail_url,
            'thumbnail_store_url' => route('files.json.thumbnail', ['file' => $file->id]),
        ];
    }
}
