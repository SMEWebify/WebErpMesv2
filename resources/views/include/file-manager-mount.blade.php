@php
    use App\Services\Files\FileKindResolver;
    use App\Services\Files\FileRole;

    /**
     * Mount point for the unified document manager.
     *
     * Required: $fileableType (alias from FileableRegistry) and $fileableId.
     * Optional: $fileRoles (subset of FileRole::all()), $fileManagerCanEdit.
     */
    $fileEndpoints = [
        'list'    => route('files.json.list'),
        'store'   => route('files.json.store'),
        'update'  => route('files.json.update', ['file' => '__ID__']),
        'destroy' => route('files.json.destroy', ['file' => '__ID__']),
    ];

    $fileRolesOptions = FileRole::options($fileRoles ?? null);

    $fileAccept = collect(FileKindResolver::allowedExtensions())
        ->map(fn (string $extension) => '.' . $extension)
        ->implode(',');

    $fileTrans = \App\Services\Files\FileTranslations::all();

    $fileJsonFlags = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG;
@endphp

<div data-react="file-manager"
     data-fileable-type="{{ $fileableType }}"
     data-fileable-id="{{ $fileableId }}"
     data-can-edit="{{ ($fileManagerCanEdit ?? true) ? '1' : '0' }}"
     data-accept="{{ $fileAccept }}"
     data-endpoints='@json($fileEndpoints, $fileJsonFlags)'
     data-roles='@json($fileRolesOptions, $fileJsonFlags)'
     data-trans='@json($fileTrans, $fileJsonFlags)'
></div>
