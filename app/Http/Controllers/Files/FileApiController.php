<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Http\Requests\Files\ReplaceFileRequest;
use App\Http\Requests\Files\StoreFilesRequest;
use App\Http\Requests\Files\UpdateFileRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Services\Cad\Bvc\BvcNestPreviews;
use App\Services\Files\FileableRegistry;
use App\Services\Files\FileConfidentiality;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use App\Services\Files\FileVersioning;
use App\Services\Files\OfficeEdit;
use App\Services\Files\Preview\FilePreview;
use App\Services\Files\Preview\PreviewUnavailable;
use App\Services\Files\ThumbnailStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * JSON endpoints backing the React FileManager.
 *
 * Replaces the per-format upload routes (products.update.stl, .svg, .drawing,
 * .image) with a single multi-file entry point usable from any entity.
 */
class FileApiController extends Controller
{
    public function __construct(private readonly FileStorageService $storage)
    {
    }

    /**
     * List the files attached to an entity.
     */
    public function index(Request $request): JsonResponse
    {
        $entity = $this->resolveEntity($request->query('fileable_type'), $request->query('fileable_id'), $request);

        // Replaced versions stay attached but only show up in their history.
        $files = $entity->files()
            ->with('UserManagement:id,name')
            ->whereNull('files.superseded_at')
            ->orderByPivot('is_primary', 'desc')
            ->orderBy('files.created_at', 'desc')
            ->get();

        return response()->json([
            'files' => FileResource::collection($files)->resolve(),
        ]);
    }

