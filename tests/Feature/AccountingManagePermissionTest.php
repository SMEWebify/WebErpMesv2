<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Les écrans de matrice de TVA et de périodes exigent accounting-manage, que rien ne
 * créait : seule une base où la permission avait été ajoutée à la main y avait accès.
 */
class AccountingManagePermissionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function the_permission_exists_and_opens_the_periods_screen()
    {
        $this->assertTrue(Permission::where('name', 'accounting-manage')->exists());

        $this->withoutMiddleware([\App\Http\Middleware\CheckUserRole::class, \App\Http\Middleware\CheckFactory::class]);
        $user = User::factory()->create();
        $user->givePermissionTo('accounting-manage');

        $this->actingAs($user)->get(route('accounting.periods.ui'))->assertOk();
    }
}
