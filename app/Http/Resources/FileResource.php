<?php

namespace App\Http\Resources;

use App\Services\Files\FileKindResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape consumed by the React FileManager and its viewers.
 *
 * @mixin \App\Models\File
 */
class FileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->original_file_name ?: $this->name,
            'stored_name' => $this->name,
            'extension' => $this->extension,
            'kind' => $this->kind ?? FileKindResolver::KIND_OTHER,
            'mime' => $this->type,
            'size' => (int) $this->size,
            'formatted_size' => $this->formatted_size,
            'comment' => $this->comment,
            'hashtags' => $this->hashtags ?? [],
            'icon' => $this->icon,
            'is_viewable' => $this->is_viewable,
            'view_url' => $this->view_url,
            'download_url' => $this->download_url,
            'uploaded_by' => $this->whenLoaded('UserManagement', fn () => $this->UserManagement?->name),
            'created_at' => $this->created_at?->toIso8601String(),
            'role' => $this->whenPivotLoaded('fileables', fn () => $this->pivot->role),
            'is_primary' => $this->whenPivotLoaded('fileables', fn () => (bool) $this->pivot->is_primary),
        ];
    }
}
