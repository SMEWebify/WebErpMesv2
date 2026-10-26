<?php

namespace Tests\Feature\OSH;

use App\Models\Methods\MethodsSection;
use App\Models\OSH\OSHRisque;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La section et la description d'un risque n'étaient pas validées : écartées par
 * validated(), la création échouait (colonnes NOT NULL) et la modification les ignorait.
 */
class RiskFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);
    }

    /** @test */
    public function a_risk_is_created_then_updated_with_its_section_and_description()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $bending = MethodsSection::create(['ordre' => 1, 'code' => 'BEND', 'label' => 'Pliage', 'user_id' => $user->id, 'color' => '#000000']);
        $laser = MethodsSection::create(['ordre' => 2, 'code' => 'LASER', 'label' => 'Laser', 'user_id' => $user->id, 'color' => '#000000']);

        $this->post(route('osh.risks.create'), [
            'section_id' => $bending->id, 'description' => 'Écrasement des mains — plieuse',
            'severity' => 3, 'probability' => 2, 'user_id' => $user->id,
        ])->assertRedirect(route('osh.risks'));

        $risk = OSHRisque::firstOrFail();
        $this->assertSame('Écrasement des mains — plieuse', $risk->description);

        $this->post(route('osh.risks.update', $risk->id), [
            'section_id' => $laser->id, 'description' => 'Rayonnement laser',
            'severity' => 2, 'probability' => 1, 'user_id' => $user->id,
        ])->assertRedirect(route('osh.risks'));

        $this->assertDatabaseHas('osh_risques', ['id' => $risk->id, 'section_id' => $laser->id, 'description' => 'Rayonnement laser']);
    }
}
