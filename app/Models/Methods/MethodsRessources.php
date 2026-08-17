<?php

namespace App\Models\Methods;

use App\Models\Planning\Task;
use App\Models\Planning\TaskResources;
use App\Models\Planning\TaskActivities;
use App\Models\Times\WorkShiftPattern;
use App\Models\Methods\MethodsSection;
use App\Models\Methods\MethodsLocation;
use App\Models\Methods\MethodsServices;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MethodsRessources extends Model
{
    use HasFactory, SoftDeletes;

    // Fillable attributes for mass assignment
    protected $fillable= ['ordre', 'code',  'label',  'picture', 'mask_time', 'capacity',  'section_id',  'color',  'methods_services_id',  'comment', 'is_labor', 'labor_ratio', 'work_shift_pattern_id'];

    protected $casts = [
        'is_labor'    => 'boolean',
        'labor_ratio' => 'float',
    ];

    /** Niveaux d'habilitation portés par le pivot resource_user. */
    public const LEVEL_TRAINING = 1;
    public const LEVEL_AUTONOMOUS = 2;
    public const LEVEL_REFERENT = 3;

    /**
     * Le service principal est toujours reflété dans le pivot : seeders, imports
     * et créations manuelles restent ainsi cohérents sans avoir à y penser.
     */
    protected static function booted(): void
    {
        static::created(function (self $ressource) {
            $ressource->syncPrincipalService();
        });

        static::updated(function (self $ressource) {
            if ($ressource->wasChanged('methods_services_id')) {
                $ressource->syncPrincipalService();
            }
        });
    }

    private function syncPrincipalService(): void
    {
        if ($this->methods_services_id) {
            $this->services()->syncWithoutDetaching([
                $this->methods_services_id => ['preference' => 0],
            ]);
        }
    }

    /**
     * Service principal de la ressource — conservé pour l'affichage par défaut
     * et la compatibilité. La liste réelle des opérations réalisables est
     * portée par services() : une machine peut en couvrir plusieurs.
     */
    public function service()
    {
        return $this->belongsTo(MethodsServices::class, 'methods_services_id');
    }

    /**
     * Services que cette ressource sait réaliser (pivot methods_ressource_service).
     * `preference` ordonne les choix de l'affectation automatique,
     * `efficiency` pondère le temps gamme, `hourly_rate` surcharge le taux du service.
     */
    public function services()
    {
        return $this->belongsToMany(MethodsServices::class, 'methods_ressource_service', 'methods_ressources_id', 'methods_services_id')
                    ->withPivot(['efficiency', 'hourly_rate', 'preference'])
                    ->withTimestamps()
                    ->orderBy('methods_ressource_service.preference');
    }

    public function tasks() {
        return $this->belongsToMany(Task::class, 'task_resources')
                    ->withPivot(['role', 'source', 'load_factor'])
                    ->withTimestamps();
    }

    /** Tâches consommant la capacité machine de la ressource. */
    public function machineTasks()
    {
        return $this->tasks()->wherePivot('role', TaskResources::ROLE_MACHINE);
    }

    /** Tâches consommant la capacité main-d'œuvre de la ressource. */
    public function laborTasks()
    {
        return $this->tasks()->wherePivot('role', TaskResources::ROLE_LABOR);
    }

    /**
     * Personnes pouvant tenir la ressource : opérateurs d'un poste manuel ou
     * conducteurs habilités d'une machine.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'resource_user', 'methods_ressources_id', 'user_id')
                    ->withPivot(['level', 'certified_until'])
                    ->withTimestamps();
    }

    /** Capacités humaines (équipes, postes manuels). */
    public function scopeLabor(Builder $query): Builder
    {
        return $query->where('is_labor', true);
    }

    /** Capacités machine. */
    public function scopeMachines(Builder $query): Builder
    {
        return $query->where('is_labor', false);
    }

    /** Rôle consommé par une tâche affectée à cette ressource. */
    public function role(): string
    {
        return $this->is_labor ? TaskResources::ROLE_LABOR : TaskResources::ROLE_MACHINE;
    }

    public function section()
    {
        return $this->belongsTo(MethodsSection::class, 'section_id');
    }

    public function locations()
    {
        return $this->hasMany(MethodsLocation::class, 'ressource_id');
    }

    /**
     * Déclarations de temps imputées à la ressource. Contrairement aux tâches
     * affectées, c'est du réalisé figé : il ne bouge plus si la tâche change de
     * ressource ensuite.
     */
    public function taskActivities()
    {
        return $this->hasMany(TaskActivities::class, 'methods_ressources_id');
    }

    /**
     * Heures réellement déclarées sur la ressource, en appariant chaque top
     * départ avec son top fin (ou la fin de la fenêtre s'il court encore).
     *
     * L'appariement est fait par tâche : une même ressource peut porter
     * plusieurs tâches ouvertes en parallèle. Calculé en PHP plutôt qu'en SQL
     * pour rester indépendant du SGBD — le calcul existant côté tâche repose sur
     * TIMESTAMPDIFF, donc MySQL uniquement.
     */
    public function declaredHours(?Carbon $from = null, ?Carbon $to = null): float
    {
        $to = $to ? $to->copy() : Carbon::now();

        $activities = $this->taskActivities()
            ->whereIn('type', [
                TaskActivities::TYPE_START,
                TaskActivities::TYPE_END,
                TaskActivities::TYPE_FINISH,
            ])
            ->where('timestamp', '<=', $to)
            ->orderBy('timestamp')
            ->get(['task_id', 'type', 'timestamp']);

        $seconds = 0.0;
        $openedAt = [];

        foreach ($activities as $activity) {
            $stamp = Carbon::parse($activity->timestamp);

            if ((int) $activity->type === TaskActivities::TYPE_START) {
                $openedAt[$activity->task_id] = $stamp;
                continue;
            }

            if (! isset($openedAt[$activity->task_id])) {
                continue;
            }

            $seconds += $this->overlapSeconds($openedAt[$activity->task_id], $stamp, $from, $to);
            unset($openedAt[$activity->task_id]);
        }

        // Tâches encore en cours à la fin de la fenêtre.
        foreach ($openedAt as $start) {
            $seconds += $this->overlapSeconds($start, $to, $from, $to);
        }

        return round($seconds / 3600, 2);
    }

    /** Recouvrement, en secondes, entre un intervalle déclaré et la fenêtre demandée. */
    private function overlapSeconds(Carbon $start, Carbon $end, ?Carbon $from, Carbon $to): float
    {
        $start = $from && $start->lessThan($from) ? $from : $start;
        $end   = $end->greaterThan($to) ? $to : $end;

        return $end->greaterThan($start) ? (float) $start->diffInSeconds($end) : 0.0;
    }

    /** Number of working days per week used to convert weekly capacity to daily. */
    public const WORKING_DAYS_PER_WEEK = 5;

    /**
     * Régime horaire propre à la ressource (1×8, 2×8, 3×8...). Null = celui de
     * l'atelier, et à défaut les horaires historiques.
     */
    public function shiftPattern()
    {
        return $this->belongsTo(WorkShiftPattern::class, 'work_shift_pattern_id');
    }

    public function effectiveShiftPattern(): ?WorkShiftPattern
    {
        $pattern = $this->shiftPattern ?: WorkShiftPattern::defaultPattern();

        // Un régime sans aucune plage n'est pas un régime « jamais ouvert » mais un
        // régime pas encore renseigné : on retombe sur la capacité hebdomadaire au
        // lieu de faire disparaître la ressource du planning sans explication.
        return $pattern && $pattern->slots->isNotEmpty() ? $pattern : null;
    }

    /**
     * Capacité du jour, en heures.
     *
     * Avec un régime horaire, ce sont les heures réellement ouvertes ce jour-là :
     * 0 le dimanche, 24 en 3×8, 16 en 2×8. Sans régime, on retombe sur le
     * comportement historique — capacité hebdomadaire lissée sur 5 jours — donc
     * rien ne change tant qu'aucun horaire n'est déclaré.
     *
     * Sans date, renvoie la moyenne des jours ouvrés (affichage, taux de charge).
     */
    public function dailyCapacity(?Carbon $date = null): float
    {
        $pattern = $this->effectiveShiftPattern();

        if (! $pattern) {
            return $this->capacity / self::WORKING_DAYS_PER_WEEK;
        }

        if ($date) {
            return $pattern->hoursForDate($date);
        }

        $openDays = $pattern->slots->pluck('weekday')->unique()->count();

        return $openDays > 0 ? round($pattern->weeklyHours() / $openDays, 3) : 0.0;
    }

    /**
     * Calculate remaining available capacity for the given day.
     *
     * The capacity field is stored as weekly hours — divide by WORKING_DAYS_PER_WEEK
     * to get the daily budget, then subtract hours already assigned that day.
     *
     * On ne décompte que les tâches affectées dans le rôle de la ressource :
     * une machine ne consomme pas la capacité main-d'œuvre et inversement.
     */
    public function remainingCapacity(Carbon $date): float
    {
        $usedCapacity = $this->tasks()
            ->wherePivot('role', $this->role())
            ->whereDate('start_date', $date->toDateString())
            ->get()
            ->sum(fn (Task $task) => $task->TotalTime());

        return max(0, $this->dailyCapacity($date) - $usedCapacity);
    }

    /**
     * Get the formatted creation date of the line.
     *
     * This accessor method returns the creation date of line
     * formatted as 'day month year' (e.g., '01 January 2023').
     *
     * @return string The formatted creation date.
     */
    public function GetPrettyCreatedAttribute()
    {
        return date('d F Y', strtotime($this->created_at));
    }
}
