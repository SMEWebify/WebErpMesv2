<?php

namespace App\Services\Files;

use App\Models\Admin\UserEmploymentContracts;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Access rules for the documents of an employee folder.
 *
 * Every other entity of the GED is readable by the whole factory (the routes
 * already carry auth / verified / has.role / check.factory). A contract, a
 * payslip or a sick note is not: it is personal data, so it is restricted to
 * the employee it belongs to and to whoever holds the HR permission.
 */
class FileConfidentiality
{
    public const HR_PERMISSION = 'human-resources-menu';

    /**
     * May this user read, and act on, the documents of that entity?
     *
     * Returns true for any entity that is not part of an employee folder.
     */
    public static function allows(?User $user, Model $entity): bool
    {
        $alias = FileableRegistry::aliasFor($entity);

        if ($alias === null || !FileableRegistry::isConfidential($alias)) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if (self::isHr($user)) {
            return true;
        }

        return self::subjectOf($entity) === (int) $user->id;
    }

    /**
     * May this user read this file, wherever it is attached?
     *
     * A file shared between a confidential entity and a public one stays
     * confidential: the most restrictive attachment wins.
     */
    public static function allowsFile(?User $user, File $file): bool
    {
        $owners = self::confidentialOwners($file);

        if ($owners === []) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if (self::isHr($user)) {
            return true;
        }

        return $owners === [(int) $user->id];
    }

    public static function isHr(User $user): bool
    {
        return $user->hasRole('Admin') || $user->can(self::HR_PERMISSION);
    }

    /**
     * Employee an employee-folder entity belongs to.
     */
    private static function subjectOf(Model $entity): ?int
    {
        if ($entity instanceof User) {
            return (int) $entity->id;
        }

        if ($entity instanceof UserEmploymentContracts) {
            return (int) $entity->user_id;
        }

        return null;
    }

    /**
     * Employees whose folder this file is attached to.
     *
     * @return array<int, int>
     */
    private static function confidentialOwners(File $file): array
    {
        $attachments = DB::table('fileables')
            ->where('file_id', $file->id)
            ->whereIn('fileable_type', FileableRegistry::confidentialClasses())
            ->get(['fileable_type', 'fileable_id']);

        if ($attachments->isEmpty()) {
            return [];
        }

        $owners = [];
        $contractIds = [];

        foreach ($attachments as $attachment) {
            if ($attachment->fileable_type === User::class) {
                $owners[] = (int) $attachment->fileable_id;
                continue;
            }

            if ($attachment->fileable_type === UserEmploymentContracts::class) {
                $contractIds[] = (int) $attachment->fileable_id;
            }
        }

        if ($contractIds !== []) {
            $owners = array_merge($owners, UserEmploymentContracts::query()
                ->whereIn('id', $contractIds)
                ->pluck('user_id')
                ->map(static fn ($id) => (int) $id)
                ->all());
        }

        return array_values(array_unique($owners));
    }
}
