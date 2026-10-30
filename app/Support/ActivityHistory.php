<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * Historique lisible d'un enregistrement journalisé (activitylog) : une entrée par
 * modification, avec pour chaque champ l'ancienne et la nouvelle valeur.
 *
 * Les libellés de champ viennent de `activity_history.fields`, les valeurs codées
 * (statut, type) de `$values`, et les clés étrangères de `$references`
 * (champ => [modèle, colonne du libellé]) : une requête par référence présente,
 * quel que soit le nombre d'entrées.
 */
class ActivityHistory
{
    public const LIMIT = 50;

    /**
     * @param  array<string, array<int|string, string>>  $values
     * @param  array<string, array{0: class-string<Model>, 1: string}>  $references
     */
    public function __construct(private array $values = [], private array $references = [])
    {
    }

    /** Les dernières modifications de l'enregistrement, la plus récente en premier. */
    public function forSubject(Model $subject, int $limit = self::LIMIT): array
    {
        $activities = Activity::forSubject($subject)
            ->where('log_name', '!=', 'access')
            ->with('causer')
            ->latest('id')
            ->limit($limit)
            ->get();

        return $this->entries($activities);
    }

    /**
     * Historique de plusieurs enregistrements d'un même modèle (une page de liste),
     * groupé par id : nombre de requêtes constant quelle que soit la taille de la page.
     *
     * @param  class-string<Model>  $model
     * @return array<int, array> entrées par id d'enregistrement
     */
    public function forSubjects(string $model, array $ids, int $limit = self::LIMIT): array
    {
        if ($ids === []) {
            return [];
        }

        $activities = Activity::query()
            ->where('subject_type', (new $model)->getMorphClass())
            ->whereIn('subject_id', $ids)
            ->where('log_name', '!=', 'access')
            ->with('causer')
            ->latest('id')
            ->get();

        return collect($this->entries($activities))
            ->groupBy('subject_id')
            ->map(fn (Collection $entries) => $entries->take($limit)->values()->all())
            ->all();
    }

    /** @param  Collection<int, Activity>  $activities */
    public function entries(Collection $activities): array
    {
        $labels = $this->resolveReferences($activities);

        return $activities->map(function (Activity $activity) use ($labels) {
            $new = (array) ($activity->properties['attributes'] ?? []);
            $old = (array) ($activity->properties['old'] ?? []);

            $changes = [];
            foreach (array_unique(array_merge(array_keys($new), array_keys($old))) as $field) {
                $changes[] = [
                    'field' => $field,
                    'label' => $this->fieldLabel($field),
                    'old'   => array_key_exists($field, $old) ? $this->display($field, $old[$field], $labels) : null,
                    'new'   => array_key_exists($field, $new) ? $this->display($field, $new[$field], $labels) : null,
                ];
            }

            return [
                'id'      => $activity->id,
                'subject_id' => $activity->subject_id,
                'event'   => $this->eventLabel($activity),
                'causer'  => $activity->causer?->name ?? __('activity_history.system'),
                'at'      => $activity->created_at?->format('d/m/Y H:i'),
                'changes' => $changes,
            ];
        })->values()->all();
    }

    private function eventLabel(Activity $activity): string
    {
        $key = 'activity_history.events.' . ($activity->event ?: $activity->description);

        return __($key) !== $key ? __($key) : (string) $activity->description;
    }

    private function fieldLabel(string $field): string
    {
        $key = 'activity_history.fields.' . $field;

        return __($key) !== $key ? __($key) : $field;
    }

    /** @return array<string, array<int|string, string>> libellés par champ de référence et par id */
    private function resolveReferences(Collection $activities): array
    {
        $labels = [];

        foreach ($this->references as $field => [$model, $column]) {
            $ids = $activities
                ->flatMap(fn (Activity $a) => [
                    $a->properties['attributes'][$field] ?? null,
                    $a->properties['old'][$field] ?? null,
                ])
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->unique()
                ->values();

            if ($ids->isNotEmpty()) {
                $query = $model::query();
                if (method_exists($model, 'bootSoftDeletes')) {
                    $query->withTrashed();
                }
                $labels[$field] = $query->whereIn('id', $ids)->pluck($column, 'id')->all();
            }
        }

        return $labels;
    }

    private function display(string $field, mixed $value, array $labels): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_scalar($value) && isset($this->values[$field][$value])) {
            return $this->values[$field][$value];
        }

        if (is_scalar($value) && isset($labels[$field][$value])) {
            return (string) $labels[$field][$value];
        }

        if (is_bool($value)) {
            return $value ? __('activity_history.yes') : __('activity_history.no');
        }

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $value)) {
            $date = Carbon::parse($value);

            // Une colonne `date` est sérialisée à minuit UTC : la convertir décalerait le jour.
            return $date->format('H:i:s') === '00:00:00'
                ? $date->format('d/m/Y')
                : $date->timezone(config('app.timezone'))->format('d/m/Y H:i');
        }

        $text = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);

        return Str::limit($text, 200);
    }
}
