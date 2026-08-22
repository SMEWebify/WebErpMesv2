<?php

namespace App\Services\Files;

/**
 * Business roles a file can take on an entity. Stored on the fileables pivot.
 *
 * On products, the first four replace the legacy drawing_file / stl_file /
 * svg_file / picture columns, which are now kept in sync as a read cache by
 * FileStorageService.
 */
class FileRole
{
    public const PLAN = 'plan';
    public const MODEL_3D = 'modele_3d';
    public const VECTOR = 'vectoriel';
    public const PHOTO = 'photo';
    public const CAM = 'cam';
    public const CERTIFICATE = 'certificat';
    public const CONTROL = 'controle';
    // Human resources
    public const CONTRACT = 'contrat';
    public const PAYSLIP = 'bulletin_paie';
    public const SICK_NOTE = 'arret_travail';
    public const DIPLOMA = 'diplome';
    public const IDENTITY = 'identite';
    public const OTHER = 'autre';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::PLAN,
            self::MODEL_3D,
            self::VECTOR,
            self::PHOTO,
            self::CAM,
            self::CERTIFICATE,
            self::CONTROL,
            self::CONTRACT,
            self::PAYSLIP,
            self::SICK_NOTE,
            self::DIPLOMA,
            self::IDENTITY,
            self::OTHER,
        ];
    }

    /**
     * Roles offered on an employee folder, in display order.
     *
     * @return array<int, string>
     */
    public static function forHumanResources(): array
    {
        return [
            self::CONTRACT,
            self::PAYSLIP,
            self::SICK_NOTE,
            self::DIPLOMA,
            self::IDENTITY,
            self::CERTIFICATE,
            self::OTHER,
        ];
    }

    /**
     * Roles offered on a product, in display order.
     *
     * @return array<int, string>
     */
    public static function forProducts(): array
    {
        return [
            self::PLAN,
            self::MODEL_3D,
            self::VECTOR,
            self::PHOTO,
            self::CAM,
            self::CERTIFICATE,
            self::CONTROL,
            self::OTHER,
        ];
    }

    /**
     * Translation key of a role label.
     */
    public static function transKey(string $role): string
    {
        return 'general_content.file_role_' . $role . '_trans_key';
    }

    /**
     * Roles as [value => translated label] pairs, ready for a select.
     *
     * @param  array<int, string>|null  $roles
     * @return array<string, string>
     */
    public static function options(?array $roles = null): array
    {
        $options = [];

        foreach ($roles ?? self::all() as $role) {
            $options[$role] = __(self::transKey($role));
        }

        return $options;
    }

    /**
     * Guess the most likely role for a freshly uploaded file from its kind.
     */
    public static function guessFromKind(string $kind): string
    {
        return match ($kind) {
            FileKindResolver::KIND_DOC => self::PLAN,
            FileKindResolver::KIND_MESH, FileKindResolver::KIND_BREP => self::MODEL_3D,
            FileKindResolver::KIND_VECTOR, FileKindResolver::KIND_CAD2D => self::VECTOR,
            FileKindResolver::KIND_IMAGE => self::PHOTO,
            default => self::OTHER,
        };
    }
}
