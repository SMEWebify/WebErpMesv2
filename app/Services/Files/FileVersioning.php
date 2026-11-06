<?php

namespace App\Services\Files;

use App\Models\File;
use App\Models\Products\Products;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Versions of a GED file ("dossier d'étude avec révisions").
 *
 * A lineage is the set of files sharing version_group_id, the id of its first
 * file. Replacing a version uploads a new file in the same lineage, copies
 * every attachment of the old one (entity, role, primary flag) and marks the
 * old one superseded: it stays attached and downloadable, and is never deleted.
 *
 * Whatever references a file by its id (an approved control plan, a FAI
 * report) keeps pointing at that very version — on purpose.
 */
class FileVersioning
{
    public function __construct(private readonly FileStorageService $storage)
    {
    }

    /**
     * Replace the latest version of a lineage by an upload.
     *
     * @param  array{revision_label?: string|null, comment?: string|null}  $meta
     *
     * @throws ConflictHttpException when the file was already replaced
     */
    public function replace(File $previous, UploadedFile $upload, array $meta = [], ?User $user = null): File
    {
        return DB::transaction(function () use ($previous, $upload, $meta, $user) {
            // Two users replacing the same plan at once would fork the lineage.
            $previous = File::whereKey($previous->id)->lockForUpdate()->firstOrFail();

            if ($previous->isSuperseded()) {
                throw new ConflictHttpException(__('general_content.file_version_already_replaced_trans_key'));
            }

            $lineage = $previous->lineageId();

            if ($previous->version_group_id === null) {
                // The first file of a lineage only becomes its version 1 now.
                $previous->forceFill(['version_group_id' => $lineage, 'version' => 1])->save();
            }

            $number = (int) File::where('version_group_id', $lineage)->max('version') + 1;

            $file = $this->storage->store($upload, [
                'comment' => $meta['comment'] ?? null,
                'hashtags' => $previous->hashtags ?? [],
                'as_photo' => $previous->as_photo,
            ]);

            $file->forceFill([
                'version_group_id' => $lineage,
                'version' => $number,
                'revision_label' => self::normalizeLabel($meta['revision_label'] ?? null),
            ])->save();

            $pivots = DB::table('fileables')->where('file_id', $previous->id)->get();

            foreach ($pivots as $pivot) {
                DB::table('fileables')->insert([
                    'file_id' => $file->id,
                    'fileable_id' => $pivot->fileable_id,
                    'fileable_type' => $pivot->fileable_type,
                    'role' => $pivot->role,
                    'is_primary' => $pivot->is_primary,
                ]);
            }

            // The old version keeps its attachment (history, access control) but
            // never again claims "the current drawing" of an entity.
            DB::table('fileables')->where('file_id', $previous->id)->update(['is_primary' => false]);

            $previous->forceFill([
                'superseded_at' => now(),
                'superseded_by' => $file->id,
            ])->save();

            $products = $pivots
                ->where('fileable_type', (new Products())->getMorphClass())
                ->pluck('fileable_id')
                ->unique();

            foreach (Products::whereKey($products)->get() as $product) {
                $this->storage->refreshLegacyColumns($product);
            }

            activity()
                ->performedOn($file)
                ->causedBy($user)
                ->withProperties([
                    'lineage' => $lineage,
                    'version' => $number,
                    'revision_label' => $file->revision_label,
                    'previous_file_id' => $previous->id,
                    'previous_version' => $previous->versionNumber(),
                    'previous_revision_label' => $previous->revision_label,
                    'comment' => $file->comment,
                ])
                ->log('file_version_replaced');

            return $file;
        });
    }

    /**
     * Every version of the lineage a file belongs to, newest first.
     *
     * @return Collection<int, File>
     */
    public function history(File $file): Collection
    {
        $lineage = $file->lineageId();

        return File::with('UserManagement:id,name')
            ->where(fn ($query) => $query->whereKey($lineage)->orWhere('version_group_id', $lineage))
            ->orderByRaw('COALESCE(version, 1) DESC')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Whether a file belongs to a lineage holding more than itself, in which
     * case none of its versions may be deleted.
     */
    public function hasHistory(File $file): bool
    {
        return $file->isSuperseded() || $file->versionNumber() > 1;
    }

    /**
     * Revision label as stored: trimmed, null when empty.
     */
    public static function normalizeLabel(?string $label): ?string
    {
        $label = trim((string) $label);

        return $label === '' ? null : mb_substr($label, 0, 20);
    }
}
