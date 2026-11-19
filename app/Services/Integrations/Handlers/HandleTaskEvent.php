<?php

namespace App\Services\Integrations\Handlers;

use App\Events\TaskChangeStatu;
use App\Models\Integrations\IntegrationEndpoint;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\User;
use App\Services\Integrations\IntegrationEventHandler;
use App\Services\Production\ProductionRelease;
use App\Services\TaskService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * Retour d'exécution atelier (pointage par scan ou manuel), contrat commun
 * ERP / MES « Pointage par scan » (docs/pointage-scan.md). Le système qui
 * pointe est propriétaire de l'exécution : l'ERP enregistre et ne recalcule
 * que le temps.
 */
class HandleTaskEvent implements IntegrationEventHandler
{
    public const EVENT_STARTED       = 'task.started';
    public const EVENT_PAUSED        = 'task.paused';
    public const EVENT_RESUMED       = 'task.resumed';
    public const EVENT_FINISHED      = 'task.finished';
    public const EVENT_DECLARED_GOOD = 'task.declared_good';
    public const EVENT_DECLARED_BAD  = 'task.declared_bad';
    public const EVENT_COMMENTED     = 'task.commented';
    public const EVENT_CANCELED      = 'task.canceled';

    /**
     * Chaque event métier est projeté sur une constante TaskActivities (1..6).
     * pause  → END   (arrêt d'un compteur en cours)
     * resume → START (nouveau compteur)
     * task.canceled n'a pas d'équivalent : traité par cancel().
     */
    private const EVENT_TO_ACTIVITY_TYPE = [
        self::EVENT_STARTED       => TaskActivities::TYPE_START,
        self::EVENT_PAUSED        => TaskActivities::TYPE_END,
        self::EVENT_RESUMED       => TaskActivities::TYPE_START,
        self::EVENT_FINISHED      => TaskActivities::TYPE_FINISH,
        self::EVENT_DECLARED_GOOD => TaskActivities::TYPE_DECLARE_GOOD,
        self::EVENT_DECLARED_BAD  => TaskActivities::TYPE_DECLARE_BAD,
        self::EVENT_COMMENTED     => TaskActivities::TYPE_COMMENT,
    ];

    /**
     * Statuses table est adressée par title (voir TaskStatuController@start / @finish).
     * On ne mappe QUE started/resumed/finished — les autres events ne changent pas
     * l'état perçu (un DECLARE_GOOD arrive tâche déjà En cours).
     */
    private const EVENT_TO_STATUS_TITLE = [
        self::EVENT_STARTED  => 'In progress',
        self::EVENT_RESUMED  => 'In progress',
        self::EVENT_FINISHED => 'Finished',
    ];

    /** Écart toléré entre actual_time_min reçu et le temps recalculé par l'ERP. */
    private const ACTUAL_TIME_TOLERANCE_MIN = 1.0;

    public function __construct(private TaskService $taskService)
    {
    }

