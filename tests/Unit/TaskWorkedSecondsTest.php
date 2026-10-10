<?php

namespace Tests\Unit;

use Tests\TestCase;
use Carbon\Carbon;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;

/**
 * Couvre Task::workedSecondsFrom(), méthode pure (aucune base requise).
 * Les activités sont de simples objets {type, timestamp, user_id, methods_ressources_id},
 * tels que les livre le get() de computeWorkedSeconds() / AffairSummaryService.
 */
class TaskWorkedSecondsTest extends TestCase
{
    /** Fabrique une activité brute, comme une ligne de task_activities. */
    private function activity(int $type, string $timestamp, ?int $user = 1, ?int $resource = 1): object
    {
        return (object) [
            'type'                  => $type,
            'timestamp'             => $timestamp,
            'user_id'               => $user,
            'methods_ressources_id' => $resource,
        ];
    }

    public function test_session_simple_compte_la_duree(): void
    {
        $seconds = Task::workedSecondsFrom([
            $this->activity(TaskActivities::TYPE_START, '2026-01-05 08:00:00'),
            $this->activity(TaskActivities::TYPE_END,   '2026-01-05 10:00:00'),
        ]);

        $this->assertSame(2 * 3600, $seconds);
    }

    public function test_deux_sessions_sequentielles_s_additionnent(): void
    {
        $seconds = Task::workedSecondsFrom([
            $this->activity(TaskActivities::TYPE_START, '2026-01-05 08:00:00'),
            $this->activity(TaskActivities::TYPE_END,   '2026-01-05 09:00:00'),
            $this->activity(TaskActivities::TYPE_START, '2026-01-05 13:00:00'),
            $this->activity(TaskActivities::TYPE_FINISH, '2026-01-05 15:00:00'),
        ]);

        $this->assertSame(3 * 3600, $seconds);
    }

    public function test_deux_operateurs_simultanes_sont_comptes_chacun(): void
    {
        // Régression A2 : deux opérateurs sur la même tâche en parallèle.
        // L'ancienne boucle n'ouvrait qu'une session et perdait la seconde.
        $seconds = Task::workedSecondsFrom([
            $this->activity(TaskActivities::TYPE_START, '2026-01-05 08:00:00', user: 1, resource: 10),
            $this->activity(TaskActivities::TYPE_START, '2026-01-05 08:30:00', user: 2, resource: 20),
            $this->activity(TaskActivities::TYPE_END,   '2026-01-05 10:00:00', user: 1, resource: 10),
            $this->activity(TaskActivities::TYPE_END,   '2026-01-05 11:00:00', user: 2, resource: 20),
        ]);

        // OP1 : 2 h, OP2 : 2 h 30 → 4 h 30.
        $this->assertSame((int) (4.5 * 3600), $seconds);
    }

    public function test_double_demarrer_du_meme_couple_n_ouvre_pas_de_seconde_session(): void
    {
        // Remarque de Kevin : un second « Démarrer » du même opérateur/ressource
        // ne doit pas créer de session fantôme. Ici le 2e START est ignoré, la
        // FIN referme l'unique session : 2 h, et aucune session ne reste ouverte.
        $seconds = Task::workedSecondsFrom([
            $this->activity(TaskActivities::TYPE_START, '2026-01-05 08:00:00', user: 1, resource: 10),
            $this->activity(TaskActivities::TYPE_START, '2026-01-05 08:15:00', user: 1, resource: 10),
            $this->activity(TaskActivities::TYPE_END,   '2026-01-05 10:00:00', user: 1, resource: 10),
        ]);

        $this->assertSame(2 * 3600, $seconds);
    }

    public function test_fin_orpheline_referme_la_session_la_plus_ancienne(): void
    {
        // FIN dont le couple ne correspond à aucun START ouvert : on referme la
        // plus ancienne session ouverte plutôt que de perdre la FIN.
        $seconds = Task::workedSecondsFrom([
            $this->activity(TaskActivities::TYPE_START, '2026-01-05 08:00:00', user: 1, resource: 10),
            $this->activity(TaskActivities::TYPE_END,   '2026-01-05 09:00:00', user: 99, resource: 99),
        ]);

        $this->assertSame(1 * 3600, $seconds);
    }

    public function test_session_ouverte_comptee_jusqu_a_maintenant(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-05 10:00:00'));

        $seconds = Task::workedSecondsFrom([
            $this->activity(TaskActivities::TYPE_START, '2026-01-05 09:00:00'),
        ]);

        Carbon::setTestNow();

        $this->assertSame(1 * 3600, $seconds);
    }
}
