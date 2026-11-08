<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

/**
 * Purge d'activity_log par journal (ISO 19443), à la place de `activitylog:clean` du
 * paquet qui supprime tout au-delà d'une seule durée. Durées dans
 * config('activitylog.retention') : un journal à durée null n'est jamais purgé.
 */
class CleanActivityLog extends Command
{
    protected $signature = 'wem:activitylog:clean {--dry-run : Compte les entrées à purger sans rien supprimer}';

    protected $description = "Purge le journal d'activité selon la durée de conservation de chaque log_name";

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $named = (array) config('activitylog.retention.log_names', []);
        $total = 0;

        foreach ($named as $logName => $days) {
            $total += $this->purge(
                (string) $logName,
                $this->days($days),
                fn (Builder $q) => $q->where('log_name', $logName),
                $dryRun,
            );
        }

        // Tous les autres journaux (modifications, visas, RGPD), log_name nul compris.
        $total += $this->purge(
            'default',
            $this->days(config('activitylog.retention.default')),
            fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereNull('log_name')
                ->orWhereNotIn('log_name', array_map('strval', array_keys($named)))),
            $dryRun,
        );

        $this->info($dryRun
            ? "Simulation : {$total} entrée(s) seraient purgées."
            : "Journal d'activité nettoyé : {$total} entrée(s) purgées.");

        return self::SUCCESS;
    }

    private function purge(string $label, ?int $days, \Closure $scope, bool $dryRun): int
    {
        if ($days === null) {
            $this->line("{$label} : conservé sans limite.");

            return 0;
        }

        $query = Activity::query()->where('created_at', '<', now()->subDays($days));
        $scope($query);

        $count = $dryRun ? $query->count() : $query->delete();
        $this->line("{$label} (> {$days} j) : {$count}");

        return $count;
    }

    /** null, vide ou négatif = jamais purgé. */
    private function days(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value) || (int) $value < 0) {
            return null;
        }

        return (int) $value;
    }
}
