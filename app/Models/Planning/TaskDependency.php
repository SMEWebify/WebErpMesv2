<?php

namespace App\Models\Planning;

use App\Enums\TaskDependencyLagUnit;
use App\Enums\TaskDependencyType;
use Illuminate\Database\Eloquent\Model;

/**
 * Lien entre deux opérations d'une même gamme : `task_id` est le successeur,
 * `predecessor_task_id` l'opération dont il dépend.
 *
 * Une opération sans aucun lien suit la précédente dans l'ordre de gamme
 * (fin-début implicite) : c'est le comportement historique, conservé tant
 * qu'on ne saisit rien.
 */
class TaskDependency extends Model
{
    protected $table = 'task_dependencies';

    protected $fillable = ['task_id', 'predecessor_task_id', 'type', 'lag_value', 'lag_unit'];

    protected $attributes = [
        'type'      => 'FS',
        'lag_value' => 0,
        'lag_unit'  => 'hour',
    ];

    protected $casts = [
        'type'      => TaskDependencyType::class,
        'lag_unit'  => TaskDependencyLagUnit::class,
        'lag_value' => 'float',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function predecessor()
    {
        return $this->belongsTo(Task::class, 'predecessor_task_id');
    }

    /**
     * Lignes de commande dont au moins une opération porte un lien explicite :
     * les seules que l'ordonnanceur fait passer par le chemin « dépendances ».
     *
     * @return array<int, true>
     */
    public static function linkedOrderLineIds(): array
    {
        return static::query()
            ->join('tasks', 'tasks.id', '=', 'task_dependencies.task_id')
            ->whereNotNull('tasks.order_lines_id')
            ->whereNull('tasks.deleted_at')
            ->distinct()
            ->pluck('tasks.order_lines_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }

    /** Supprime les liens d'une opération, dans les deux sens. */
    public static function deleteForTask(int $taskId): void
    {
        static::query()
            ->where('task_id', $taskId)
            ->orWhere('predecessor_task_id', $taskId)
            ->delete();
    }
}
