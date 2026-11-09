<?php

namespace Tests\Feature\Files;

use App\Models\File;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\User;
use App\Services\Files\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * « Modifier dans Excel » : session WebDAV à jeton, chaque enregistrement
 * d'Office devient une nouvelle version du fichier.
 */
class OfficeEditTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Products $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        Storage::fake(config('files.disk'));
        config(['files.office_edit.enabled' => true]);

        MethodsUnits::factory()->create();
        MethodsFamilies::factory()->create();

        Permission::findOrCreate('products-menu');
        Permission::findOrCreate('products-edit');

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['products-menu', 'products-edit']);
        $this->actingAs($this->user);

        $this->product = Products::factory()->create(['picture' => null, 'drawing_file' => null, 'stl_file' => null, 'svg_file' => null, 'purchased' => 2]);
    }

    private function upload(string $name = 'chiffrage.xlsx', string $contents = 'v1'): File
    {
        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'product',
            'fileable_id' => $this->product->id,
            'files' => [UploadedFile::fake()->createWithContent($name, $contents)],
            'revision_label' => 'B',
        ])->assertStatus(201);

        return File::latest('id')->firstOrFail();
    }

    /**
     * @return array{0: string, 1: string} lien Office, adresse WebDAV (chemin)
     */
    private function open(File $file): array
    {
        $link = $this->postJson(route('files.json.office-edit', ['file' => $file->id]), [
            'fileable_type' => 'product',
            'fileable_id' => $this->product->id,
        ])->assertOk()->json('url');

        $url = substr($link, strpos($link, '|u|') + 3);

        return [$link, parse_url($url, PHP_URL_PATH)];
    }

    private function dav(string $method, string $uri, array $headers = [], ?string $body = null)
    {
        $server = [];
        foreach ($headers as $name => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }

        return $this->call($method, $uri, [], [], [], $server, $body);
    }

    public function test_the_list_offers_office_editing_only_when_enabled(): void
    {
        $this->upload();
        $this->upload('plan.pdf');

        $files = collect($this->getJson(route('files.json.list', ['fileable_type' => 'product', 'fileable_id' => $this->product->id]))->json('files'))
            ->keyBy('name');

        $this->assertSame('ms-excel', $files['chiffrage.xlsx']['office_edit']);
        $this->assertNull($files['plan.pdf']['office_edit']);

        config(['files.office_edit.enabled' => false]);
        $file = File::where('original_file_name', 'chiffrage.xlsx')->firstOrFail();

        $this->postJson(route('files.json.office-edit', ['file' => $file->id]), [
            'fileable_type' => 'product',
            'fileable_id' => $this->product->id,
        ])->assertNotFound();
    }

    public function test_the_link_opens_excel_on_a_webdav_address(): void
    {
        [$link, $path] = $this->open($this->upload());

        $this->assertStringStartsWith('ms-excel:ofe|u|http', $link);
        $this->assertMatchesRegularExpression('~^/dav/[A-Za-z0-9]{48}/chiffrage\.xlsx$~', $path);
    }

    public function test_the_webdav_routes_run_without_session_nor_csrf(): void
    {
        $middleware = Route::getRoutes()->getByName('office.dav.file')->gatherMiddleware();

        $this->assertNotContains('web', $middleware);
    }

    public function test_excel_reads_locks_saves_and_unlocks(): void
    {
        $file = $this->upload();
        app()->setLocale('fr');
        [, $path] = $this->open($file);
        // Les requêtes d'Office arrivent sans langue : celle du clic doit rester.
        app()->setLocale('en');

        $this->dav('OPTIONS', $path)->assertOk()->assertHeader('DAV', '1, 2');

        $propfind = $this->dav('PROPFIND', $path, ['Depth' => '0']);
        $propfind->assertStatus(207);
        $this->assertStringContainsString('<D:getcontentlength>2</D:getcontentlength>', $propfind->getContent());
        $this->assertStringContainsString('spreadsheetml.sheet', $propfind->getContent());

        $folder = $this->dav('PROPFIND', dirname($path) . '/', ['Depth' => '1']);
        $folder->assertStatus(207);
        $this->assertStringContainsString('<D:collection/>', $folder->getContent());
        $this->assertStringContainsString('chiffrage.xlsx', $folder->getContent());

        $this->assertSame('v1', $this->dav('GET', $path)->assertOk()->getContent());

        $lock = $this->dav('LOCK', $path, ['Timeout' => 'Second-600'], '<?xml version="1.0"?><D:lockinfo xmlns:D="DAV:"><D:lockscope><D:exclusive/></D:lockscope><D:locktype><D:write/></D:locktype><D:owner><D:href>Utilisateur</D:href></D:owner></D:lockinfo>');
        $lock->assertOk();
        $lockToken = trim($lock->headers->get('Lock-Token'), '<>');
        $this->assertStringStartsWith('opaquelocktoken:', $lockToken);

        // Un second verrou, ou un enregistrement sans le jeton du verrou, est refusé.
        $this->dav('LOCK', $path, [], '<D:lockinfo xmlns:D="DAV:"/>')->assertStatus(423);
        $this->dav('PUT', $path, [], 'pirate')->assertStatus(423);

        $this->dav('PUT', $path, ['If' => "(<{$lockToken}>)"], 'v2')->assertNoContent();
        $this->dav('PUT', $path, ['If' => "(<{$lockToken}>)"], 'v3')->assertNoContent();

        $versions = File::where('version_group_id', $file->id)->orderBy('version')->get();
        $this->assertSame([1, 2, 3], $versions->pluck('version')->all());
        $latest = $versions->last();
        $this->assertSame('chiffrage.xlsx', $latest->original_file_name);
        $this->assertSame('B', $latest->revision_label, 'The revision label is kept.');
        $this->assertSame('Modifié dans Office', $latest->comment);
        $this->assertSame($this->user->id, $latest->user_id);
        $this->assertTrue($this->product->files()->whereKey($latest->id)->exists(), 'The new version keeps the attachment.');
        $this->assertTrue($file->fresh()->isSuperseded());

        // La session suit la dernière version.
        $this->assertSame('v3', $this->dav('GET', $path)->assertOk()->getContent());

        $this->dav('UNLOCK', $path, ['Lock-Token' => "<{$lockToken}>"])->assertNoContent();
        $this->dav('PUT', $path, [], 'v4')->assertNoContent();
    }

    public function test_an_unknown_token_opens_nothing(): void
    {
        $this->dav('GET', '/dav/' . str_repeat('a', 48) . '/x.xlsx')->assertNotFound();
        $this->dav('PUT', '/dav/' . str_repeat('a', 48) . '/x.xlsx', [], 'x')->assertNotFound();
    }

    public function test_a_withdrawn_right_closes_the_session(): void
    {
        [, $path] = $this->open($this->upload());

        $this->user->revokePermissionTo('products-edit');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertSame('v1', $this->dav('GET', $path)->assertOk()->getContent());
        $this->dav('PUT', $path, [], 'v2')->assertForbidden();
        $this->assertSame(1, File::count());
    }

    public function test_a_version_uploaded_meanwhile_is_not_overwritten(): void
    {
        $file = $this->upload();
        [, $path] = $this->open($file);

        $this->postJson(route('files.json.replace', ['file' => $file->id]), [
            'fileable_type' => 'product',
            'fileable_id' => $this->product->id,
            'file' => UploadedFile::fake()->createWithContent('chiffrage.xlsx', 'autre'),
        ])->assertStatus(201);

        $this->dav('PUT', $path, [], 'v2')->assertStatus(409);
        $this->assertSame(2, File::count());
    }

    public function test_only_the_latest_version_and_a_writer_may_open_office(): void
    {
        $file = $this->upload();
        $this->open($file);

        $reader = User::factory()->create();
        $reader->givePermissionTo('products-menu');

        $this->actingAs($reader)->postJson(route('files.json.office-edit', ['file' => $file->id]), [
            'fileable_type' => 'product',
            'fileable_id' => $this->product->id,
        ])->assertForbidden();
    }
}
