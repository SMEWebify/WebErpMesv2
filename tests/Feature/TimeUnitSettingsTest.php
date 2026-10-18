<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Support\TimeDisplay;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimeUnitSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);

        Permission::findOrCreate('your-company-menu');
        Role::findOrCreate('Admin');
        Role::findOrCreate('Operator');
    }

    private function admin(): User
    {
        $user = User::factory()->create()->assignRole('Admin');
        $user->givePermissionTo('your-company-menu');

        return $user;
    }

    public function test_un_operateur_ne_peut_pas_changer_l_unite(): void
    {
        $operator = User::factory()->create()->assignRole('Operator');

        $this->actingAs($operator)
            ->put(route('admin.time.unit.settings.update'), ['time_unit' => 'minutes'])
            ->assertForbidden();

        $this->assertSame(TimeDisplay::HOURS, TimeDisplay::unit());
    }

    public function test_l_admin_bascule_en_minutes_et_la_page_le_publie(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.time.unit.settings.update'), ['time_unit' => 'minutes'])
            ->assertRedirect(route('admin.time.unit.settings'));

        $this->assertSame('minutes', app(SettingsService::class)->get(TimeDisplay::SETTING_KEY));
        $this->actingAs($admin)
            ->get(route('admin.time.unit.settings'))
            ->assertOk()
            ->assertSee('<meta name="time-unit" content="minutes">', false);
    }

    public function test_par_defaut_la_page_publie_les_heures(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.time.unit.settings'))
            ->assertOk()
            ->assertSee('<meta name="time-unit" content="hours">', false);
    }

    public function test_une_valeur_inconnue_est_refusee(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.time.unit.settings.update'), ['time_unit' => 'secondes'])
            ->assertSessionHasErrors('time_unit');
    }
}
