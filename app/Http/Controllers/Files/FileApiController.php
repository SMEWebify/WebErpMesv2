<?php

namespace App\Http\Controllers\Files;

use App\Http\Controllers\Controller;
use App\Http\Requests\Files\StoreFilesRequest;
use App\Http\Requests\Files\UpdateFileRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Services\Files\FileableRegistry;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $entity = $this->resolveEntity($request->query('fileable_type'), $request->query('fileable_id'));

        $files = $entity->files()
            ->with('UserManagement:id,name')
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
    public function store(StoreFilesRequest $request): JsonResponse
    {
        $entity = $this->resolveEntity($request->input('fileable_type'), $request->input('fileable_id'));

        $hashtags = $this->storage->normalizeHashtags($request->input('hashtags'));
        $role = $request->input('role');
        $isPrimary = $request->boolean('is_primary');

        $created = DB::transaction(function () use ($request, $entity, $hashtags, $role, $isPrimary) {
            $files = [];

            foreach ($request->file('files') as $upload) {
                $file = $this->storage->store($upload, [
                    'comment' => $request->input('comment'),
                    'hashtags' => $hashtags,
                ]);

                // Only the first upload of a batch can claim the primary slot,
                // otherwise the last one would silently demote the others.
                $this->storage->attach($file, $entity, $role, $isPrimary && $files === []);

                $files[] = $file;
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

        $entity = $this->resolveEntity($request->input('fileable_type'), $request->input('fileable_id'));

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
    public function destroy(Request $request, File $file): JsonResponse
    {
        $this->authorize('delete', $file);

        $entity = $this->resolveEntity($request->input('fileable_type'), $request->input('fileable_id'));

        abort_unless($entity->files()->whereKey($file->id)->exists(), 404);

        $this->storage->detach($file, $entity);

        return response()->json(['deleted' => true]);
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
     * Stream a file as an attachment.
     */
    public function download(File $file): StreamedResponse
    {
        $this->authorize('view', $file);

        return $this->storage->response($file, download: true);
    }

    /**
     * Resolve the whitelisted entity a request targets.
     */
    private function resolveEntity(?string $alias, int|string|null $id): Model
    {
        abort_if($alias === null || $id === null, 404);

        $entity = FileableRegistry::find($alias, $id);

        abort_if($entity === null, 404);

        return $entity;
    }
}
