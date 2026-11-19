<?php

namespace Tests\Feature\Configurators;

use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Integrations\ConfiguratorSession;
use App\Models\Integrations\ExternalConfigurator;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\LineConfigurationItem;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Configurators\ConfiguratorScreen;
use App\Services\Files\FileRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Configurateurs externes : session ouverte par l'ERP, résultat relu par le serveur
 * (jamais cru du navigateur), aperçu sans écriture, puis lignes, détails, options et
 * fichiers écrits après confirmation. Configurateur simulé par Http::fake.
 */
class ExternalConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://cfg.test/api/v1/';

    private User $user;
    private Companies $company;
    private ExternalConfigurator $configurator;
    private Quotes $quote;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        Storage::fake(config('files.disk'));
        config(['configurators.enabled' => true]);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(Permission::findOrCreate('your-company-menu'));
        $this->actingAs($this->user);
        \App\Models\Methods\MethodsFamilies::factory()->create();

        AccountingVat::factory()->create(['rate' => 20, 'default' => 1]);
        MethodsUnits::factory()->create(['code' => 'U', 'label' => 'Unité', 'default' => 1]);

        $this->company = Companies::factory()->create(['discount' => 15, 'statu_customer' => 2]);
        $this->quote = Quotes::factory()->create([
            'companies_id' => $this->company->id, 'statu' => 1,
            'companies_contacts_id'  => CompaniesContacts::factory()->create(['companies_id' => $this->company->id])->id,
            'companies_addresses_id' => CompaniesAddresses::factory()->create(['companies_id' => $this->company->id])->id,
        ]);

        $this->configurator = new ExternalConfigurator([
            'code' => 'punch', 'label' => 'Poinçons', 'base_url' => 'https://cfg.test',
            'display' => 'iframe', 'document_types' => ['quote', 'order'], 'price_mode' => 'gross', 'active' => true,
        ]);
        $this->configurator->api_key = 'secret-key';
        $this->configurator->save();
    }

    private function configuredResult(array $overrides = []): array
    {
        return array_replace([
            'result_id'         => 'res-1',
            'session_id'        => 'remote-1',
            'configuration_ref' => 'CFG-0001',
            'lines'             => [[
                'code'           => 'PC-RECT-LP',
                'label'          => 'Poinçon rectangle long plat 30 × 5',
                'product_code'   => 'PC-1',
                'quantity'       => 2,
                'unit'           => 'U',
                'unit_price'     => 100,
                'lead_time_days' => 8,
                'options'        => [['code' => 'COAT', 'label' => 'Revêtement', 'value' => 'Optima', 'unit_price' => 20]],
                'dimensions'     => [['code' => 'A', 'label' => 'Cote A', 'value' => 30, 'unit' => 'mm']],
                'detail'         => ['material' => 'Acier outil', 'thickness' => 3, 'x_size' => 30, 'y_size' => 5, 'unknown' => 'x'],
                'supplier'       => ['code' => 'F1', 'reference' => '25825', 'label' => 'PUNCH RECT LP'],
                'comment_external' => 'Poinçon long plat, revêtement Optima',
                'files'          => [
                    ['id' => 'f1', 'name' => 'plan.pdf', 'role' => 'plan', 'size' => 10],
                    ['id' => 'f2', 'name' => '../../model.step', 'role' => 'unknown-role', 'size' => 10],
                ],
            ]],
            'files' => [['id' => 'f9', 'name' => 'configuration.pdf', 'role' => 'autre', 'size' => 10]],
        ], $overrides);
    }

    private function fakeConfigurator(array $result = [], string $url = 'https://cfg.test/s/abc'): void
    {
        Http::fake([
            self::API . 'sessions'                   => Http::response(['session_id' => 'remote-1', 'url' => $url], 201),
            self::API . 'results/res-1/files/*'      => Http::response('%PDF-1.4 fichier', 200, ['Content-Type' => 'application/pdf']),
            self::API . 'results/res-1'              => Http::response($result ?: $this->configuredResult()),
            self::API . 'ping'                       => Http::response(['name' => 'Configurateur test', 'version' => '1.0', 'contract' => 'v1']),
        ]);
    }

    private function open(string $type = 'quote', ?int $documentId = null)
    {
        return $this->postJson(route('configurators.open', ['type' => $type, 'documentId' => $documentId ?? $this->quote->id]), [
            'configurator_id' => $this->configurator->id,
            'product_id'      => Products::factory()->create(['code' => 'PC-1'])->id,
            'quantity'        => 2,
        ]);
    }

    // ------------------------------------------------------------------
    // Ouverture
    // ------------------------------------------------------------------

    public function test_opening_sends_the_document_context_without_address(): void
    {
        $this->fakeConfigurator();

        $this->open()->assertOk()->assertJson([
            'url' => 'https://cfg.test/s/abc', 'display' => 'iframe', 'origin' => 'https://cfg.test',
        ]);

        $session = ConfiguratorSession::firstOrFail();
        $this->assertSame('remote-1', $session->remote_session_id);
        $this->assertSame($this->quote->code . '/L1', $session->reference_externe);

        Http::assertSent(function (HttpRequest $request) {
            if ($request->url() !== self::API . 'sessions') {
                return false;
            }
            $data = $request->data();

            return $request->hasHeader('Authorization', 'Bearer secret-key')
                && $data['product']['code'] === 'PC-1'
                && $data['customer']['code'] === $this->company->code
                && ! isset($data['customer']['address'])
                && $data['return']['origin'] !== null;
        });
    }

    public function test_a_session_url_from_another_site_is_refused(): void
    {
        $this->fakeConfigurator([], 'https://evil.test/s/abc');

        $this->open()->assertStatus(422);
        $this->assertSame(ConfiguratorSession::STATUS_CANCELLED, ConfiguratorSession::firstOrFail()->status);
    }

    public function test_an_inactive_configurator_is_neither_offered_nor_opened(): void
    {
        $this->configurator->update(['active' => false]);

        $this->assertSame([], ConfiguratorScreen::endpoints('quote', $this->quote->id));
        $this->open()->assertNotFound();
    }

    // ------------------------------------------------------------------
    // Aperçu
    // ------------------------------------------------------------------

    public function test_preview_reads_the_result_server_side_and_writes_nothing(): void
    {
        $this->fakeConfigurator();
        $session = $this->open()->json('session');

        $result = $this->postJson(route('configurators.result', ['session' => $session]), ['result_id' => 'res-1'])
            ->assertOk()->json('result');

        // Prix brut : remise du client (15 %) appliquée.
        $this->assertSame(15.0, (float) $result['lines'][0]['discount']);
        $this->assertSame(170.0, (float) $result['lines'][0]['net_total']);
        $this->assertSame('PC-1', $result['lines'][0]['product_code']);
        // Rôle inconnu → « autre », nom de fichier sans chemin.
        $this->assertSame(FileRole::OTHER, $result['lines'][0]['files'][1]['role']);
        $this->assertSame('model.step', $result['lines'][0]['files'][1]['name']);
        $this->assertSame(0, QuoteLines::count());
    }

    public function test_a_result_of_another_session_is_refused(): void
    {
        $this->fakeConfigurator($this->configuredResult(['session_id' => 'remote-OTHER']));
        $session = $this->open()->json('session');

        $this->postJson(route('configurators.result', ['session' => $session]), ['result_id' => 'res-1'])
            ->assertStatus(422);
        $this->assertSame(ConfiguratorSession::STATUS_PENDING, ConfiguratorSession::firstOrFail()->status);
    }

    public function test_a_session_of_another_user_does_not_exist_for_me(): void
    {
        $this->fakeConfigurator();
        $session = $this->open()->json('session');

        $this->actingAs(User::factory()->create());
        $this->postJson(route('configurators.result', ['session' => $session]), ['result_id' => 'res-1'])->assertNotFound();
    }

    public function test_a_result_without_line_is_refused(): void
    {
        $this->fakeConfigurator($this->configuredResult(['lines' => []]));
        $session = $this->open()->json('session');

        $this->postJson(route('configurators.result', ['session' => $session]), ['result_id' => 'res-1'])->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // Écriture
    // ------------------------------------------------------------------

    public function test_confirmed_result_becomes_lines_details_items_and_files(): void
    {
        $this->fakeConfigurator();
        $session = $this->open()->json('session');
        $this->postJson(route('configurators.result', ['session' => $session]), ['result_id' => 'res-1'])->assertOk();

        $this->postJson(route('configurators.apply', ['session' => $session]))
            ->assertCreated()->assertJson(['created' => 1, 'warnings' => []]);

        $line = QuoteLines::where('quotes_id', $this->quote->id)->firstOrFail();
        $this->assertSame('Poinçon rectangle long plat 30 × 5', $line->label);
        $this->assertEquals(2, $line->qty);
        $this->assertEquals(100, $line->getRawOriginal('selling_price'));
        $this->assertEquals(15, $line->discount);
        $this->assertSame($this->configurator->id, (int) $line->external_configurator_id);
        $this->assertSame('CFG-0001', $line->configuration_ref);
        $this->assertFalse((bool) $line->use_calculated_price);
        $this->assertSame(now()->addDays(8)->toDateString(), substr((string) $line->delivery_date, 0, 10));

        $detail = $line->QuoteLineDetails;
        $this->assertSame('Acier outil', $detail->material);
        $this->assertEquals(3, $detail->thickness);
        $this->assertSame('Poinçon long plat, revêtement Optima', $detail->external_comment);

        $items = $line->configurationItems()->get();
        $this->assertSame(
            [LineConfigurationItem::KIND_OPTION, LineConfigurationItem::KIND_DIMENSION, LineConfigurationItem::KIND_SUPPLIER],
            $items->pluck('kind')->all()
        );
        $this->assertSame('Optima', $items[0]->value);
        $this->assertSame('25825', $items[2]->value);

        $files = $line->files()->get();
        $this->assertCount(2, $files);
        $plan = $files->firstWhere('original_file_name', 'plan.pdf');
        $this->assertSame(FileRole::PLAN, $plan->pivot->role);
        $this->assertTrue((bool) $plan->pivot->is_primary);
        $this->assertSame(1, $this->quote->files()->count());

        $this->assertSame(ConfiguratorSession::STATUS_DONE, ConfiguratorSession::firstOrFail()->status);

        // Une seconde confirmation ne recrée rien.
        $this->postJson(route('configurators.apply', ['session' => $session]))->assertStatus(409);
        $this->assertSame(1, QuoteLines::where('quotes_id', $this->quote->id)->count());

        // La ligne porte la pastille du configurateur dans la liste des lignes.
        $this->getJson(route('quotes.lines.json.for-quote', ['quoteId' => $this->quote->id]))
            ->assertOk()->assertJsonPath('lines.0.configured', 'Poinçons');
    }

    public function test_net_price_mode_applies_no_discount(): void
    {
        $this->configurator->update(['price_mode' => 'net']);
        $this->fakeConfigurator();
        $session = $this->open()->json('session');

        $result = $this->postJson(route('configurators.result', ['session' => $session]), ['result_id' => 'res-1'])->json('result');
        $this->assertSame(0.0, (float) $result['lines'][0]['discount']);
        $this->assertSame(200.0, (float) $result['lines'][0]['net_total']);
    }

    public function test_a_failed_file_is_reported_without_losing_the_lines(): void
    {
        Http::fake([
            self::API . 'sessions'              => Http::response(['session_id' => 'remote-1', 'url' => 'https://cfg.test/s/abc'], 201),
            self::API . 'results/res-1/files/*' => Http::response('', 500),
            self::API . 'results/res-1'         => Http::response($this->configuredResult()),
        ]);
        $session = $this->open()->json('session');
        $this->postJson(route('configurators.result', ['session' => $session]), ['result_id' => 'res-1'])->assertOk();

        $response = $this->postJson(route('configurators.apply', ['session' => $session]))->assertCreated();

        $this->assertCount(3, $response->json('warnings'));
        $this->assertSame(1, QuoteLines::where('quotes_id', $this->quote->id)->count());
    }

    public function test_order_lines_go_through_the_credit_limit(): void
    {
        $order = Orders::factory()->create([
            'companies_id' => $this->company->id, 'type' => 1, 'statu' => 1,
            'companies_contacts_id'  => $this->quote->companies_contacts_id,
            'companies_addresses_id' => $this->quote->companies_addresses_id,
        ]);
        // 170 HT × 1,2 = 204 TTC > 100
        $this->company->update(['credit_limit' => 100, 'credit_block_level' => 'block']);
        Permission::findOrCreate('credit-limit-override');

        $this->fakeConfigurator();
        $session = $this->open('order', $order->id)->assertOk()->json('session');
        $this->postJson(route('configurators.result', ['session' => $session]), ['result_id' => 'res-1'])->assertOk();

        $this->postJson(route('configurators.apply', ['session' => $session]))
            ->assertStatus(409)->assertJsonPath('credit_limit.level', 'block');
        $this->assertSame(0, OrderLines::where('orders_id', $order->id)->count());

        $this->company->update(['credit_limit' => null]);
        $this->postJson(route('configurators.apply', ['session' => $session]))->assertCreated();

        $line = OrderLines::where('orders_id', $order->id)->firstOrFail();
        $this->assertEquals(2, $line->delivered_remaining_qty);
        $this->assertSame(1, $line->configurationItems()->where('kind', 'option')->count());
    }

    public function test_a_locked_quote_accepts_no_configuration(): void
    {
        $this->quote->update(['statu' => 3]);
        $this->fakeConfigurator();

        $this->open()->assertStatus(422);
    }

    public function test_return_page_hands_the_result_back_to_the_opener(): void
    {
        $this->fakeConfigurator();
        $session = $this->open()->json('session');

        $this->get(route('configurators.return', ['session' => $session, 'result_id' => 'res-1']))
            ->assertOk()
            ->assertSee('wem-configurator:return', false)
            ->assertSee($session, false);
    }

    // ------------------------------------------------------------------
    // Réglage
    // ------------------------------------------------------------------

    public function test_admin_saves_the_key_encrypted_and_tests_the_link(): void
    {
        $this->fakeConfigurator();

        $this->post(route('admin.integrations.configurators.store'), [
            'code' => 'bend', 'label' => 'Pliage', 'base_url' => 'https://cfg.test/', 'api_key' => 'k-123',
            'display' => 'tab', 'document_types' => ['quote'], 'price_mode' => 'net', 'active' => 1,
        ])->assertRedirect(route('admin.integrations.configurators.index'))->assertSessionHas('success');

        $saved = ExternalConfigurator::where('code', 'bend')->firstOrFail();
        $this->assertSame('https://cfg.test', $saved->base_url);
        $this->assertSame('k-123', $saved->api_key);
        $this->assertNotSame('k-123', $saved->getRawOriginal('api_key'));
        $this->assertNotNull($saved->last_check_at);
        $this->assertArrayNotHasKey('api_key', $saved->toArray());

        // Clé laissée vide : conservée.
        $this->put(route('admin.integrations.configurators.update', $saved), [
            'code' => 'bend', 'label' => 'Pliage 2', 'base_url' => 'https://cfg.test', 'api_key' => '',
            'display' => 'tab', 'document_types' => ['quote', 'order'], 'price_mode' => 'net', 'active' => 1,
        ])->assertRedirect();
        $this->assertSame('k-123', $saved->fresh()->api_key);

        $this->get(route('admin.integrations.configurators.index'))->assertOk()->assertDontSee('k-123');
    }
}
