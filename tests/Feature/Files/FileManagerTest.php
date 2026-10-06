<?php

namespace Tests\Feature\Files;

use App\Models\File;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\User;
use App\Services\Files\FileKindResolver;
use App\Services\Files\FileRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FileManagerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        Storage::fake(config('files.disk'));

        // ProductsFactory picks an existing unit and family at random.
        MethodsUnits::factory()->create();
        MethodsFamilies::factory()->create();

        Permission::findOrCreate('products-menu');

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('products-menu');
        $this->actingAs($this->user);
    }

    /**
     * A product whose legacy CAD columns are empty, so the assertions on the
     * synchronised columns are not polluted by the factory's random values.
     */
    private function product(): Products
    {
        return Products::factory()->create([
            'picture' => null,
            'drawing_file' => null,
            'stl_file' => null,
            'svg_file' => null,
            // A purchased product makes the show page compute an average supply
            // delay through DATEDIFF, which SQLite does not provide.
            'purchased' => 2,
        ]);
    }

    public function test_the_product_page_exposes_the_documents_tab(): void
    {
        $product = $this->product();

        $response = $this->get(route('products.show', ['id' => $product->id]));

        $response->assertStatus(200);
        $response->assertSee('data-react="file-manager"', false);
        $response->assertSee('data-fileable-type="product"', false);
    }

    public function test_it_uploads_several_formats_at_once_and_resolves_their_kind(): void
    {
        $product = $this->product();

        $response = $this->postJson(route('files.json.store'), [
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
            'files' => [
                UploadedFile::fake()->create('plan.pdf', 12, 'application/pdf'),
                UploadedFile::fake()->create('piece.step', 40, 'application/octet-stream'),
                UploadedFile::fake()->create('flat.dxf', 8, 'application/octet-stream'),
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonCount(3, 'files');

        $kinds = File::pluck('kind', 'original_file_name');

        $this->assertSame(FileKindResolver::KIND_DOC, $kinds['plan.pdf']);
        $this->assertSame(FileKindResolver::KIND_BREP, $kinds['piece.step']);
        $this->assertSame(FileKindResolver::KIND_CAD2D, $kinds['flat.dxf']);

        $this->assertSame(3, $product->files()->count());
    }

    public function test_uploads_are_stored_outside_the_web_root(): void
    {
        $product = $this->product();

        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
            'files' => [UploadedFile::fake()->create('plan.pdf', 12, 'application/pdf')],
        ])->assertStatus(201);

        $file = File::firstOrFail();

        $this->assertStringStartsWith('private/files/', $file->path);
        Storage::disk(config('files.disk'))->assertExists($file->path);
        $this->assertFileDoesNotExist(public_path('file/' . $file->name));
    }

    public function test_a_primary_upload_feeds_the_legacy_product_column(): void
    {
        $product = $this->product();

        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
            'files' => [UploadedFile::fake()->create('plan.pdf', 12, 'application/pdf')],
            'role' => FileRole::PLAN,
            'is_primary' => true,
        ])->assertStatus(201);

        $file = File::firstOrFail();

        $this->assertSame($file->name, $product->fresh()->drawing_file);
    }

    public function test_promoting_a_file_demotes_the_previous_primary(): void
    {
        $product = $this->product();

        foreach (['planA.pdf', 'planB.pdf'] as $name) {
            $this->postJson(route('files.json.store'), [
                'fileable_type' => 'product',
                'fileable_id' => $product->id,
                'files' => [UploadedFile::fake()->create($name, 12, 'application/pdf')],
                'role' => FileRole::PLAN,
                'is_primary' => true,
            ])->assertStatus(201);
        }

        $primaries = $product->files()->wherePivot('is_primary', true)->get();

        $this->assertCount(1, $primaries);
        $this->assertSame('planB.pdf', $primaries->first()->original_file_name);
    }

    public function test_it_rejects_an_unsupported_extension(): void
    {
        $product = $this->product();

        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
            'files' => [UploadedFile::fake()->create('payload.php', 2, 'text/plain')],
        ])->assertStatus(422);

        $this->assertSame(0, File::count());
    }

    public function test_it_rejects_an_unknown_fileable_type(): void
    {
        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'App\Models\User',
            'fileable_id' => $this->user->id,
            'files' => [UploadedFile::fake()->create('plan.pdf', 12, 'application/pdf')],
        ])->assertStatus(422);
    }

    public function test_the_raw_route_requires_authentication(): void
    {
        $product = $this->product();

        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
            'files' => [UploadedFile::fake()->create('plan.pdf', 12, 'application/pdf')],
        ])->assertStatus(201);

        $file = File::firstOrFail();

        auth()->logout();

        $response = $this->get(route('files.raw', ['file' => $file->id]));

        $this->assertContains(
            $response->getStatusCode(),
            [302, 401, 403],
            'An anonymous visitor must never be able to download an attached document.',
        );
    }

    public function test_an_svg_is_served_with_a_locked_down_policy(): void
    {
        $product = $this->product();

        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
            'files' => [UploadedFile::fake()->create('flat.svg', 4, 'image/svg+xml')],
        ])->assertStatus(201);

        $file = File::firstOrFail();

        $response = $this->get(route('files.raw', ['file' => $file->id]));

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('sandbox', $response->headers->get('Content-Security-Policy'));
    }

    /**
     * GHSA-cqq2-7c22-p2gg: the MIME declared by the browser was stored and
     * reflected as the response Content-Type, so a "poc.pdf" sent as text/html
     * rendered as a page of our origin.
     */
    public function test_the_client_declared_mime_is_never_reflected(): void
    {
        $product = $this->product();

        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
            'files' => [UploadedFile::fake()->createWithContent('poc.pdf', '<script>alert(document.domain)</script>')->mimeType('text/html')],
        ])->assertStatus(201);

        $file = File::firstOrFail();

        $response = $this->get(route('files.raw', ['file' => $file->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_a_row_already_stored_with_a_hostile_type_is_neutralised(): void
    {
        $product = $this->product();

        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
            'files' => [UploadedFile::fake()->create('notes.txt', 1, 'text/plain')],
        ])->assertStatus(201);

        $file = File::firstOrFail();
        $file->update(['type' => 'text/html']);

        $response = $this->get(route('files.raw', ['file' => $file->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/octet-stream');
        $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_a_legacy_svg_is_served_with_a_locked_down_policy(): void
    {
        $path = trim(config('files.legacy_root'), '/') . '/svg/flat.svg';
        Storage::disk(config('files.disk'))->put($path, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $response = $this->get('/svg/flat.svg');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('sandbox', $response->headers->get('Content-Security-Policy'));
    }

    public function test_detaching_the_last_reference_deletes_the_file(): void
    {
        $product = $this->product();

        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
            'files' => [UploadedFile::fake()->create('plan.pdf', 12, 'application/pdf')],
            'role' => FileRole::PLAN,
            'is_primary' => true,
        ])->assertStatus(201);

        $file = File::firstOrFail();
        $path = $file->path;

        $this->deleteJson(route('files.json.destroy', ['file' => $file->id]), [
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
        ])->assertStatus(200);

        $this->assertSame(0, File::count());
        Storage::disk(config('files.disk'))->assertMissing($path);
        $this->assertNull($product->fresh()->drawing_file);
    }
}