    public function handle(IntegrationEndpoint $endpoint, array $data, array $meta): void
    {
        $eventType = (string) ($meta['event_type'] ?? '');
        if ($eventType !== self::EVENT_CANCELED && ! array_key_exists($eventType, self::EVENT_TO_ACTIVITY_TYPE)) {
            throw new InvalidArgumentException("Unknown task event type: {$eventType}");
        }

        $ofCode = (string) ($data['of_code'] ?? '');
        $lineRef = (string) ($data['line_ref'] ?? '');
        $operationCode = (string) ($data['operation_code'] ?? '');
        $seq = isset($data['seq']) && is_numeric($data['seq']) ? (int) $data['seq'] : null;

        $task = Task::resolveByExternalRef($ofCode, $lineRef, $operationCode, $seq);
        if (! $task) {
            throw new RuntimeException(
                "Task not found for external ref: of={$ofCode} line={$lineRef} op={$operationCode} seq=" . ($seq ?? '-')
            );
        }

        // Même borne que recordTaskActivity : un horodatage futur vaut maintenant.
        $occurredAt = $this->normalizeTimestamp($meta['occurred_at'] ?? null);
        if ($occurredAt->greaterThan(Carbon::now())) {
            $occurredAt = Carbon::now();
        }

        $userId = $this->resolveUserId($data, $task, $meta);

        if ($eventType === self::EVENT_CANCELED) {
            $this->cancel($task, $occurredAt, $userId, (string) ($meta['source'] ?? ''));
            return;
        }

        $activityType = self::EVENT_TO_ACTIVITY_TYPE[$eventType];

        // Bon pour fabrication : l'événement échoue (livraison en erreur, rejouable
        // une fois le BPF donné) plutôt que d'enregistrer un démarrage interdit.
        if ($activityType === TaskActivities::TYPE_START) {
            app(ProductionRelease::class)->assertCanStart($task);
        }

        // Lu AVANT d'écrire l'activité : un événement arrivé en retard (plus
        // ancien qu'un démarrage ou une fin déjà enregistrés) entre dans
        // l'historique et le calcul du temps, mais ne fait pas reculer le statut.
        $statusIsStale = $this->hasLaterStatusChange($task, $occurredAt);

        // Contrat : quantités entières, en écarts, jamais négatives. Elles ne sont
        // comptées que sur declared_good / declared_bad (Task::getTotalLogGoodQt).
        $this->taskService->recordTaskActivity(
            taskId:             $task->getKey(),
            type:               $activityType,
            goodQty:            max(0, (int) ($data['good_qty'] ?? 0)),
            addBadQt:           max(0, (int) ($data['bad_qty'] ?? 0)),
            comment:            (string) ($data['comment'] ?? ''),
            userIdOverride:     $userId,
            timestampOverride:  $occurredAt,
        );

        if (! $statusIsStale) {
            $this->syncTaskStatus($task, $eventType);
        }

        if ($eventType === self::EVENT_FINISHED) {
            $this->compareActualTime($task, $data, $meta);
        }
    }

    /**
     * Annulation : les sessions encore ouvertes à l'instant de l'annulation sont
     * refermées (le temps déjà passé reste compté, plus rien ne court), aucune
     * quantité, et une trace dans l'historique. Le statut ne change pas : l'ERP
     * n'a pas de statut « annulé » pour une tâche.
     */
    private function cancel(Task $task, Carbon $occurredAt, ?int $userId, string $source): void
    {
        foreach ($this->openSessionUsersAt($task, $occurredAt) as $sessionUserId) {
            $this->taskService->recordTaskActivity(
                taskId:            $task->getKey(),
                type:              TaskActivities::TYPE_END,
                userIdOverride:    $sessionUserId ?? $userId,
                timestampOverride: $occurredAt,
            );
        }

        $this->taskService->recordTaskActivity(
            taskId:            $task->getKey(),
            type:              TaskActivities::TYPE_COMMENT,
            comment:           __('general_content.task_canceled_by_integration_trans_key') . ($source !== '' ? " ({$source})" : ''),
            userIdOverride:    $userId,
            timestampOverride: $occurredAt,
        );
    }

    /**
     * Opérateurs des sessions encore ouvertes à $at, une entrée par session —
     * même appariement que Task::workedSecondsFrom().
     *
     * @return array<int, ?int>
     */
    private function openSessionUsersAt(Task $task, Carbon $at): array
    {
        $activities = TaskActivities::query()
            ->where('task_id', $task->getKey())
            ->whereIn('type', [TaskActivities::TYPE_START, TaskActivities::TYPE_END, TaskActivities::TYPE_FINISH])
            ->where('timestamp', '<=', $at)
            ->orderBy('timestamp')
            ->orderBy('id')
            ->get(['type', 'user_id', 'methods_ressources_id']);

        $open = [];
        foreach ($activities as $activity) {
            $key = ($activity->user_id ?? '-') . '|' . ($activity->methods_ressources_id ?? '-');
            $type = (int) $activity->type;

            if ($type === TaskActivities::TYPE_START) {
                if (! array_key_exists($key, $open)) {
                    $open[$key] = $activity->user_id !== null ? (int) $activity->user_id : null;
                }
            } elseif ($type === TaskActivities::TYPE_FINISH) {
                $open = [];
            } elseif (array_key_exists($key, $open)) {
                unset($open[$key]);
            } elseif ($open !== []) {
                unset($open[array_key_first($open)]);
            }
        }

        return array_values($open);
    }

