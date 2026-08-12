<?php

namespace App\Services\Integrations\Handlers;

use App\Events\TaskChangeStatu;
use App\Models\Integrations\IntegrationEndpoint;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\User;
use App\Services\Integrations\IntegrationEventHandler;
use App\Services\TaskService;
use Carbon\Carbon;
use InvalidArgumentException;
use RuntimeException;

class HandleTaskEvent implements IntegrationEventHandler
{
    public const EVENT_STARTED       = 'task.started';
    public const EVENT_PAUSED        = 'task.paused';
    public const EVENT_RESUMED       = 'task.resumed';
    public const EVENT_FINISHED      = 'task.finished';
    public const EVENT_DECLARED_GOOD = 'task.declared_good';
    public const EVENT_DECLARED_BAD  = 'task.declared_bad';
    public const EVENT_COMMENTED     = 'task.commented';

    /**
     * Chaque event métier est projeté sur une constante TaskActivities (1..6).
     * pause  → END   (arrêt d'un compteur en cours)
     * resume → START (nouveau compteur)
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

    public function __construct(private TaskService $taskService)
    {
    }

    public function handle(IntegrationEndpoint $endpoint, array $data, array $meta): void
    {
        $eventType = (string) ($meta['event_type'] ?? '');
        if (! array_key_exists($eventType, self::EVENT_TO_ACTIVITY_TYPE)) {
            throw new InvalidArgumentException("Unknown task event type: {$eventType}");
        }

        $ofCode = (string) ($data['of_code'] ?? '');
        $lineRef = (string) ($data['line_ref'] ?? '');
        $operationCode = (string) ($data['operation_code'] ?? '');

        $task = Task::resolveByExternalRef($ofCode, $lineRef, $operationCode);
        if (! $task) {
            throw new RuntimeException(
                "Task not found for external ref: of={$ofCode} line={$lineRef} op={$operationCode}"
            );
        }

        $activityType = self::EVENT_TO_ACTIVITY_TYPE[$eventType];
        $occurredAt = $this->normalizeTimestamp($meta['occurred_at'] ?? null);

        $this->taskService->recordTaskActivity(
            taskId:             $task->getKey(),
            type:               $activityType,
            goodQty:            (int) ($data['good_qty'] ?? 0),
            addBadQt:           (int) ($data['bad_qty'] ?? 0),
            comment:            (string) ($data['comment'] ?? ''),
            userIdOverride:     $this->resolveUserId($data),
            timestampOverride:  $occurredAt,
        );

        $this->syncTaskStatus($task, $eventType);
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

    private function resolveUserId(array $data): ?int
    {
        if (isset($data['user_id']) && is_numeric($data['user_id'])) {
            $id = (int) $data['user_id'];
            return User::query()->whereKey($id)->exists() ? $id : null;
        }

        $email = $data['user_email'] ?? null;
        if (is_string($email) && $email !== '') {
            $userId = User::query()->where('email', $email)->value('id');
            return $userId ? (int) $userId : null;
        }

        return null;
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
