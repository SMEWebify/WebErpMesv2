<?php

namespace Tests\Feature\Imports;

use App\Models\Companies\Companies;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\User;
use App\Services\ImportCsvService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Assistant d'import de l'écran Imports/Exports : dépôt et correspondance
 * proposée, aperçu simulé sans écriture, import, fichier gardé en privé.
 */
class ImportWizardTest extends TestCase
{
    use ImportTestFiles;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
            \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
        Storage::fake('local');
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        MethodsFamilies::factory()->create();
    }

    private function clients(): UploadedFile
    {
        return $this->xlsx([
            ['Code tiers', 'Raison sociale', 'N° TVA intra.', 'Site web', 'Colonne inutile'],
            ['C001', 'Tôlerie Martin', 'FR32512345678', 'www.martin.fr', 'x'],
            ['', 'Sans code', null, null, null],
            ['C002', 'Équerres & Cie', null, null, null],
        ], 'export-tiers.xlsx');
    }

    public function test_the_preview_suggests_the_mapping_from_the_headers(): void
    {
        $data = $this->post(route('admin.imports.preview', ['type' => 'companies']), ['file' => $this->clients()], ['Accept' => 'application/json'])
            ->assertOk()->json();

        $this->assertSame(['code' => 0, 'label' => 1, 'website' => 3, 'intra_community_vat' => 2], $data['mapping']);
        $this->assertSame(4, $data['total']);
        $this->assertSame('Raison sociale', $data['columns'][1]['header']);
        $this->assertSame('B', $data['columns'][1]['letter']);
        $this->assertSame(['line' => 2, 'cells' => ['C001', 'Tôlerie Martin', 'FR32512345678', 'www.martin.fr', 'x']], $data['rows'][1]);
        Storage::disk('local')->assertExists("imports/{$this->user->id}/{$data['token']}.xlsx");
    }

    public function test_the_check_simulates_without_writing_then_the_run_imports(): void
    {
        $token = $this->post(route('admin.imports.preview', ['type' => 'companies']), ['file' => $this->clients()], ['Accept' => 'application/json'])->json('token');
        $payload = ['token' => $token, 'filename' => 'export-tiers.xlsx', 'header' => true, 'mapping' => ['code' => 0, 'label' => 1, 'intra_community_vat' => 2]];

        $check = $this->postJson(route('admin.imports.check', ['type' => 'companies']), $payload)->assertOk()->json();
        $this->assertSame([2, 1], [$check['created'], $check['rejected']]);
        $this->assertSame('create', $check['lines']['2']['status']);
        $this->assertSame('reject', $check['lines']['3']['status']);
        $this->assertSame(3, $check['rejects'][0]['line']);
        $this->assertSame(0, Companies::whereIn('code', ['C001', 'C002'])->count(), 'la vérification n\'écrit rien');

        $run = $this->postJson(route('admin.imports.run', ['type' => 'companies']), $payload)->assertOk()->json();
        $this->assertSame(__('imports.result', ['created' => 2, 'rejected' => 1]), $run['message']);
        $this->assertSame('FR32512345678', Companies::where('code', 'C001')->value('intra_community_vat'));
        $this->assertSame('export-tiers.xlsx', Companies::where('code', 'C001')->value('csv_file_name'));
        Storage::disk('local')->assertMissing("imports/{$this->user->id}/{$token}.xlsx");

        // Le même fichier une seconde fois : les clients existent déjà, rien n'est doublé.
        $token = $this->post(route('admin.imports.preview', ['type' => 'companies']), ['file' => $this->clients()], ['Accept' => 'application/json'])->json('token');
        $again = $this->postJson(route('admin.imports.check', ['type' => 'companies']), ['token' => $token] + $payload)->json();
        $this->assertSame([0, 3], [$again['created'], $again['rejected']]);
        $this->assertSame(__('imports.reject.already_exists', ['code' => 'C001']), $again['lines']['2']['reason']);
    }

    public function test_a_required_field_without_column_is_refused(): void
    {
        $token = $this->post(route('admin.imports.preview', ['type' => 'companies']), ['file' => $this->clients()], ['Accept' => 'application/json'])->json('token');

        $this->postJson(route('admin.imports.check', ['type' => 'companies']), ['token' => $token, 'header' => true, 'mapping' => ['code' => 0]])
            ->assertStatus(422)
            ->assertJsonPath('message', __('imports.unmapped', ['fields' => __('general_content.name_company_trans_key')]));
    }

    public function test_the_file_of_another_user_cannot_be_used(): void
    {
        $token = $this->post(route('admin.imports.preview', ['type' => 'companies']), ['file' => $this->clients()], ['Accept' => 'application/json'])->json('token');

        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.imports.run', ['type' => 'companies']), ['token' => $token, 'header' => true, 'mapping' => ['code' => 0, 'label' => 1]])
            ->assertStatus(410);
        $this->assertSame(0, Companies::where('code', 'C001')->count());
    }

    public function test_products_need_the_catalogue_write_permission_and_report_updates(): void
    {
        $file = $this->csvUtf8Bom([['Référence', 'Désignation', 'Épaisseur (mm)'], ['EQ-1', 'Équerre', '2,5'], ['EQ-2', 'Platine', 'abc']]);

        $this->post(route('admin.imports.preview', ['type' => 'products']), ['file' => $file], ['Accept' => 'application/json'])->assertForbidden();

        Permission::findOrCreate('products-edit');
        $this->user->givePermissionTo('products-edit');
        Products::factory()->create(['code' => 'EQ-1']);

        $data = $this->post(route('admin.imports.preview', ['type' => 'products']), ['file' => $file], ['Accept' => 'application/json'])->assertOk()->json();
        $this->assertSame(['code' => 0, 'label' => 1, 'thickness' => 2], $data['mapping']);

        $options = ['methods_services_id' => MethodsServices::factory()->create()->id, 'methods_families_id' => MethodsFamilies::value('id'), 'methods_units_id' => MethodsUnits::value('id'), 'sold' => '1'];
        $run = $this->postJson(route('admin.imports.run', ['type' => 'products']), ['token' => $data['token'], 'header' => true, 'mapping' => $data['mapping'], 'options' => $options])
            ->assertOk()->json();

        $this->assertSame([0, 1, 1], [$run['created'], $run['updated'], $run['rejected']]);
        $this->assertSame(__('imports.reject.not_a_number', ['field' => __('general_content.thickness_trans_key'), 'value' => 'abc']), $run['rejects'][0]['reason']);
        $this->assertSame('Équerre', Products::where('code', 'EQ-1')->value('label'));
        $this->assertEquals(2.5, (float) Products::where('code', 'EQ-1')->value('thickness'));
    }

    public function test_an_unknown_type_is_not_found(): void
    {
        $this->post(route('admin.imports.preview', ['type' => 'users']), ['file' => $this->clients()], ['Accept' => 'application/json'])->assertNotFound();
    }

    public function test_the_template_is_recognised_by_the_suggestion(): void
    {
        $response = $this->get(route('admin.imports.template', ['type' => 'quotes']))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx';
        file_put_contents($path, $response->streamedContent());
        $headers = IOFactory::load($path)->getActiveSheet()->toArray()[0];

        $fields = array_column(app(ImportCsvService::class)->fields('quotes'), 'key');
        $this->assertSame(array_flip($fields), app(ImportCsvService::class)->suggestMapping('quotes', $headers));
    }

    public function test_the_page_mounts_the_wizard(): void
    {
        $this->get(route('admin.imports.exports'))
            ->assertOk()
            ->assertSee('id="import-wizard-app"', false)
            ->assertSee(__('imports.wizard.tab'));
    }
}
