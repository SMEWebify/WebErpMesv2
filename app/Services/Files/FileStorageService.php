<?php

namespace App\Services\Files;

use App\Models\File;
use App\Models\Products\Products;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Single entry point for writing, reading and deleting attached documents.
 *
 * Files are stored outside the web root and only reachable through the
 * authorized routes exposed by FileApiController, which is why nothing here
 * ever returns a public asset() URL.
 */
class FileStorageService
{
    /**
     * Store an upload and create the matching File record.
     *
     * @param  array{comment?: string|null, hashtags?: array<int, string>, as_photo?: bool, name?: string|null}  $meta
     */
    public function store(UploadedFile $upload, array $meta = []): File
    {
        $extension = mb_strtolower($upload->getClientOriginalExtension());
        $kind = FileKindResolver::fromExtension($extension);

        $directory = trim(config('files.root'), '/') . '/' . now()->format('Y/m');
        $storedName = Str::uuid()->toString() . ($extension !== '' ? '.' . $extension : '');

        $disk = config('files.disk');
        $upload->storeAs($directory, $storedName, ['disk' => $disk]);

        return File::create([
            'user_id' => Auth::id(),
            'name' => $storedName,
            'original_file_name' => $upload->getClientOriginalName(),
            'type' => $upload->getClientMimeType(),
            'kind' => $kind,
            'extension' => $extension !== '' ? $extension : null,
            'disk' => $disk,
            'path' => $directory . '/' . $storedName,
            'size' => $upload->getSize(),
            'comment' => $this->normalizeComment($meta['comment'] ?? null),
            'hashtags' => $meta['hashtags'] ?? [],
            'as_photo' => (bool) ($meta['as_photo'] ?? false),
        ]);
    }

    /**
     * Attach a file to an entity with a business role.
     *
     * When the file is flagged as primary, any other file holding that role on
     * the same entity is demoted so that "the current drawing" stays unique.
     */
    public function attach(File $file, Model $entity, ?string $role = null, bool $isPrimary = false): void
    {
        $role ??= FileRole::guessFromKind($file->kind ?? FileKindResolver::KIND_OTHER);

        if ($isPrimary) {
            $this->demoteRole($entity, $role);
        }

        $entity->files()->syncWithoutDetaching([
            $file->id => ['role' => $role, 'is_primary' => $isPrimary],
        ]);

        $this->refreshLegacyColumns($entity);
    }

    /**
     * Promote a file as the primary one for its role on that entity.
     */
    public function markPrimary(File $file, Model $entity, string $role): void
    {
        $this->demoteRole($entity, $role);

        $entity->files()->updateExistingPivot($file->id, [
            'role' => $role,
            'is_primary' => true,
        ]);

        $this->refreshLegacyColumns($entity);
    }

    /**
     * Detach a file from an entity, and delete it entirely once it is orphaned.
     */
    public function detach(File $file, Model $entity): void
    {
        $entity->files()->detach($file->id);
        $this->refreshLegacyColumns($entity);

        if ($this->attachmentCount($file) === 0) {
            $this->delete($file);
        }
    }

    /**
     * Delete the record and the underlying bytes.
     */
    public function delete(File $file): void
    {
        if (filled($file->path) && filled($file->disk) && Storage::disk($file->disk)->exists($file->path)) {
            Storage::disk($file->disk)->delete($file->path);
        }

        $file->delete();
    }

    /**
     * Stream a file to the browser, inline for the viewers or as an attachment.
     */
    public function response(File $file, bool $download = false): StreamedResponse
    {
        $located = $this->locate($file);

        abort_if($located === null, 404);

        [$disk, $path] = $located;

        $headers = [
            'Content-Type' => $file->type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ];

        // An SVG is an executable document: served inline from our own origin it
        // could run scripts against the session. Uploads are user supplied, so
        // the response is locked down to a static image.
        if ($file->kind === FileKindResolver::KIND_VECTOR) {
            $headers['Content-Security-Policy'] = "default-src 'none'; style-src 'unsafe-inline'; img-src data:; sandbox";
        }

        $name = $file->original_file_name ?: $file->name;

        return $download
            ? Storage::disk($disk)->download($path, $name, $headers)
            : Storage::disk($disk)->response($path, $name, $headers, 'inline');
    }

