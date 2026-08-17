<?php

namespace Tests\Feature;

use App\Models\Assets\Asset;
use App\Models\Maintenance\WorkOrder;
use App\Models\Methods\MethodsRessources;
use App\Models\Times\TimesAbsence;
use App\Models\Times\TimesBanckHoliday;
use App\Models\Times\WorkShiftPattern;
use App\Models\User;
use App\Services\ResourceCapacityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Capacité disponible = heures ouvertes par le régime horaire, moins les
 * indisponibilités datées déjà présentes dans l'ERP mais jamais consommées
 * par la planification : fériés, arrêts machine, absences validées.
 */
class ResourceCapacityServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResourceCapacityService $service;

    /** Lundi. */
    private const MONDAY = '2026-08-10';

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ResourceCapacityService::class);
    }

    /** Une plage 06h00-14h00 du lundi au vendredi. */
    private function dayShiftPattern(): WorkShiftPattern
    {
        $pattern = WorkShiftPattern::create(['code' => '1X8', 'label' => '1x8']);

        foreach ([1, 2, 3, 4, 5] as $weekday) {
            $pattern->slots()->create([
                'weekday' => $weekday,
                'start_time' => '06:00:00',
                'end_time' => '14:00:00',
            ]);
        }

        return $pattern;
    }

    private function machine(): MethodsRessources
    {
        return MethodsRessources::factory()->create([
            'capacity' => 40,
            'is_labor' => false,
            'work_shift_pattern_id' => $this->dayShiftPattern()->id,
        ]);
    }

    public function test_a_bank_holiday_closes_the_day()
    {
        $machine = $this->machine();

        $this->assertSame(8.0, $this->service->availableHours($machine, Carbon::parse(self::MONDAY)));

        TimesBanckHoliday::create(['fixed' => false, 'date' => self::MONDAY, 'label' => 'Pont']);

        $this->assertSame(0.0, $this->service->availableHours($machine, Carbon::parse(self::MONDAY)));
    }

    /** Un arrêt machine déclaré en GMAO retire ses heures de la capacité du jour. */
    public function test_a_machine_stopping_work_order_reduces_the_available_hours()
    {
        $machine = $this->machine();

        $asset = Asset::create([
            'name' => 'Laser',
            'methods_ressource_id' => $machine->id,
            'acquisition_value' => 1000,
            'acquisition_date' => '2020-01-01',
            'depreciation_duration' => 5,
        ]);

        WorkOrder::create([
            'asset_id' => $asset->id,
            'title' => 'Revision',
            'status' => 'planned',
            'requested_at' => self::MONDAY,
            'machine_stopped' => true,
            'started_at' => self::MONDAY . ' 08:00:00',
            'finished_at' => self::MONDAY . ' 11:00:00',
        ]);

        // 8 h ouvertes - 3 h d'arrêt.
        $this->assertSame(5.0, $this->service->availableHours($machine->fresh(), Carbon::parse(self::MONDAY)));
    }

    /** Un arrêt plus long que la journée ne retire jamais plus que ce qui était ouvert. */
    public function test_downtime_is_capped_by_the_open_hours()
    {
        $machine = $this->machine();

        $asset = Asset::create([
            'name' => 'Laser',
            'methods_ressource_id' => $machine->id,
            'acquisition_value' => 1000,
            'acquisition_date' => '2020-01-01',
            'depreciation_duration' => 5,
        ]);

        WorkOrder::create([
            'asset_id' => $asset->id,
            'title' => 'Panne majeure',
            'status' => 'in_progress',
            'requested_at' => self::MONDAY,
            'machine_stopped' => true,
            'started_at' => self::MONDAY . ' 00:00:00',
            'finished_at' => '2026-08-12 00:00:00',
        ]);

        $this->assertSame(0.0, $this->service->availableHours($machine->fresh(), Carbon::parse(self::MONDAY)));
    }

    /** La capacité d'un poste manuel baisse au prorata des absents validés. */
    public function test_validated_absences_reduce_a_labor_resource_proportionally()
    {
        $team = MethodsRessources::factory()->create([
            'capacity' => 40,
            'is_labor' => true,
            'work_shift_pattern_id' => $this->dayShiftPattern()->id,
        ]);

        $operators = User::factory()->count(4)->create();
        $team->users()->sync($operators->pluck('id'));

        TimesAbsence::create([
            'user_id' => $operators->first()->id,
            'absence_type' => 1,
            'absence_type_day' => 3,
            'statu' => ResourceCapacityService::ABSENCE_VALIDATED,
            'start_date' => self::MONDAY,
            'end_date' => self::MONDAY,
        ]);

        // Un absent sur quatre : 8 h - 25 %.
        $this->assertSame(6.0, $this->service->availableHours($team->fresh(), Carbon::parse(self::MONDAY)));
    }

    /** Une demande non validée ne retire rien : elle peut encore être refusée. */
    public function test_a_pending_absence_request_does_not_reduce_capacity()
    {
        $team = MethodsRessources::factory()->create([
            'capacity' => 40,
            'is_labor' => true,
            'work_shift_pattern_id' => $this->dayShiftPattern()->id,
        ]);

        $operator = User::factory()->create();
        $team->users()->sync([$operator->id]);

        TimesAbsence::create([
            'user_id' => $operator->id,
            'absence_type' => 1,
            'absence_type_day' => 3,
            'statu' => 1, // à valider
            'start_date' => self::MONDAY,
            'end_date' => self::MONDAY,
        ]);

        $this->assertSame(8.0, $this->service->availableHours($team->fresh(), Carbon::parse(self::MONDAY)));
    }

    /** 20 h sur une ressource en 1×8 occupent trois jours, week-end sauté. */
    public function test_hours_are_spread_over_the_following_open_days()
    {
        $machine = $this->machine();

        $residual = fn (string $day) => $this->service->availableHours($machine, Carbon::parse($day));

        $plan = $this->service->spreadHours(20, Carbon::parse('2026-08-14'), $residual);

        $this->assertSame([
            '2026-08-14' => 8.0,  // vendredi
            '2026-08-17' => 8.0,  // lundi — samedi et dimanche fermés
            '2026-08-18' => 4.0,
        ], $plan);
    }

    /** Une charge qui ne rentre pas dans l'horizon n'est pas planifiée. */
    public function test_spreading_gives_up_beyond_the_horizon()
    {
        $this->assertNull(
            $this->service->spreadHours(100, Carbon::parse(self::MONDAY), fn () => 0.0)
        );
    }
}
