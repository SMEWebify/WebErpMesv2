<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use App\Models\Spreadsheet;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SpreadsheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_spreadsheets(): void
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        Spreadsheet::create([
            'name' => 'Pilotage',
            'description' => 'Tableur de test',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('spreadsheet.index'));

        $response->assertStatus(200);
    }

    public function test_user_can_create_spreadsheet(): void
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('spreadsheet.store'), [
            'name' => 'Suivi production',
            'description' => 'Description',
        ]);

        $this->assertDatabaseHas('spreadsheets', [
            'name' => 'Suivi production',
            'created_by' => $user->id,
        ]);

        $spreadsheet = Spreadsheet::first();
        $response->assertRedirect(route('spreadsheet.edit', $spreadsheet));
    }

    public function test_user_can_save_spreadsheet_data(): void
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $spreadsheet = Spreadsheet::create([
            'name' => 'KPI',
            'created_by' => $user->id,
        ]);

        $sheet = $spreadsheet->sheets()->create([
            'name' => 'Sheet1',
            'order' => 0,
            'data' => null,
        ]);

        $response = $this->actingAs($user)->postJson(route('spreadsheet.save', $spreadsheet), [
            'sheets' => [
                [
                    'id' => $sheet->id,
                    'name' => 'Sheet1',
                    'data' => ['cellData' => ['0' => ['0' => ['v' => 10]]]],
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_edit_page_displays_formula_help_modal(): void
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();
        $spreadsheet = Spreadsheet::create([
            'name' => 'Aide formules',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('spreadsheet.edit', $spreadsheet));

        $response->assertStatus(200);
        $response->assertSee('Aide formules');
        $response->assertSee('WEM.STOCK(reference)');
        $response->assertSee('WEM.COMMANDES_EN_COURS()');
    }

    public function test_stock_data_endpoint_returns_json(): void
    {
        $this->withoutMiddleware();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/spreadsheet/data/stock/TEST');

        $response->assertStatus(200)->assertJsonStructure([
            'reference',
            'quantity',
            'unit',
            'updated_at',
        ]);
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->get(route('spreadsheet.index'));

        $response->assertStatus(302);
        $this->assertStringContainsString('/login', $response->headers->get('Location'));
    }
}