    /**
     * Resolve the disk and path holding the bytes, covering files stored before
     * the private storage migration.
     *
     * @return array{0: string, 1: string}|null
     */
    public function locate(File $file): ?array
    {
        if (filled($file->path) && filled($file->disk)) {
            return Storage::disk($file->disk)->exists($file->path)
                ? [$file->disk, $file->path]
                : null;
        }

        return $this->locateLegacy($file->name);
    }

    /**
     * Look for a legacy file name in every known legacy folder.
     *
     * @return array{0: string, 1: string}|null
     */
    public function locateLegacy(?string $name, ?string $folder = null): ?array
    {
        if (blank($name)) {
            return null;
        }

        $name = basename($name);
        $disk = config('files.disk');
        $root = trim(config('files.legacy_root'), '/');
        $folders = $folder !== null ? [$folder] : config('files.legacy_folders');

        foreach ($folders as $candidate) {
            $path = $root . '/' . $candidate . '/' . $name;

            if (Storage::disk($disk)->exists($path)) {
                return [$disk, $path];
            }
        }

        return null;
    }

    /**
     * Number of entities a file is still attached to.
     */
    public function attachmentCount(File $file): int
    {
        return (int) DB::table('fileables')
            ->where('file_id', $file->id)
            ->count();
    }

    /**
     * Keep the legacy product columns in sync with the primary files.
     *
     * They are no longer the source of truth but a read cache: quote lines,
     * order lines, the API resource and TaskStatu still read them, so dropping
     * them would be a much wider change than this refactor.
     */
    public function refreshLegacyColumns(Model $entity): void
    {
        if (! $entity instanceof Products) {
            return;
        }

        $mapping = [
            FileRole::PLAN => 'drawing_file',
            FileRole::MODEL_3D => 'stl_file',
            FileRole::VECTOR => 'svg_file',
            FileRole::PHOTO => 'picture',
        ];

        $primaries = $entity->files()
            ->wherePivot('is_primary', true)
            ->get()
            ->keyBy(fn (File $file) => $file->pivot->role);

        $updates = [];

        foreach ($mapping as $role => $column) {
            $file = $primaries->get($role);

            // stl_file only makes sense for a mesh: a STEP model is a valid
            // primary 3D file but the legacy three.js loader cannot read it.
            if ($file !== null
                && $role === FileRole::MODEL_3D
                && $file->kind !== FileKindResolver::KIND_MESH) {
                $file = null;
            }

            $value = $file?->name;

            if ($entity->{$column} !== $value) {
                $updates[$column] = $value;
            }
        }

        if ($updates !== []) {
            $entity->forceFill($updates)->save();
        }
    }

    /**
     * Demote every file currently primary for a role on an entity.
     */
    private function demoteRole(Model $entity, string $role): void
    {
        $ids = $entity->files()
            ->wherePivot('role', $role)
            ->wherePivot('is_primary', true)
            ->pluck('files.id');

        foreach ($ids as $id) {
            $entity->files()->updateExistingPivot($id, ['is_primary' => false]);
        }
    }

    /**
     * Trim a comment down to null when it carries nothing.
     */
    private function normalizeComment(?string $comment): ?string
    {
        if ($comment === null) {
            return null;
        }

        $comment = trim($comment);

        return $comment === '' ? null : $comment;
    }

    /**
     * Normalize a raw "#tag1 #tag2" string into a list of unique tags.
     *
     * @return array<int, string>
     */
    public function normalizeHashtags(?string $rawHashtags): array
    {
        if ($rawHashtags === null) {
            return [];
        }

        $parts = preg_split('/[\s,]+/u', $rawHashtags);

        if ($parts === false) {
            return [];
        }

        $hashtags = [];

        foreach ($parts as $part) {
            $tag = trim(ltrim(trim($part), "#＃"));

            if ($tag === '') {
                continue;
            }

            $tag = mb_substr($tag, 0, 50);
            $hashtags[mb_strtolower($tag)] ??= $tag;
        }

        return array_values($hashtags);
    }
}
