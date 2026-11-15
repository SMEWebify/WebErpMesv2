<?php

namespace Tests\Feature\SharePoint;

use App\Models\Admin\Factory;
use App\Models\Integrations\SharePointSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Écran Intégrations → SharePoint : secret chiffré et jamais réaffiché, champ vide =
 * conserver, test qui résout le site et la bibliothèque.
 */
class SharePointSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'Abc8Q~super-secret-value';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('your-company-menu'));
        $this->actingAs($user);

        app()->forgetInstance('Factory');
        app()->instance('Factory', Factory::create([
            'name' => 'Tôlerie', 'address' => '1 rue', 'zipcode' => '35000', 'city' => 'Rennes',
            'phone_number' => '0', 'mail' => 'a@b.test', 'curency' => 'EUR', 'pdf_header_font_color' => '#dddddd',
        ]));
    }

    private function fakeGraph(array $drives = [['id' => 'drive-1', 'name' => 'Documents', 'webUrl' => 'https://contoso.sharepoint.com/sites/ERP/Shared%20Documents']]): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'graph-token', 'expires_in' => 3600]),
            'graph.microsoft.com/v1.0/sites/contoso.sharepoint.com:/sites/ERP' => Http::response(['id' => 'site-1', 'displayName' => 'ERP']),
            'graph.microsoft.com/v1.0/sites/site-1/drives' => Http::response(['value' => $drives]),
            'graph.microsoft.com/v1.0/sites/site-1/drive' => Http::response($drives[0]),
        ]);
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'tenant_id'     => 'contoso.onmicrosoft.com',
            'client_id'     => '11111111-2222-3333-4444-555555555555',
            'client_secret' => self::SECRET,
            'site_url'      => 'https://contoso.sharepoint.com/sites/ERP/',
            'library'       => 'Documents',
            'root_folder'   => 'ERP: Nest',
            'is_active'     => 1,
        ], $extra);
    }

    public function test_saving_encrypts_the_secret_and_resolves_the_library(): void
    {
        $this->fakeGraph();

        $this->put(route('admin.integrations.sharepoint.update'), $this->payload())
            ->assertRedirect(route('admin.integrations.sharepoint.index'))
            ->assertSessionHas('success');

        $this->assertStringNotContainsString(self::SECRET, (string) DB::table('sharepoint_settings')->value('client_secret'));

        $setting = SharePointSetting::current();
        $this->assertSame('drive-1', $setting->drive_id);
        $this->assertSame(SharePointSetting::STATUS_OK, $setting->status);
        $this->assertSame('ERP_ Nest', $setting->root_folder);
        $this->assertTrue($setting->isUsable());
        $this->assertArrayNotHasKey('client_secret', $setting->toArray());

        Http::assertSent(fn (ClientRequest $r) => str_contains($r->url(), '/oauth2/v2.0/token')
            && $r['client_secret'] === self::SECRET
            && $r['scope'] === 'https://graph.microsoft.com/.default');

        $this->get(route('admin.integrations.sharepoint.index'))
            ->assertOk()
            ->assertDontSee(self::SECRET, false);
    }

    public function test_an_empty_secret_keeps_the_saved_one(): void
    {
        $this->fakeGraph();
        $this->put(route('admin.integrations.sharepoint.update'), $this->payload());

        $this->put(route('admin.integrations.sharepoint.update'), $this->payload(['client_secret' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame(self::SECRET, SharePointSetting::current()->client_secret);
    }

    public function test_an_unknown_library_is_reported_with_the_available_ones(): void
    {
        $this->fakeGraph();

        $this->put(route('admin.integrations.sharepoint.update'), $this->payload(['library' => 'Plans']))
            ->assertSessionHas('warning');

        $setting = SharePointSetting::current();
        $this->assertSame(SharePointSetting::STATUS_ERROR, $setting->status);
        $this->assertNull($setting->drive_id);
        $this->assertStringContainsString('Documents', $setting->last_error);
        $this->assertFalse($setting->isUsable());
    }

    public function test_refused_credentials_are_reported_without_the_secret(): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response([
                'error' => 'invalid_client',
                'error_description' => "AADSTS7000215: Invalid client secret provided.\r\nTrace ID: x",
            ], 401),
        ]);

        $this->put(route('admin.integrations.sharepoint.update'), $this->payload());

        $this->postJson(route('admin.integrations.sharepoint.test'))
            ->assertOk()
            ->assertJson(['ok' => false])
            // Première ligne du message d'Entra ID seulement (ni trace, ni secret).
            ->assertJsonFragment(['message' => __('sharepoint.error.auth') . ' AADSTS7000215: Invalid client secret provided.']);

        $this->assertStringNotContainsString(self::SECRET, (string) SharePointSetting::current()->last_error);
    }

    public function test_the_hub_shows_the_sharepoint_card(): void
    {
        $this->get(route('admin.integrations.index'))
            ->assertOk()
            ->assertSee(route('admin.integrations.sharepoint.index'), false);
    }
}
