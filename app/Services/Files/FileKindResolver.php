<?php

namespace App\Services\Files;

/**
 * Maps a file extension to a functional "kind", which drives both the upload
 * whitelist and the viewer used on the front-end.
 *
 * Adding support for a new format is a one-line change here plus, when the kind
 * is new, a viewer in resources/js/components/files/viewers/.
 */
class FileKindResolver
{
    /** Triangle meshes, rendered directly by three.js. */
    public const KIND_MESH = 'mesh';

    /** Boundary representation (CAD kernel formats), tessellated client-side by OCCT/WASM. */
    public const KIND_BREP = 'brep';

    /** 2D CAD drawings. */
    public const KIND_CAD2D = 'cad2d';

    /** Scalable vector images. */
    public const KIND_VECTOR = 'vector';

    /** Paginated documents. */
    public const KIND_DOC = 'doc';

    /** Raster images. */
    public const KIND_IMAGE = 'image';

    /** Spreadsheets and delimited data. */
    public const KIND_SHEET = 'sheet';

    /** Archives and everything without a viewer. */
    public const KIND_ARCHIVE = 'archive';
    public const KIND_OTHER = 'other';

    /**
     * Extensions accepted per kind. Keys are the kinds, values the lowercase
     * extensions. Order matters only for readability.
     *
     * @var array<string, array<int, string>>
     */
    private const EXTENSIONS = [
        self::KIND_MESH => ['stl', 'obj', '3mf', 'ply', 'glb', 'gltf'],
        self::KIND_BREP => ['step', 'stp', 'iges', 'igs', 'brep'],
        self::KIND_CAD2D => ['dxf'],
        self::KIND_VECTOR => ['svg'],
        self::KIND_DOC => ['pdf'],
        self::KIND_IMAGE => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'],
        self::KIND_SHEET => ['csv', 'xls', 'xlsx', 'ods'],
        self::KIND_ARCHIVE => ['zip', '7z', 'rar', 'gz'],
        // sym and geo are RADAN sources: the import reads them but no viewer
        // renders them, so they stay download only.
        self::KIND_OTHER => ['doc', 'docx', 'odt', 'rtf', 'txt', 'nc', 'tap', 'gcode', 'json', 'xml', 'sym', 'geo'],
    ];

    /**
     * Kinds the front-end can display inline. Anything else is download only.
     *
     * @var array<int, string>
     */
    private const VIEWABLE = [
        self::KIND_MESH,
        self::KIND_BREP,
        self::KIND_CAD2D,
        self::KIND_VECTOR,
        self::KIND_DOC,
        self::KIND_IMAGE,
    ];

    /**
     * Extract the lowercase extension of a file name, without the dot.
     */
    public static function extensionOf(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return $extension === '' ? null : mb_strtolower($extension);
    }

    /**
     * Resolve the kind of a file from its name.
     */
    public static function fromFilename(?string $filename): string
    {
        return self::fromExtension(self::extensionOf($filename));
    }

    /**
     * Resolve the kind of a file from its extension.
     */
    public static function fromExtension(?string $extension): string
    {
        if ($extension === null) {
            return self::KIND_OTHER;
        }

        $extension = mb_strtolower($extension);

        foreach (self::EXTENSIONS as $kind => $extensions) {
            if (in_array($extension, $extensions, true)) {
                return $kind;
            }
        }

        return self::KIND_OTHER;
    }

    /**
     * Every accepted extension, used to build the upload validation rule.
     *
     * @return array<int, string>
     */
    public static function allowedExtensions(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::EXTENSIONS))));
    }

    /**
     * Extensions of a given kind.
     *
     * @return array<int, string>
     */
    public static function extensionsOf(string $kind): array
    {
        return self::EXTENSIONS[$kind] ?? [];
    }

    /**
     * All known kinds.
     *
     * @return array<int, string>
     */
    public static function kinds(): array
    {
        return array_keys(self::EXTENSIONS);
    }

    /**
     * Whether the front-end has a viewer for that kind.
     */
    public static function isViewable(string $kind): bool
    {
        return in_array($kind, self::VIEWABLE, true);
    }

    /**
     * Font Awesome icon used in listings, per kind.
     */
    public static function icon(string $kind): string
    {
        return match ($kind) {
            self::KIND_MESH, self::KIND_BREP => 'fas fa-cube',
            self::KIND_CAD2D => 'fas fa-drafting-compass',
            self::KIND_VECTOR => 'fas fa-vector-square',
            self::KIND_DOC => 'far fa-file-pdf',
            self::KIND_IMAGE => 'far fa-file-image',
            self::KIND_SHEET => 'far fa-file-excel',
            self::KIND_ARCHIVE => 'far fa-file-archive',
            default => 'far fa-file',
        };
    }
}