    /**
     * Store one or more uploads and attach them to the entity.
     */
    public function store(StoreFilesRequest $request, BvcNestPreviews $nests): JsonResponse
    {
        $entity = $this->resolveEntity($request->input('fileable_type'), $request->input('fileable_id'), $request, write: true);

        $hashtags = $this->storage->normalizeHashtags($request->input('hashtags'));
        $role = $request->input('role');
        $isPrimary = $request->boolean('is_primary');

        $created = DB::transaction(function () use ($request, $entity, $hashtags, $role, $isPrimary, $nests) {
            $files = [];

            foreach ($request->file('files') as $upload) {
                $file = $this->storage->store($upload, [
                    'comment' => $request->input('comment'),
                    'hashtags' => $hashtags,
                    'revision_label' => $request->input('revision_label'),
                ]);

                // Only the first upload of a batch can claim the primary slot,
                // otherwise the last one would silently demote the others.
                $this->storage->attach($file, $entity, $role, $isPrimary && $files === []);

                $files[] = $file;

                // A BySoft job is download only: its nests are drawn next to it.
                // A job that cannot be read is still kept, just without them.
                if ($nests->supports($file, $entity)) {
                    try {
                        array_push($files, ...$nests->attach($upload->getRealPath(), $file, $entity));
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            }

            return $files;
        });

        $ids = array_map(static fn (File $file) => $file->id, $created);

        $fresh = $entity->files()
            ->with('UserManagement:id,name')
            ->whereIn('files.id', $ids)
            ->get();

        return response()->json([
            'files' => FileResource::collection($fresh)->resolve(),
        ], 201);
    }

    /**
     * Update the metadata of a file for a given entity.
     */
    public function update(UpdateFileRequest $request, File $file): JsonResponse
    {
        $this->authorize('update', $file);

        $entity = $this->resolveEntity($request->input('fileable_type'), $request->input('fileable_id'), $request, write: true);

        // Route model binding gives us a File without pivot, so read the current
        // attachment from the entity side.
        $attached = $entity->files()->whereKey($file->id)->first();

        abort_if($attached === null, 404);

        $attributes = [];

        if ($request->has('comment')) {
            $comment = trim((string) $request->input('comment'));
            $attributes['comment'] = $comment === '' ? null : $comment;
        }

        if ($request->has('hashtags')) {
            $attributes['hashtags'] = $this->storage->normalizeHashtags($request->input('hashtags'));
        }

        if ($attributes !== []) {
            $file->update($attributes);
        }

        $role = $request->input('role')
            ?? $attached->pivot->role
            ?? FileRole::guessFromKind($file->kind ?? '');

        if ($request->boolean('is_primary')) {
            $this->storage->markPrimary($file, $entity, $role);
        } elseif ($request->has('role') || $request->has('is_primary')) {
            $entity->files()->updateExistingPivot($file->id, [
                'role' => $role,
                'is_primary' => false,
            ]);
            $this->storage->refreshLegacyColumns($entity);
        }

        $fresh = $entity->files()
            ->with('UserManagement:id,name')
            ->whereKey($file->id)
            ->firstOrFail();

        return response()->json(['file' => (new FileResource($fresh))->resolve()]);
    }

    /**
     * Detach a file from an entity, deleting it once no entity references it.
     */
    public function destroy(Request $request, File $file, FileVersioning $versioning): JsonResponse
    {
        $this->authorize('delete', $file);

        $entity = $this->resolveEntity($request->input('fileable_type'), $request->input('fileable_id'), $request, write: true);

        abort_unless($entity->files()->whereKey($file->id)->exists(), 404);

        // The versions of a drawing are its design history: a wrong upload is
        // corrected by a further version, never by deleting one.
        abort_if($versioning->hasHistory($file), 409, __('general_content.file_version_cannot_delete_trans_key'));

        $this->storage->detach($file, $entity);

        return response()->json(['deleted' => true]);
    }

    /**
     * Replace the latest version of a file by a new upload, on every entity it
     * is attached to.
     */
    public function replace(ReplaceFileRequest $request, File $file, FileVersioning $versioning): JsonResponse
    {
        $this->authorize('replace', $file);

        $entity = $this->resolveEntity($request->input('fileable_type'), $request->input('fileable_id'), $request, write: true);

        abort_unless($entity->files()->whereKey($file->id)->exists(), 404);

        $created = $versioning->replace($file, $request->file('file'), [
            'revision_label' => $request->input('revision_label'),
            'comment' => $request->input('comment'),
        ], $request->user());

        $fresh = $entity->files()
            ->with('UserManagement:id,name')
            ->whereKey($created->id)
            ->firstOrFail();

        return response()->json(['file' => (new FileResource($fresh))->resolve()], 201);
    }

    /**
     * Open the latest version of a file in the desktop Office (Excel, Word):
     * returns the ms-excel: / ms-word: link the browser hands to Office. Each
     * save in Office then uploads a new version, like replace().
     */
    public function officeEdit(Request $request, File $file, OfficeEdit $edits): JsonResponse
    {
        $this->authorize('replace', $file);

        $entity = $this->resolveEntity($request->input('fileable_type'), $request->input('fileable_id'), $request, write: true);

        abort_unless($entity->files()->whereKey($file->id)->exists(), 404);
        abort_if($file->isSuperseded(), 409, __('general_content.file_version_already_replaced_trans_key'));

        return response()->json(['url' => $edits->open($file, $request->user())]);
    }

    /**
     * Every version of the lineage a file attached to the entity belongs to.
     */
    public function versions(Request $request, File $file, FileVersioning $versioning): JsonResponse
    {
        $this->authorize('view', $file);

        $entity = $this->resolveEntity($request->query('fileable_type'), $request->query('fileable_id'), $request);

        abort_unless($entity->files()->whereKey($file->id)->exists(), 404);

        $versions = $versioning->history($file)->map(fn (File $version) => [
            'id' => $version->id,
            'name' => $version->original_file_name ?: $version->name,
            'version' => $version->versionNumber(),
            'revision_label' => $version->revision_label,
            'comment' => $version->comment,
            'uploaded_by' => $version->UserManagement?->name,
            'created_at' => $version->created_at?->toIso8601String(),
            'superseded_at' => $version->superseded_at?->toIso8601String(),
            'is_current' => ! $version->isSuperseded(),
            'formatted_size' => $version->formatted_size,
            'view_url' => $version->view_url,
            'download_url' => $version->download_url,
        ])->values();

        return response()->json(['versions' => $versions]);
    }

    /**
     * Stream a file inline, for the viewers.
     */
    public function raw(File $file): StreamedResponse
    {
        $this->authorize('view', $file);

        return $this->storage->response($file);
    }

    /**
     * Content of a spreadsheet or an e-mail, read by the server for the viewers
     * the browser cannot provide. 422 with a reason when it cannot be read: the
     * viewer then offers the download.
     */
    public function preview(File $file, FilePreview $preview): JsonResponse
    {
        $this->authorize('view', $file);

        try {
            $content = $preview->read($file);
        } catch (PreviewUnavailable $e) {
            return response()->json(['reason' => $e->reason], $e->reason === 'unsupported' ? 404 : 422);
        }

        if (($content['type'] ?? null) === 'mail') {
            foreach ($content['attachments'] as &$attachment) {
                $attachment['download_url'] = $attachment['downloadable']
                    ? route('files.preview.attachment', ['file' => $file->id, 'index' => $attachment['index']])
                    : null;
            }
            unset($attachment);
        }

        return response()->json($content);
    }

    /**
     * An attachment of an e-mail kept in the GED, always as an opaque download:
     * its content comes from outside and is never rendered by our origin.
     */
    public function previewAttachment(File $file, int $index, FilePreview $preview): Response
    {
        $this->authorize('view', $file);

        try {
            $attachment = $preview->attachment($file, $index);
        } catch (PreviewUnavailable) {
            abort(404);
        }

        // The name comes from the e-mail: no path separator nor control character
        // in the header, and an ASCII fallback for the browsers that need one.
        $name = trim((string) preg_replace('~[\x00-\x1F\x7F/\\\\]~u', '_', $attachment['name'])) ?: 'piece-jointe';
        $fallback = trim((string) preg_replace('/[^\x20-\x7E]|[%"]/', '_', Str::ascii($name))) ?: 'piece-jointe';

        return response($attachment['data'], 200, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $name, $fallback),
        ]);
    }

