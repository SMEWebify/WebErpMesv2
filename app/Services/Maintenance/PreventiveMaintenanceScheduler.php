<?php

namespace App\Services\Maintenance;

use App\Models\Maintenance\MaintenancePlan;
use App\Models\Maintenance\WorkOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Échéances des plans de maintenance et création des ordres de travail préventifs.
 *
 * - « Date fixe » : une seule échéance, la date du plan.
 * - « Périodique » : tous les interval_days jours, calendrier fixe : l'échéance suivante
 *   part de la date planifiée du dernier ordre du plan (pas de sa date de clôture, pour
 *   ne pas dériver), la première de fixed_date, sinon de la création du plan + intervalle.
 * - Heures machine / cycles : aucun compteur n'est relevé, donc rien d'automatique.
 *
 * Un plan ne prépare un ordre que s'il n'en a aucun encore ouvert : un ordre en retard
 * n'en empile pas d'autres derrière lui.
 */
class PreventiveMaintenanceScheduler
{
    public function nextDueDate(MaintenancePlan $plan): ?Carbon
    {
        if ($plan->trigger_type === 'fixed_date') {
            if (! $plan->fixed_date || $plan->workOrders()->exists()) {
                return null;
            }

            return $plan->fixed_date->copy()->startOfDay();
        }

        if ($plan->trigger_type !== 'time' || ! $plan->interval_days) {
            return null;
        }

        $last = $plan->workOrders()
            ->orderByRaw('COALESCE(scheduled_at, requested_at) DESC')
            ->first();

        if ($last) {
            $anchor = $last->scheduled_at ?? $last->requested_at;

            return Carbon::parse($anchor)->startOfDay()->addDays($plan->interval_days);
        }

        return $plan->fixed_date
            ? $plan->fixed_date->copy()->startOfDay()
            : Carbon::parse($plan->created_at)->startOfDay()->addDays($plan->interval_days);
    }

    /**
     * Ordre à créer aujourd'hui pour ce plan, ou null : génération désactivée, échéance
     * encore trop lointaine (au-delà du délai d'anticipation) ou ordre déjà ouvert.
     */
    public function dueFor(MaintenancePlan $plan, Carbon $today): ?Carbon
    {
        if (! $plan->auto_generate || ! in_array($plan->trigger_type, MaintenancePlan::SCHEDULABLE_TRIGGERS, true)) {
            return null;
        }

        $due = $this->nextDueDate($plan);
        if (! $due || $due->copy()->subDays((int) $plan->lead_days)->gt($today->copy()->startOfDay())) {
            return null;
        }

        $hasOpenOrder = $plan->workOrders()
            ->whereNotIn('status', WorkOrder::CLOSED_STATUSES)
            ->exists();

        return $hasOpenOrder ? null : $due;
    }

    /**
     * Crée les ordres dus. Renvoie les ordres créés (ou, en simulation, les plans
     * concernés avec leur échéance, sans rien écrire).
     *
     * @return Collection<int, array{plan: MaintenancePlan, due: Carbon, work_order: ?WorkOrder}>
     */
    public function generateDue(?Carbon $today = null, bool $dryRun = false): Collection
    {
        $today ??= Carbon::today();

        return MaintenancePlan::query()
            ->where('auto_generate', true)
            ->whereIn('trigger_type', MaintenancePlan::SCHEDULABLE_TRIGGERS)
            ->orderBy('id')
            ->get()
            ->map(function (MaintenancePlan $plan) use ($today, $dryRun) {
                $due = $this->dueFor($plan, $today);
                if (! $due) {
                    return null;
                }

                $workOrder = $dryRun ? null : $this->createWorkOrder($plan, $due, null);

                return ['plan' => $plan, 'due' => $due, 'work_order' => $workOrder];
            })
            ->filter()
            ->values();
    }

    /** Ordre de travail préventif tiré du plan. $createdBy null = tâche planifiée. */
    public function createWorkOrder(MaintenancePlan $plan, ?Carbon $scheduledAt, ?int $createdBy): WorkOrder
    {
        return WorkOrder::create([
            'asset_id' => $plan->asset_id,
            'maintenance_plan_id' => $plan->id,
            'title' => $plan->title,
            'description' => $plan->description,
            'actions_performed' => $plan->actions,
            'parts_consumed' => $plan->required_parts,
            'priority' => 'medium',
            'work_type' => 'preventive',
            'status' => 'planned',
            'requested_at' => now()->toDateString(),
            'scheduled_at' => $scheduledAt?->toDateString(),
            'estimated_duration_minutes' => $plan->estimated_duration_minutes,
            'created_by' => $createdBy,
        ]);
    }
}
