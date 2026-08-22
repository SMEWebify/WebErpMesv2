<?php

namespace Tests\Feature\HumanResources;

use App\Models\HumanResources\LeaveBalance;
use App\Models\HumanResources\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeaveControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        config()->set('hr.leave_period_start_month', 6);
        config()->set('hr.leave_period_start_day', 1);

        Permission::findOrCreate('human-resources-menu');

        $this->hr = User::factory()->create();
        $this->hr->givePermissionTo('human-resources-menu');
    }

    /** @test */
    public function it_displays_the_leave_balances_page()
    {
        LeaveType::create(['code' => 'CP', 'label' => 'Congés payés', 'counts_against_balance' => true, 'default_annual_quota' => 25]);
        User::factory(3)->create();

        $this->actingAs($this->hr)
            ->get(route('human.resources.leave.balances'))
            ->assertOk()
            ->assertViewIs('admin.human-resources-leave-balances')
            ->assertViewHas('Rows');
    }

    /** @test */
    public function a_user_without_the_hr_permission_cannot_reach_the_balances()
    {
        $employee = User::factory()->create();

        $this->actingAs($employee)
            ->get(route('human.resources.leave.balances'))
            ->assertForbidden();
    }

    /** @test */
    public function it_upserts_an_entitlement_instead_of_stacking_duplicates()
    {
        $type = LeaveType::create(['code' => 'CP', 'label' => 'Congés payés', 'counts_against_balance' => true]);
        $employee = User::factory()->create();

        $payload = [
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
            'period_start' => '2026-08-16',
            'entitled_days' => 25,
            'carried_over_days' => 3,
        ];

        $this->actingAs($this->hr)->post(route('human.resources.leave.balance.store'), $payload)->assertRedirect();
        $this->actingAs($this->hr)->post(route('human.resources.leave.balance.store'), array_merge($payload, ['entitled_days' => 27]))->assertRedirect();

        $this->assertSame(1, LeaveBalance::where('user_id', $employee->id)->count());

        $balance = LeaveBalance::where('user_id', $employee->id)->first();

        // The date submitted is normalised to the start of the reference period.
        $this->assertSame('2026-06-01', $balance->period_start->toDateString());
        $this->assertEquals(27.0, (float) $balance->entitled_days);
        $this->assertEquals(30.0, $balance->acquired_days);
    }

    /** @test */
    public function it_seeds_missing_entitlements_from_the_default_quota()
    {
        LeaveType::create(['code' => 'CP', 'label' => 'Congés payés', 'counts_against_balance' => true, 'default_annual_quota' => 25]);
        LeaveType::create(['code' => 'MAL', 'label' => 'Arrêt maladie', 'counts_against_balance' => false, 'default_annual_quota' => 0]);

        User::factory(2)->create();

        $this->actingAs($this->hr)
            ->post(route('human.resources.leave.balance.generate'), ['period_start' => '2026-08-16'])
            ->assertRedirect();

        // Three employees (the two created here plus the HR user), one counted
        // type: the sick leave type never gets an entitlement row.
        $this->assertSame(3, LeaveBalance::count());
        $this->assertEquals(25.0, (float) LeaveBalance::first()->entitled_days);
    }
}