    /**
     * Stream a file as an attachment.
     */
    public function download(File $file): StreamedResponse
    {
        $this->authorize('view', $file);

        return $this->storage->response($file, download: true);
    }

    /**
     * Stream the thumbnail of a file, rendered earlier by a browser.
     */
    public function thumbnail(File $file, ThumbnailStore $thumbnails): StreamedResponse
    {
        $this->authorize('view', $file);

        return $thumbnails->response($file->thumbnail_path);
    }

    /**
     * Store the thumbnail a browser just rendered (pdf.js, first page).
     *
     * A thumbnail is derived from the file, so anyone allowed to see the file
     * may provide it; ThumbnailStore only accepts a small image.
     */
    public function storeThumbnail(Request $request, File $file, ThumbnailStore $thumbnails): JsonResponse
    {
        $this->authorize('view', $file);

        $request->validate(['thumbnail' => ['required', 'file']]);

        $file->forceFill([
            'thumbnail_path' => $thumbnails->store($request->file('thumbnail'), 'files', $file->id, $file->thumbnail_path),
        ])->save();

        return response()->json(['thumbnail_url' => $file->thumbnail_url]);
    }

    /**
     * Resolve the whitelisted entity a request targets. $write: attaching,
     * editing or detaching, which some aliases reserve to a further permission
     * (item master: products-edit).
     */
    private function resolveEntity(?string $alias, int|string|null $id, ?Request $request = null, bool $write = false): Model
    {
        $request ??= request();

        abort_if($alias === null || $id === null, 404);

        $permission = FileableRegistry::permissionFor($alias);
        abort_if($permission !== null && ! $request->user()?->can($permission), 403);

        $writePermission = $write ? FileableRegistry::writePermissionFor($alias) : null;
        abort_if($writePermission !== null && ! $request->user()?->can($writePermission), 403);

        $entity = FileableRegistry::find($alias, $id);

        abort_if($entity === null, 404);

        // An employee folder is not browsable by the whole factory: listing or
        // attaching there is reserved to the employee and to HR.
        abort_unless(FileConfidentiality::allows($request->user(), $entity), 403);

        return $entity;
    }
}