    /**
     * Un démarrage ou une fin plus récents que l'événement sont déjà enregistrés.
     */
    private function hasLaterStatusChange(Task $task, Carbon $occurredAt): bool
    {
        return TaskActivities::query()
            ->where('task_id', $task->getKey())
            ->whereIn('type', [TaskActivities::TYPE_START, TaskActivities::TYPE_FINISH])
            ->where('timestamp', '>', $occurredAt)
            ->exists();
    }

    /**
     * actual_time_min du système qui pointe est informatif : l'ERP recalcule le
     * temps depuis les débuts / fins. Un écart notable est seulement journalisé.
     */
    private function compareActualTime(Task $task, array $data, array $meta): void
    {
        if (! isset($data['actual_time_min']) || ! is_numeric($data['actual_time_min'])) {
            return;
        }

        $received = (float) $data['actual_time_min'];
        $computed = round(Task::query()->findOrFail($task->getKey())->getTotalLogSeconds() / 60, 1);

        if (abs($received - $computed) > self::ACTUAL_TIME_TOLERANCE_MIN) {
            Log::channel('n2p')->warning('Task actual time differs from the ERP computation', [
                'task_id' => $task->getKey(),
                'event_id' => $meta['event_id'] ?? null,
                'received_min' => $received,
                'computed_min' => $computed,
            ]);
        }
    }

    /**
     * Aligne le statut ERP de la tâche sur l'event N2P. Silencieux si le
     * status n'existe pas encore en base (installation neuve, cf. absence de
     * seeder prod pour la table statuses).
     */
    private function syncTaskStatus(Task $task, string $eventType): void
    {
        $title = self::EVENT_TO_STATUS_TITLE[$eventType] ?? null;
        if ($title === null) {
            return;
        }

        $statusId = Status::query()->where('title', $title)->value('id');
        if (! $statusId) {
            return;
        }

        if ((int) $task->status_id === (int) $statusId) {
            return;
        }

        $task->status_id = $statusId;
        $task->save();

        // Doit propager pour que CheckOrderLineTaskStatus recalcule
        // order_lines.tasks_status (sinon la ligne reste bloquée à "Créé"
        // même quand toutes les tâches passent Finished par événement N2P).
        event(new TaskChangeStatu($task->getKey()));
    }

    /**
     * Opérateur = user_email du contrat (badge actif, sinon utilisateur connecté
     * côté MES). user_id reste lu pour les anciens émetteurs. Introuvable :
     * utilisateur technique INTEGRATIONS_FALLBACK_USER_EMAIL s'il existe, sinon
     * le repli historique de recordTaskActivity — toujours journalisé.
     */
    private function resolveUserId(array $data, Task $task, array $meta): ?int
    {
        if (isset($data['user_id']) && is_numeric($data['user_id'])
            && User::query()->whereKey((int) $data['user_id'])->exists()) {
            return (int) $data['user_id'];
        }

        $email = $data['user_email'] ?? null;
        if (is_string($email) && $email !== '') {
            $userId = User::query()->where('email', $email)->value('id');
            if ($userId) {
                return (int) $userId;
            }
        }

        $fallbackEmail = (string) config('integrations.fallback_user_email', '');
        $fallbackId = $fallbackEmail !== ''
            ? User::query()->where('email', $fallbackEmail)->value('id')
            : null;

        Log::channel('n2p')->warning('Task event operator not found', [
            'task_id' => $task->getKey(),
            'event_id' => $meta['event_id'] ?? null,
            'user_email' => is_string($email) ? $email : null,
            'fallback_user_id' => $fallbackId,
        ]);

        return $fallbackId ? (int) $fallbackId : null;
    }

    private function normalizeTimestamp(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }
        if ($value === null || $value === '') {
            return Carbon::now();
        }
        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return Carbon::now();
        }
    }
}
