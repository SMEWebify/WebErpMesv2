<?php

namespace App\Services\Integrations\SharePoint;

use App\Jobs\MirrorFileToSharePoint;
use App\Models\File;
use App\Models\Integrations\SharePointMirrorItem;
use App\Models\Integrations\SharePointSetting;
use App\Services\Files\FileableRegistry;
use App\Services\Files\FileStorageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Recopie de la GED vers SharePoint, à sens unique.
 *
 * La GED reste la source de vérité : rien n'est jamais lu depuis SharePoint, rien n'y est
 * jamais supprimé (un fichier détaché dans l'ERP reste dans SharePoint). Une nouvelle
 * version remplace le contenu du même élément SharePoint, qui en garde l'historique.
 *
 * Ne recopie jamais un fichier rattaché à une entité confidentielle (dossier salarié,
 * contrats, notes de frais), ni les trames de devis, ni un alias absent de
 * `config('sharepoint.folders')` / `line_parents`.
 */
class SharePointMirror
{
    private const FOLDER_CACHE_TTL = 86400;

    public function __construct(private readonly FileStorageService $storage) {}

    // ── Mise en file (appelée par la GED) ────────────────────────────────────

    /**
     * Demande la recopie d'un fichier dans le dossier d'une entité. Sans effet si la
     * liaison est inactive. N'échoue jamais : la GED ne doit pas dépendre de SharePoint.
     */
    public function queue(File $file, string $fileableType, int $fileableId): void
    {
        try {
            if (! SharePointSetting::usable() || ! $this->isMirroredClass($fileableType) || $this->isConfidential($file)) {
                return;
            }

            $item = SharePointMirrorItem::firstOrNew([
                'lineage_id'    => $file->lineageId(),
                'fileable_type' => $fileableType,
                'fileable_id'   => $fileableId,
            ]);

            // Une version plus ancienne ne remplace jamais la dernière demandée.
            if ($item->exists && (int) $item->file_id !== $file->id) {
                $queued = File::find($item->file_id);
                if ($queued && $queued->versionNumber() > $file->versionNumber()) {
                    return;
                }
            }

            if ($item->exists && $item->status === SharePointMirrorItem::STATUS_SYNCED && (int) $item->synced_file_id === $file->id) {
                return;
            }

            $item->fill([
                'file_id'    => $file->id,
                'status'     => SharePointMirrorItem::STATUS_PENDING,
                'attempts'   => 0,
                'last_error' => null,
            ])->save();

            MirrorFileToSharePoint::dispatch($item->id)->afterCommit();
        } catch (Throwable $e) {
            Log::warning('SharePoint: recopie non demandée', [
                'file_id' => $file->id,
                'fileable_type' => $fileableType,
                'fileable_id' => $fileableId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Met en file les fichiers existants (dernières versions) pas encore recopiés.
     *
     * @return int nombre de recopies demandées (ou qui le seraient, en simulation)
     */
    public function backfill(bool $dryRun = false): int
    {
        if (! SharePointSetting::usable()) {
            return 0;
        }

        $count = 0;

        DB::table('fileables')
            ->join('files', 'files.id', '=', 'fileables.file_id')
            ->whereNull('files.superseded_at')
            ->whereIn('fileables.fileable_type', $this->mirroredClasses())
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('sharepoint_mirror_items')
                    ->whereColumn('sharepoint_mirror_items.synced_file_id', 'fileables.file_id')
                    ->whereColumn('sharepoint_mirror_items.fileable_type', 'fileables.fileable_type')
                    ->whereColumn('sharepoint_mirror_items.fileable_id', 'fileables.fileable_id');
            })
            ->orderBy('fileables.id')
            ->select('fileables.id', 'fileables.file_id', 'fileables.fileable_type', 'fileables.fileable_id')
            ->chunkById(500, function ($rows) use ($dryRun, &$count) {
                $files = File::whereKey($rows->pluck('file_id')->unique())->get()->keyBy('id');

                foreach ($rows as $row) {
                    $file = $files->get($row->file_id);
                    if (! $file || $this->isConfidential($file)) {
                        continue;
                    }

                    $count++;
                    if (! $dryRun) {
                        $this->queue($file, $row->fileable_type, (int) $row->fileable_id);
                    }
                }
            }, 'fileables.id', 'id');

        return $count;
    }

    /**
     * Remet en file les recopies en échec (après correction des droits, par exemple).
     */
    public function retryFailed(): int
    {
        $count = 0;

        SharePointMirrorItem::where('status', SharePointMirrorItem::STATUS_FAILED)
            ->orderBy('id')
            ->chunkById(500, function ($items) use (&$count) {
                foreach ($items as $item) {
                    $item->forceFill(['status' => SharePointMirrorItem::STATUS_PENDING, 'attempts' => 0, 'last_error' => null])->save();
                    MirrorFileToSharePoint::dispatch($item->id);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * @return array{pending: int, synced: int, failed: int, skipped: int}
     */
    public function stats(): array
    {
        $counts = SharePointMirrorItem::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'pending' => (int) ($counts[SharePointMirrorItem::STATUS_PENDING] ?? 0),
            'synced'  => (int) ($counts[SharePointMirrorItem::STATUS_SYNCED] ?? 0),
            'failed'  => (int) ($counts[SharePointMirrorItem::STATUS_FAILED] ?? 0),
            'skipped' => (int) ($counts[SharePointMirrorItem::STATUS_SKIPPED] ?? 0),
        ];
    }

    // ── Recopie (appelée par le job) ─────────────────────────────────────────

    /**
     * Envoie la dernière version demandée. Lève SharePointException en cas d'échec Graph.
     */
    public function sync(SharePointMirrorItem $item): void
    {
        $setting = SharePointSetting::usable();
        if (! $setting) {
            return; // Reste en attente : repartira avec « Relancer » ou la commande.
        }

        $file = File::find($item->file_id);
        if (! $file) {
            $this->skip($item, 'file_missing');

            return;
        }

        if ($item->status === SharePointMirrorItem::STATUS_SYNCED && (int) $item->synced_file_id === $file->id) {
            return;
        }

        $entity = $this->entity($item->fileable_type, (int) $item->fileable_id);
        if (! $entity || ! $this->isMirroredEntity($entity) || $this->isConfidential($file)) {
            $this->skip($item, 'not_mirrored');

            return;
        }

        $client = $this->client($setting);

        try {
            $result = $this->push($client, $setting, $item, $entity, $file);
        } catch (SharePointException $e) {
            if (! $e->isNotFound()) {
                throw $e;
            }
            // Dossier ou fichier supprimé dans SharePoint : on recrée, une seule fois.
            $this->forgetFolders($setting, $entity);
            $item->forceFill(['remote_item_id' => null, 'remote_name' => null, 'folder_item_id' => null])->save();
            $result = $this->push($client, $setting, $item, $entity, $file);
        }

        $item->forceFill([
            'status'         => SharePointMirrorItem::STATUS_SYNCED,
            'synced_file_id' => $file->id,
            'synced_version' => $file->versionNumber(),
            'folder_item_id' => $result['folder']['id'],
            'folder_web_url' => $result['folder']['webUrl'],
            'remote_item_id' => $result['item']['id'],
            'remote_name'    => $result['item']['name'],
            'web_url'        => $result['item']['webUrl'],
            'last_error'     => null,
            'synced_at'      => now(),
        ])->save();
    }

    /**
     * @return array{folder: array{id: string, webUrl: ?string}, item: array{id: string, name: string, webUrl: ?string}}
     */
    private function push(GraphClient $client, SharePointSetting $setting, SharePointMirrorItem $item, Model $entity, File $file): array
    {
        $location = $this->storage->locate($file);
        if (! $location) {
            throw new SharePointException(__('sharepoint.error.file_unreadable'), 422);
        }

        [$disk, $path] = $location;
        $stream = Storage::disk($disk)->readStream($path);
        if (! is_resource($stream)) {
            throw new SharePointException(__('sharepoint.error.file_unreadable'), 422);
        }

        try {
            $folder = $item->folder_item_id
                ? ['id' => $item->folder_item_id, 'webUrl' => $item->folder_web_url]
                : $this->entityFolder($client, $setting, $entity);

            $name = $item->remote_name ?: SharePointNames::clean($file->original_file_name ?: $file->name);

            $remote = $client->upload(
                $folder['id'],
                $name,
                $stream,
                (int) Storage::disk($disk)->size($path),
                $item->remote_item_id ?: null,
            );
        } finally {
            fclose($stream);
        }

        return ['folder' => $folder, 'item' => $remote];
    }

    // ── Dossiers ─────────────────────────────────────────────────────────────

    /**
     * Dossier SharePoint de l'entité, créé au besoin. Nommé une seule fois : son id est
     * repris des recopies déjà faites pour l'entité, puis du cache.
     *
     * @return array{id: string, webUrl: ?string}
     */
    public function entityFolder(GraphClient $client, SharePointSetting $setting, Model $entity): array
    {
        $known = SharePointMirrorItem::where('fileable_type', $entity->getMorphClass())
            ->where('fileable_id', $entity->getKey())
            ->whereNotNull('folder_item_id')
            ->latest('synced_at')
            ->first(['folder_item_id', 'folder_web_url']);

        if ($known) {
            return ['id' => $known->folder_item_id, 'webUrl' => $known->folder_web_url];
        }

        $key = $this->entityFolderCacheKey($setting, $entity);
        if ($cached = Cache::get($key)) {
            return $cached;
        }

        $alias = (string) FileableRegistry::aliasFor($entity);
        $parentAlias = config('sharepoint.line_parents.' . $alias);

        if ($parentAlias) {
            $parent = $this->parentOf($entity, $parentAlias);
            $base = $parent
                ? $this->entityFolder($client, $setting, $parent)
                : $this->path($client, $setting, $this->typeSegments($setting, $parentAlias));
        } else {
            $base = $this->path($client, $setting, $this->typeSegments($setting, $alias));
        }

        $folder = $client->ensureFolder($base['id'], $this->entityFolderName($entity, $alias));
        Cache::put($key, $folder, self::FOLDER_CACHE_TTL);

        return $folder;
    }

    /**
     * Chemin de dossiers depuis la racine de la bibliothèque, chaque niveau mis en cache.
     *
     * @param  array<int, string>  $segments
     * @return array{id: string, webUrl: ?string}
     */
    private function path(GraphClient $client, SharePointSetting $setting, array $segments): array
    {
        $folder = ['id' => 'root', 'webUrl' => $setting->drive_web_url];
        $walked = [];

        foreach ($segments as $segment) {
            $walked[] = $segment;
            $key = 'sharepoint:folder:' . $setting->drive_id . ':' . sha1(implode('/', $walked));

            $parentId = $folder['id'];
            $folder = Cache::get($key) ?? tap(
                $client->ensureFolder($parentId, $segment),
                fn (array $created) => Cache::put($key, $created, self::FOLDER_CACHE_TTL)
            );
        }

        return $folder;
    }

    /**
     * @return array<int, string>
     */
    private function typeSegments(SharePointSetting $setting, string $alias): array
    {
        $parts = array_merge(
            explode('/', $setting->rootFolder()),
            explode('/', (string) config('sharepoint.folders.' . $alias, $alias)),
        );

        return array_values(array_map(
            static fn (string $part) => SharePointNames::clean($part),
            array_filter($parts, static fn (string $part) => trim($part) !== '')
        ));
    }

    public function entityFolderName(Model $entity, string $alias): string
    {
        $code = trim((string) $entity->getAttribute('code'));
        $label = trim((string) $entity->getAttribute('label'));

        if (config('sharepoint.line_parents.' . $alias)) {
            $order = $entity->getAttribute('ordre') ?? $entity->getKey();

            return SharePointNames::clean('Ligne ' . $order . (($code ?: $label) !== '' ? ' - ' . ($code ?: $label) : ''));
        }

        if ($code === '') {
            $code = match ($alias) {
                'opportunity' => 'OPP-' . $entity->getKey(),
                'stock-move'  => 'MVT-' . $entity->getKey(),
                default       => '#' . $entity->getKey(),
            };
        }

        $name = $label !== '' && $label !== $code ? $code . ' - ' . $label : $code;

        return SharePointNames::clean($name, (string) $entity->getKey());
    }

    private function parentOf(Model $entity, string $parentAlias): ?Model
    {
        $relation = ['quote' => 'quote', 'order' => 'order'][$parentAlias] ?? null;
        if (! $relation || ! method_exists($entity, $relation)) {
            return null;
        }

        return $entity->{$relation}()->first();
    }

    private function forgetFolders(SharePointSetting $setting, Model $entity): void
    {
        Cache::forget($this->entityFolderCacheKey($setting, $entity));

        SharePointMirrorItem::where('fileable_type', $entity->getMorphClass())
            ->where('fileable_id', $entity->getKey())
            ->update(['folder_item_id' => null, 'folder_web_url' => null]);

        $alias = (string) FileableRegistry::aliasFor($entity);
        $parentAlias = config('sharepoint.line_parents.' . $alias);
        $walked = [];
        foreach ($this->typeSegments($setting, $parentAlias ?: $alias) as $segment) {
            $walked[] = $segment;
            Cache::forget('sharepoint:folder:' . $setting->drive_id . ':' . sha1(implode('/', $walked)));
        }

        if ($parentAlias && ($parent = $this->parentOf($entity, $parentAlias))) {
            Cache::forget($this->entityFolderCacheKey($setting, $parent));
        }
    }

    private function entityFolderCacheKey(SharePointSetting $setting, Model $entity): string
    {
        return 'sharepoint:entity-folder:' . $setting->drive_id . ':' . sha1($entity->getMorphClass() . '#' . $entity->getKey());
    }

    // ── Règles ───────────────────────────────────────────────────────────────

    /**
     * @return array<int, class-string<Model>>
     */
    public function mirroredClasses(): array
    {
        $aliases = array_merge(
            array_keys((array) config('sharepoint.folders')),
            array_keys((array) config('sharepoint.line_parents')),
        );

        return array_values(array_filter(array_map(
            static fn (string $alias) => FileableRegistry::isConfidential($alias) ? null : FileableRegistry::classFor($alias),
            $aliases
        )));
    }

    private function isMirroredClass(string $class): bool
    {
        return in_array($class, $this->mirroredClasses(), true);
    }

    private function isMirroredEntity(Model $entity): bool
    {
        if (! $this->isMirroredClass($entity->getMorphClass())) {
            return false;
        }

        // Une trame de devis n'est pas une affaire.
        if ((bool) $entity->getAttribute('is_template')) {
            return false;
        }

        // Ligne : rangée dans le dossier de son document. Le scope des devis masque les
        // trames, donc une ligne de trame n'a pas de parent et n'est pas recopiée.
        $parentAlias = config('sharepoint.line_parents.' . FileableRegistry::aliasFor($entity));

        return ! $parentAlias || $this->parentOf($entity, $parentAlias) !== null;
    }

    /**
     * Un fichier aussi rattaché à une entité confidentielle ne sort jamais.
     */
    private function isConfidential(File $file): bool
    {
        return DB::table('fileables')
            ->where('file_id', $file->id)
            ->whereIn('fileable_type', FileableRegistry::confidentialClasses())
            ->exists();
    }

    private function entity(string $class, int $id): ?Model
    {
        $alias = FileableRegistry::aliasFor($class);

        return $alias ? FileableRegistry::find($alias, $id) : null;
    }

    private function skip(SharePointMirrorItem $item, string $reason): void
    {
        $item->forceFill([
            'status' => SharePointMirrorItem::STATUS_SKIPPED,
            'last_error' => __('sharepoint.skipped.' . $reason),
        ])->save();
    }

    protected function client(SharePointSetting $setting): GraphClient
    {
        return new GraphClient($setting);
    }
}
