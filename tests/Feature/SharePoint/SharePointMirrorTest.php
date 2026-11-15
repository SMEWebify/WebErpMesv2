<?php

namespace Tests\Feature\SharePoint;

use App\Models\File;
use App\Models\Integrations\SharePointMirrorItem;
use App\Models\Integrations\SharePointSetting;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\User;
use App\Services\Files\FileStorageService;
use App\Services\Files\FileVersioning;
use App\Services\Integrations\SharePoint\SharePointMirror;
use App\Services\Integrations\SharePoint\SharePointNames;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Copie de la GED vers SharePoint (Microsoft Graph simulé par Http::fake) : dossiers,
 * envoi simple et par morceaux, nouvelle version sur le même élément, exclusions,
 * échec sans effet sur la GED, fichiers existants.
 */
class SharePointMirrorTest extends TestCase
{
    use RefreshDatabase;

    private const GRAPH = 'https://graph.microsoft.com/v1.0';

    private FileStorageService $storage;

    /** @var array<int, string> noms des dossiers créés */
    private array $createdFolders = [];

    private int $uploads = 0;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('files.disk'));
        MethodsUnits::factory()->create();
        MethodsFamilies::factory()->create();

        $this->storage = app(FileStorageService::class);
    }

    private function link(array $extra = []): SharePointSetting
    {
        return SharePointSetting::create(array_merge([
            'tenant_id'     => 'contoso.onmicrosoft.com',
            'client_id'     => '11111111-2222-3333-4444-555555555555',
            'client_secret' => 'secret-value-XYZ',
            'site_url'      => 'https://contoso.sharepoint.com/sites/ERP',
            'is_active'     => true,
            'site_id'       => 'site-1',
            'drive_id'      => 'drive-1',
            'drive_web_url' => 'https://contoso.sharepoint.com/sites/ERP/Documents',
            'status'        => SharePointSetting::STATUS_OK,
        ], $extra));
    }

    private function fakeGraph(int $uploadStatus = 201, array $existingFolders = []): void
    {
        Http::fake(function (ClientRequest $request) use ($uploadStatus, $existingFolders) {
            $url = $request->url();
            $method = $request->method();

            if (str_starts_with($url, 'https://login.microsoftonline.com/')) {
                return Http::response(['access_token' => 'graph-token', 'expires_in' => 3600]);
            }

            if (str_starts_with($url, 'https://upload.test/')) {
                preg_match('#bytes (\d+)-(\d+)/(\d+)#', $request->header('Content-Range')[0] ?? '', $m);

                return ((int) $m[2] + 1) === (int) $m[3]
                    ? Http::response(['id' => 'item-big', 'name' => 'gros.bin', 'webUrl' => 'https://sp/gros.bin'], 201)
                    : Http::response(['nextExpectedRanges' => [((int) $m[2] + 1) . '-']], 202);
            }

            if ($method === 'POST' && str_ends_with($url, '/children')) {
                $name = $request['name'];
                if (in_array($name, $existingFolders, true)) {
                    return Http::response(['error' => ['code' => 'nameAlreadyExists']], 409);
                }
                $this->createdFolders[] = $name;

                return Http::response(['id' => 'folder-' . md5($name), 'name' => $name, 'webUrl' => 'https://sp/' . rawurlencode($name), 'folder' => ['childCount' => 0]], 201);
            }

            if ($method === 'GET' && str_contains($url, ':/')) {
                $name = rawurldecode(basename($url));

                return Http::response(['id' => 'existing-' . md5($name), 'name' => $name, 'webUrl' => 'https://sp/existing', 'folder' => ['childCount' => 1]]);
            }

            if ($method === 'POST' && str_ends_with($url, '/createUploadSession')) {
                return Http::response(['uploadUrl' => 'https://upload.test/session-1']);
            }

            if ($method === 'PUT' && str_contains($url, '/content')) {
                $this->uploads++;
                if ($uploadStatus >= 400) {
                    return Http::response(['error' => ['message' => 'Access denied']], $uploadStatus);
                }

                return Http::response(['id' => 'item-1', 'name' => 'plan-A.pdf', 'webUrl' => 'https://sp/plan-A.pdf'], 201);
            }

            return Http::response(['error' => ['message' => 'unexpected ' . $method . ' ' . $url]], 500);
        });
    }

    private function product(): Products
    {
        return Products::factory()->create([
            'code' => 'TOLE-01', 'label' => 'Support : gauche',
            'picture' => null, 'drawing_file' => null, 'stl_file' => null, 'svg_file' => null, 'purchased' => 2,
        ]);
    }

    private function attachPlan(Products $product, string $contents = '%PDF-1.4 plan', string $name = 'plan-A.pdf'): File
    {
        $file = $this->storage->storeContents($contents, $name, 'application/pdf');
        $this->storage->attach($file, $product, 'plan', true);

        return $file;
    }

    public function test_nothing_is_sent_while_the_link_is_inactive(): void
    {
        $this->link(['is_active' => false]);
        Http::fake();

        $this->attachPlan($this->product());

        Http::assertNothingSent();
        $this->assertSame(0, SharePointMirrorItem::count());
    }

    public function test_an_attached_file_is_copied_into_the_entity_folder(): void
    {
        $this->link();
        $this->fakeGraph();
        $product = $this->product();

        $file = $this->attachPlan($product);

        // ERP / Articles / {code} - {libellé} (le « : » est refusé par SharePoint).
        $this->assertSame(['ERP', 'Articles', 'TOLE-01 - Support _ gauche'], $this->createdFolders);

        $item = SharePointMirrorItem::sole();
        $this->assertSame(SharePointMirrorItem::STATUS_SYNCED, $item->status);
        $this->assertSame($file->id, (int) $item->synced_file_id);
        $this->assertSame('item-1', $item->remote_item_id);
        $this->assertSame('https://sp/plan-A.pdf', $item->web_url);

        Http::assertSent(fn (ClientRequest $r) => $r->method() === 'PUT'
            && $r->url() === self::GRAPH . '/drives/drive-1/items/folder-' . md5('TOLE-01 - Support _ gauche') . ':/plan-A.pdf:/content?@microsoft.graph.conflictBehavior=rename'
            && $r->hasHeader('Authorization', 'Bearer graph-token')
            && $r->body() === '%PDF-1.4 plan');
    }

    public function test_a_second_file_reuses_the_entity_folder(): void
    {
        $this->link();
        $this->fakeGraph();
        $product = $this->product();

        $this->attachPlan($product);
        $this->attachPlan($product, 'autre', 'photo.jpg');

        $this->assertCount(3, $this->createdFolders);
        $this->assertSame(2, SharePointMirrorItem::where('status', 'synced')->count());
    }

    public function test_an_existing_folder_is_reused_instead_of_duplicated(): void
    {
        $this->link();
        $this->fakeGraph(existingFolders: ['ERP']);

        $this->attachPlan($this->product());

        $this->assertNotContains('ERP', $this->createdFolders);
        Http::assertSent(fn (ClientRequest $r) => $r->method() === 'POST'
            && str_starts_with($r->url(), self::GRAPH . '/drives/drive-1/items/existing-' . md5('ERP') . '/children'));
    }

    public function test_a_new_version_replaces_the_same_sharepoint_item(): void
    {
        $this->link();
        $this->fakeGraph();
        $product = $this->product();
        $first = $this->attachPlan($product);

        $second = app(FileVersioning::class)->replaceWithContents($first, '%PDF-1.4 indice B', 'plan-B.pdf', 'application/pdf');

        $item = SharePointMirrorItem::sole();
        $this->assertSame($second->id, (int) $item->synced_file_id);
        $this->assertSame(2, (int) $item->synced_version);

        Http::assertSent(fn (ClientRequest $r) => $r->method() === 'PUT'
            && $r->url() === self::GRAPH . '/drives/drive-1/items/item-1/content'
            && $r->body() === '%PDF-1.4 indice B');
    }

    public function test_a_large_file_goes_through_an_upload_session_without_the_token(): void
    {
        config(['sharepoint.simple_upload_max_bytes' => 10, 'sharepoint.chunk_bytes' => 8]);
        $this->link();
        $this->fakeGraph();

        $this->attachPlan($this->product(), str_repeat('x', 20), 'gros.bin');

        $chunks = Http::recorded(fn (ClientRequest $r) => str_starts_with($r->url(), 'https://upload.test/'));
        $this->assertCount(3, $chunks);
        foreach ($chunks as [$request]) {
            $this->assertFalse($request->hasHeader('Authorization'));
        }
        $this->assertSame('item-big', SharePointMirrorItem::sole()->remote_item_id);
    }

    public function test_confidential_files_are_never_copied(): void
    {
        $this->link();
        Http::fake();
        $user = User::factory()->create();

        $file = $this->storage->storeContents('bulletin', 'paie.pdf', 'application/pdf');
        $this->storage->attach($file, $user, 'bulletin_paie');

        Http::assertNothingSent();
        $this->assertSame(0, SharePointMirrorItem::count());
    }

    public function test_a_sharepoint_failure_never_breaks_the_document_store(): void
    {
        $this->link();
        $this->fakeGraph(uploadStatus: 403);
        $product = $this->product();

        $file = $this->attachPlan($product);

        $this->assertTrue($product->files()->whereKey($file->id)->exists());
        $item = SharePointMirrorItem::sole();
        $this->assertSame(SharePointMirrorItem::STATUS_FAILED, $item->status);
        $this->assertStringContainsString('Access denied', $item->last_error);
        // 403 : inutile de réessayer, un seul envoi.
        $this->assertSame(1, $this->uploads);
    }

    public function test_existing_files_are_queued_by_the_backfill(): void
    {
        $product = $this->product();
        $this->attachPlan($product);
        $this->assertSame(0, SharePointMirrorItem::count());

        $this->link();
        $this->fakeGraph();

        $this->assertSame(1, app(SharePointMirror::class)->backfill(dryRun: true));
        $this->assertSame(0, SharePointMirrorItem::count());

        $this->artisan('wem:sharepoint:sync')->assertSuccessful();

        $this->assertSame(1, SharePointMirrorItem::where('status', 'synced')->count());
        $this->assertSame(0, app(SharePointMirror::class)->backfill(dryRun: true));
    }

    public function test_names_are_cleaned_for_sharepoint(): void
    {
        $this->assertSame('CMD_1 _ test', SharePointNames::clean('CMD#1 : test'));
        $this->assertSame('_CON.pdf', SharePointNames::clean('CON.pdf'));
        $this->assertSame('_lock.docx', SharePointNames::clean('~$lock.docx'));
        $this->assertSame('sans-nom', SharePointNames::clean(' .. '));
        $long = SharePointNames::clean(str_repeat('a', 200) . '.step');
        $this->assertSame(120, mb_strlen($long));
        $this->assertStringEndsWith('.step', $long);
    }
}
