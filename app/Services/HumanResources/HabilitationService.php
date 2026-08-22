<?php

namespace App\Services\HumanResources;

use App\Models\HumanResources\TrainingType;
use App\Models\OSH\OSHFormation;
use App\Models\Planning\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Versatility matrix built on top of the OSH training register.
 *
 * Deliberately advisory: nothing here is called by the planning, the task
 * assignment or the shop-floor screens. It reports who holds what and flags
 * what looks wrong; deciding to run the job anyway stays with the workshop.
 * An expired authorisation must never stop a machine.
 */
class HabilitationService
{
    /** Never trained for this. */
    public const STATUS_MISSING = 'missing';

    /** Trained, certification not obtained. */
    public const STATUS_FAILED = 'failed';

    /** Held and valid. */
    public const STATUS_VALID = 'valid';

    /** Valid but expiring within the warning window. */
    public const STATUS_EXPIRING = 'expiring';

    /** Expiry date passed. */
    public const STATUS_EXPIRED = 'expired';

    /** osh_formations.certification_obtained: 1 = yes, 2 = no. */
    private const CERTIFICATION_OBTAINED = 1;

    /**
     * How long before the expiry date an authorisation is flagged.
     */
    public function warningDays(): int
    {
        return (int) config('hr.habilitation_warning_days', 60);
    }

    /**
     * Matrix employee × training type.
     *
     * @param  iterable<int, User|int>  $users
     * @return array<int, array<int, array{status: string, training: OSHFormation|null, expires_at: Carbon|null}>>
     */
    public function matrix(iterable $users, ?Collection $types = null, ?Carbon $reference = null): array
    {
        $userIds = [];

        foreach ($users as $user) {
            $userIds[] = $user instanceof User ? (int) $user->id : (int) $user;
        }

        $userIds = array_values(array_unique($userIds));
        $types ??= TrainingType::active()->orderBy('ordre')->orderBy('label')->get();
        $reference = ($reference ?? Carbon::now())->copy()->startOfDay();

        // Latest training wins: a renewal supersedes the expired session.
        $trainings = OSHFormation::query()
            ->whereIn('user_id', $userIds)
            ->whereNotNull('training_type_id')
            ->orderBy('training_date')
            ->get()
            ->groupBy('user_id');

        $matrix = [];

        foreach ($userIds as $userId) {
            $held = collect($trainings->get($userId, []))->keyBy('training_type_id');

            foreach ($types as $type) {
                $matrix[$userId][$type->id] = $this->evaluate($held->get($type->id), $reference);
            }
        }

        return $matrix;
    }

    /**
     * Status of one employee for one training type.
     *
     * @return array{status: string, training: OSHFormation|null, expires_at: Carbon|null}
     */
    public function statusFor(int $userId, int $trainingTypeId, ?Carbon $reference = null): array
    {
        $training = OSHFormation::query()
            ->where('user_id', $userId)
            ->where('training_type_id', $trainingTypeId)
            ->orderBy('training_date')
            ->get()
            ->last();

        return $this->evaluate($training, ($reference ?? Carbon::now())->copy()->startOfDay());
    }

    /**
     * Tasks whose assignee does not hold a valid authorisation for one of the
     * resources the task runs on.
     *
     * Purely a report: it is computed on demand for the matrix screen and does
     * not gate anything.
     *
     * @return array<int, array{task: Task, user: User, resource: mixed, type: TrainingType, status: string}>
     */
    public function taskAlerts(?Carbon $reference = null): array
    {
        $requirements = TrainingType::active()
            ->with('resources:id,code,label')
            ->get()
            ->filter(fn (TrainingType $type) => $type->resources->isNotEmpty());

        if ($requirements->isEmpty()) {
            return [];
        }

        // resource id => training types expected on it
        $byResource = [];

        foreach ($requirements as $type) {
            foreach ($type->resources as $resource) {
                $byResource[$resource->id][] = $type;
            }
        }

        $tasks = Task::query()
            ->whereNotNull('user_id')
            ->with(['user:id,name', 'resources:id,code,label'])
            ->whereHas('resources', fn ($query) => $query->whereIn('methods_ressources.id', array_keys($byResource)))
            ->get();

        if ($tasks->isEmpty()) {
            return [];
        }

        $matrix = $this->matrix($tasks->pluck('user_id')->all(), $requirements->values(), $reference);

        $alerts = [];

        foreach ($tasks as $task) {
            foreach ($task->resources as $resource) {
                foreach ($byResource[$resource->id] ?? [] as $type) {
                    $status = $matrix[$task->user_id][$type->id]['status'] ?? self::STATUS_MISSING;

                    if (in_array($status, [self::STATUS_VALID, self::STATUS_EXPIRING], true)) {
                        continue;
                    }

                    $alerts[] = [
                        'task' => $task,
                        'user' => $task->user,
                        'resource' => $resource,
                        'type' => $type,
                        'status' => $status,
                    ];
                }
            }
        }

        return $alerts;
    }

    /**
     * @return array{status: string, training: OSHFormation|null, expires_at: Carbon|null}
     */
    private function evaluate(?OSHFormation $training, Carbon $reference): array
    {
        if ($training === null) {
            return ['status' => self::STATUS_MISSING, 'training' => null, 'expires_at' => null];
        }

        if ((int) $training->certification_obtained !== self::CERTIFICATION_OBTAINED) {
            return ['status' => self::STATUS_FAILED, 'training' => $training, 'expires_at' => null];
        }

        $expiresAt = $this->toDate($training->expiration_date);

        // No expiry date means a lifelong authorisation.
        if ($expiresAt === null) {
            return ['status' => self::STATUS_VALID, 'training' => $training, 'expires_at' => null];
        }

        if ($expiresAt->lt($reference)) {
            return ['status' => self::STATUS_EXPIRED, 'training' => $training, 'expires_at' => $expiresAt];
        }

        $status = $expiresAt->lte($reference->copy()->addDays($this->warningDays()))
            ? self::STATUS_EXPIRING
            : self::STATUS_VALID;

        return ['status' => $status, 'training' => $training, 'expires_at' => $expiresAt];
    }

    private function toDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
