<?php

namespace App\Services\HumanResources;

use App\Models\Times\TimesAbsence;
use App\Models\User;
use App\Notifications\AbsenceRequestNotification;
use App\Services\Files\FileConfidentiality;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Notifications du circuit de validation des absences (times_absences.statu :
 * 1 = à valider, 2 = validé, 3 = refusé). Celui qui agit n'est jamais notifié.
 */
class AbsenceNotifier
{
    /** Nouvelle demande : RH (permission ou rôle Admin) et responsable du salarié. */
    public function requested(TimesAbsence $absence, ?int $actorId): void
    {
        $employee = User::find($absence->user_id);

        $recipients = $this->approvers()
            ->when($employee?->supervisor_id, fn (Collection $users) => $users->push(User::find($employee->supervisor_id)))
            ->filter()
            ->unique('id')
            ->reject(fn (User $user) => $user->id === $actorId || $user->id === (int) $absence->user_id);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new AbsenceRequestNotification($absence, AbsenceRequestNotification::REQUESTED, $actorId));
        }
    }

    /** Validation ou refus : le salarié, sauf s'il a traité sa propre demande. */
    public function decided(TimesAbsence $absence, ?int $actorId): void
    {
        $event = match ((int) $absence->statu) {
            2 => AbsenceRequestNotification::APPROVED,
            3 => AbsenceRequestNotification::REFUSED,
            default => null,
        };

        $employee = User::find($absence->user_id);
        if (! $event || ! $employee || $employee->id === $actorId) {
            return;
        }

        $employee->notify(new AbsenceRequestNotification($absence, $event, $actorId));
    }

    /** Porteurs du droit de valider, selon la même règle que le dossier salarié. */
    private function approvers(): Collection
    {
        return User::with(['roles', 'permissions'])->get()
            ->filter(fn (User $user) => $user->hasRole('Admin') || $user->can(FileConfidentiality::HR_PERMISSION))
            ->values();
    }
}
