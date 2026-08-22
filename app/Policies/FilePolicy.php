<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use App\Services\Files\FileConfidentiality;

/**
 * Documents are readable by any authenticated user of the factory — the routes
 * already carry the auth / verified / has.role / check.factory stack — but only
 * the uploader or an administrator may alter or remove one.
 *
 * Employee folder documents are the exception: FileConfidentiality narrows them
 * down to the employee they belong to and to HR.
 */
class FilePolicy
{
    /**
     * Administrators bypass every check.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, File $file): bool
    {
        // Employee folder documents (contract, payslip, sick note) are personal
        // data: only the employee concerned and HR may open them.
        return FileConfidentiality::allowsFile($user, $file);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, File $file): bool
    {
        return FileConfidentiality::allowsFile($user, $file) && $this->isOwner($user, $file);
    }

    public function delete(User $user, File $file): bool
    {
        return FileConfidentiality::allowsFile($user, $file) && $this->isOwner($user, $file);
    }

    /**
     * Files imported from the pre-polymorphic era may carry no uploader, in
     * which case only an administrator can act on them.
     */
    private function isOwner(User $user, File $file): bool
    {
        return $file->user_id !== null && (int) $file->user_id === (int) $user->id;
    }
}
