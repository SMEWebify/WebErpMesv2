<?php

namespace Tests\Feature\Products;

use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\Products\StockLocation;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Models\User;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * API des chutes : une FAO tierce lit les chutes en stock (format, lot, valeur)
 * pour imbriquer dessus. Lecture seule.
 */
class RemnantApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Products $sheet;
    private StockLocationProducts $slp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);

        $this->user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'stock-lot-serial-management', 'guard_name' => 'web']);
        $this->user->givePermissionTo('stock-lot-serial-management');

        StockLocation::query()->exists() || StockLocation::factory()->create();
        $service = MethodsServices::factory()->create(['type' => 3]);
        $family = MethodsFamilies::factory()->create(['methods_services_id' => $service->id, 'nest_type' => 'sheet']);
        MethodsUnits::factory()->create(['code' => 'PC']);

        $this->sheet = Products::factory()->create([
            'code' => 'TOLE-S235-3', 'methods_families_id' => $family->id,
            'material' => 'S235', 'thickness' => 3, 'x_size' => 1500, 'y_size' => 3000, 'z_size' => 3,
        ]);
        $this->slp = StockLocationProducts::factory()->create(['products_id' => $this->sheet->id]);
        $this->slp->update(['unit_cost' => 90]);

        // 3 tôles entières, 1 sortie avec une chute 1500 × 1000 et une 600 × 400 remises en stock.
        $this->move(3, 1500, 3000, 3);
        $exit = $this->move(1, 1500, 3000, 2);
        StockMove::create([
            'user_id' => $this->user->id, 'qty' => 1, 'stock_location_products_id' => $this->slp->id, 'typ_move' => 5,
            'x_size' => 1500, 'y_size' => 1000, 'z_size' => 3, 'component_price' => 30,
        ])->forceFill(['remnant_source_move_id' => $exit->id])->save();
        StockMove::create([
            'user_id' => $this->user->id, 'qty' => 1, 'stock_location_products_id' => $this->slp->id, 'typ_move' => 5,
            'x_size' => 600, 'y_size' => 400, 'z_size' => 3, 'component_price' => 4.8,
        ])->forceFill(['remnant_source_move_id' => $exit->id])->save();
    }

    private function move(float $qty, float $x, float $y, int $type): StockMove
    {
        return StockMove::create([
            'user_id' => $this->user->id, 'qty' => $qty, 'stock_location_products_id' => $this->slp->id, 'typ_move' => $type,
            'x_size' => $x, 'y_size' => $y, 'z_size' => 3, 'component_price' => 90,
        ]);
    }

    private function list(array $query = [], array $abilities = ['remnants:read'])
    {
        Sanctum::actingAs($this->user, $abilities);

        return $this->getJson(route('api.remnants.index', $query));
    }

    public function test_it_lists_the_offcuts_in_stock_with_their_value(): void
    {
        $response = $this->list()->assertOk()->assertJsonPath('meta.count', 2);

        $first = $response->json('data.0');
        $this->assertTrue($first['is_remnant']);
        $this->assertSame(['1500', '1000'], [(string) $first['x_size'], (string) $first['y_size']]); // la plus grande d'abord
        $this->assertSame(1, (int) $first['qty']);
        $this->assertSame('TOLE-S235-3', $first['product']['code']);
        $this->assertSame($this->slp->id, $first['stock_line']['id']);
        $this->assertSame(30.0, (float) $first['unit_value']); // 90 × 1,5 m² / 4,5 m²
    }

    public function test_full_sheets_are_listed_on_request(): void
    {
        $data = collect($this->list(['include_sheets' => 1])->assertOk()->json('data'));

        $sheet = $data->firstWhere('is_remnant', false);
        $this->assertNotNull($sheet);
        $this->assertSame(2, (int) $sheet['qty']);
        $this->assertSame(3, $data->count());
    }

    public function test_offcuts_are_filtered_by_minimum_size_in_either_direction(): void
    {
        $data = $this->list(['min_x' => 900, 'min_y' => 1400])->assertOk()->json('data');

        $this->assertCount(1, $data);
        $this->assertSame(1500.0, (float) $data[0]['x_size']);
    }

    public function test_offcuts_are_filtered_by_material_and_thickness(): void
    {
        $this->list(['material' => 'S235', 'thickness' => 3])->assertOk()->assertJsonPath('meta.count', 2);
        $this->list(['material' => 'INOX'])->assertOk()->assertJsonPath('meta.count', 0);
        $this->list(['thickness' => 5])->assertOk()->assertJsonPath('meta.count', 0);
    }

    public function test_the_offcut_drawing_is_listed_and_downloadable_with_the_token(): void
    {
        Storage::fake(config('files.disk'));
        $entry = StockMove::whereNotNull('remnant_source_move_id')->where('x_size', 1500)->sole();
        $storage = app(FileStorageService::class);
        $file = $storage->storeContents("0\nSECTION\n0\nEOF\n", 'chute-1.dxf', 'image/vnd.dxf');
        $storage->attach($file, $entry, FileRole::VECTOR);

        $first = $this->list()->assertOk()->json('data.0');
        $this->assertSame([['id' => $file->id, 'name' => 'chute-1.dxf']], $first['drawings']);
        $this->assertSame([], $this->list()->json('data.1.drawings'));

        $this->get(route('api.files.download', ['file' => $file->id]))->assertOk();
    }

    public function test_token_without_the_ability_or_user_without_the_permission_is_refused(): void
    {
        $this->list([], ['nests:write'])->assertForbidden();

        $this->user->permissions()->detach();
        $this->user->forgetCachedPermissions();
        $this->list()->assertForbidden();
    }
}
