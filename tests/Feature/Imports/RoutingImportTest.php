<?php

namespace Tests\Feature\Imports;

use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\QuoteLines;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Import de gamme / nomenclature sur une ligne (écran de gamme) : opérations
 * sans composant, CSV ou XLSX, rejets listés avec leur numéro de ligne.
 */
class RoutingImportTest extends TestCase
{
    use ImportTestFiles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->actingAs(User::factory()->create());
        MethodsFamilies::factory()->create();
        \App\Models\Planning\Status::create(['title' => 'Open', 'order' => 1]);
    }

    private function import(QuoteLines $line, $file)
    {
        return $this->post(route('task.manage.json.import-csv', ['id_type' => 'quote_lines_id', 'id_page' => $line->quotes_id, 'id_line' => $line->id]), ['csv_file' => $file], ['Accept' => 'application/json']);
    }

    #[DataProvider('formats')]
    public function test_a_routing_without_components_is_imported(string $format): void
    {
        foreach (['LAS' => 'Découpe laser', 'PLI' => 'Pliage', 'SOU' => 'Soudure'] as $code => $label) {
            MethodsServices::factory()->create(['code' => $code, 'label' => $label, 'type' => 1]);
        }
        $matiere = MethodsServices::factory()->create(['code' => 'MAT', 'type' => 3]);
        $tole = Products::factory()->create(['code' => 'TOLE-304-2']);
        $line = QuoteLines::factory()->create();

        $file = $this->{$format}([
            ['ordre', 'service_code', 'label', 'component_code', 'unit_code', 'qty', 'unit_cost', 'unit_price', 'unit_time'],
            [10, 'LAS', 'Découpe laser', null, null, 1, '12,5', 20, '0,15'],
            [20, 'PLI', 'Pliage 4 plis', null, null, null, null, null, 0.2],
            [30, 'SOU', 'Soudure TIG', null, null, 1, 30, 45, 0.5],
            [40, 'MAT', 'Tôle inox', 'TOLE-304-2', null, 2, 8, 10, null],
            [50, 'XXX', 'Poste inconnu', null, null, 1, 0, 0, null],
            [60, 'PLI', 'Article inconnu', 'NOPE', null, 1, 0, 0, null],
        ]);

        $data = $this->import($line, $file)->assertOk()->json();

        $this->assertSame(4, $data['success_count']);
        $this->assertSame([
            __('imports.reject.line', ['line' => 6, 'reason' => __('imports.reject.service_unknown', ['code' => 'XXX'])]),
            __('imports.reject.line', ['line' => 7, 'reason' => __('imports.reject.product_unknown', ['code' => 'NOPE'])]),
        ], $data['errors']);

        $laser = Task::where('quote_lines_id', $line->id)->where('ordre', 10)->sole();
        $this->assertNull($laser->component_id);
        $this->assertSame('Découpe laser', $laser->label);
        $this->assertSame('LAS', $laser->code);
        $this->assertEquals(12.5, (float) $laser->unit_cost);
        $this->assertEquals(0.15, (float) $laser->unit_time);

        $pliage = Task::where('quote_lines_id', $line->id)->where('ordre', 20)->sole();
        $this->assertEquals(1, (float) $pliage->qty);
        $this->assertEquals(0, (float) $pliage->unit_price);

        $this->assertSame($tole->id, Task::where('quote_lines_id', $line->id)->where('ordre', 40)->value('component_id'));
        $this->assertCount(3, $data['technical_cut']);
        $this->assertCount(1, $data['bom']);
    }

    public function test_a_line_with_the_wrong_number_of_columns_is_reported(): void
    {
        MethodsServices::factory()->create(['code' => 'LAS', 'type' => 1]);
        $line = QuoteLines::factory()->create();

        $file = $this->uploaded("ordre,service_code,label,qty,unit_cost,unit_price\n10,LAS,Découpe,1,0,0\n20,LAS,Trop court\n", 'gamme.csv');

        $data = $this->import($line, $file)->assertOk()->json();

        $this->assertSame(1, $data['success_count']);
        $this->assertSame([__('imports.reject.line', ['line' => 3, 'reason' => __('imports.reject.columns', ['found' => 3, 'expected' => 6])])], $data['errors']);
    }

    public function test_the_former_csv_format_still_imports(): void
    {
        // Format d'avant : virgule, en-tête exact, composant et unité renseignés.
        MethodsServices::factory()->create(['code' => 'MAT', 'type' => 3]);
        Products::factory()->create(['code' => 'VIS-M6']);
        $unit = MethodsUnits::factory()->create(['code' => 'U']);
        $line = QuoteLines::factory()->create();

        $file = $this->uploaded("ordre,service_code,label,component_code,unit_code,qty,unit_cost,unit_price\n1,MAT,Vis M6,VIS-M6,U,4,0.1,0.2\n", 'bom.csv');

        $data = $this->import($line, $file)->assertOk()->json();

        $this->assertSame(1, $data['success_count']);
        $this->assertSame([], $data['errors']);
        $this->assertSame($unit->id, Task::where('quote_lines_id', $line->id)->value('methods_units_id'));
    }
}
