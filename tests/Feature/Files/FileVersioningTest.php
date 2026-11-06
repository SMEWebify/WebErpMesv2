<?php

namespace Tests\Feature\Files;

use App\Models\File;
use App\Models\Inspection\InspectionPlan;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\User;
use App\Services\Files\FileRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Versions des fichiers de la GED : remplacement dans la même lignée,
 * historique, ancienne version conservée et accessible, plan de contrôle validé
 * toujours sur sa version, colonnes héritées sur la dernière version.
 */
class FileVersioningTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        Storage::fake(config('files.disk'));

        MethodsUnits::factory()->create();
        MethodsFamilies::factory()->create();

        Permission::findOrCreate('products-menu');
        Permission::findOrCreate('products-edit');

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['products-menu', 'products-edit']);
        $this->actingAs($this->user);
    }

    private function product(): Products
    {
        return Products::factory()->create([
            'ind' => 'B',
            'picture' => null,
            'drawing_file' => null,
            'stl_file' => null,
            'svg_file' => null,
            'purchased' => 2,
        ]);
    }

    private function uploadPlan(Products $product, string $name = 'plan-A.pdf', array $extra = []): File
    {
        $this->postJson(route('files.json.store'), $extra + [
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
            'files' => [UploadedFile::fake()->create($name, 12, 'application/pdf')],
            'role' => FileRole::PLAN,
            'is_primary' => true,
        ])->assertStatus(201);

        return File::where('original_file_name', $name)->firstOrFail();
    }

    private function replace(File $file, Products $product, string $name = 'plan-B.pdf', array $extra = [])
    {
        return $this->postJson(route('files.json.replace', ['file' => $file->id]), $extra + [
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
            'file' => UploadedFile::fake()->create($name, 14, 'application/pdf'),
            'revision_label' => 'B',
            'comment' => 'Cote 12 modifiée',
        ]);
    }

    public function test_the_revision_label_is_stored_on_upload(): void
    {
        $file = $this->uploadPlan($this->product(), 'plan-A.pdf', ['revision_label' => '  A  ']);

        $this->assertSame('A', $file->revision_label);
        $this->assertNull($file->version_group_id);
    }

    public function test_replacing_creates_a_new_version_in_the_same_lineage(): void
    {
        $product = $this->product();
        $old = $this->uploadPlan($product);

        $response = $this->replace($old, $product);

        $response->assertStatus(201)
            ->assertJsonPath('file.version', 2)
            ->assertJsonPath('file.revision_label', 'B')
            ->assertJsonPath('file.role', FileRole::PLAN)
            ->assertJsonPath('file.is_primary', true)
            ->assertJsonPath('file.has_history', true);

        $old->refresh();
        $new = File::findOrFail($response->json('file.id'));

        $this->assertSame($old->id, $old->version_group_id);
        $this->assertSame(1, $old->version);
        $this->assertSame($old->id, $new->version_group_id);
        $this->assertSame(2, $new->version);
        $this->assertSame('Cote 12 modifiée', $new->comment);

        // L'ancienne version est remplacée, gardée, rattachée, mais plus principale.
        $this->assertNotNull($old->superseded_at);
        $this->assertSame($new->id, $old->superseded_by);
        Storage::disk(config('files.disk'))->assertExists($old->path);

        $pivots = $product->files()->get()->keyBy('id');
        $this->assertCount(2, $pivots);
        $this->assertFalse((bool) $pivots[$old->id]->pivot->is_primary);
        $this->assertTrue((bool) $pivots[$new->id]->pivot->is_primary);
        $this->assertSame(FileRole::PLAN, $pivots[$new->id]->pivot->role);
    }

    public function test_the_list_shows_the_latest_version_only(): void
    {
        $product = $this->product();
        $old = $this->uploadPlan($product);
        $newId = $this->replace($old, $product)->json('file.id');

        $response = $this->getJson(route('files.json.list', ['fileable_type' => 'product', 'fileable_id' => $product->id]));

        $response->assertOk()->assertJsonCount(1, 'files');
        $this->assertSame($newId, $response->json('files.0.id'));
    }

    public function test_the_history_lists_every_version_and_the_replacement_is_logged(): void
    {
        $product = $this->product();
        $old = $this->uploadPlan($product, 'plan-A.pdf', ['revision_label' => 'A']);
        $v2 = $this->replace($old, $product)->json('file.id');
        $v3 = $this->replace(File::findOrFail($v2), $product, 'plan-C.pdf', ['revision_label' => 'C', 'comment' => null])
            ->assertStatus(201)
            ->json('file.id');

        $response = $this->getJson(route('files.json.versions', [
            'file' => $old->id,
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
        ]));

        $response->assertOk()->assertJsonCount(3, 'versions');
        $this->assertSame([$v3, $v2, $old->id], array_column($response->json('versions'), 'id'));
        $this->assertSame(['C', 'B', 'A'], array_column($response->json('versions'), 'revision_label'));
        $this->assertSame([true, false, false], array_column($response->json('versions'), 'is_current'));
        $this->assertSame($this->user->name, $response->json('versions.1.uploaded_by'));
        $this->assertSame('Cote 12 modifiée', $response->json('versions.1.comment'));

        $log = Activity::where('description', 'file_version_replaced')->orderBy('id')->get();
        $this->assertCount(2, $log);
        $this->assertSame($v2, (int) $log[0]->subject_id);
        $this->assertSame($old->id, $log[0]->properties['previous_file_id']);
        $this->assertSame('B', $log[0]->properties['revision_label']);
        $this->assertSame($this->user->id, (int) $log[0]->causer_id);
    }

    public function test_a_replaced_version_stays_downloadable(): void
    {
        $product = $this->product();
        $old = $this->uploadPlan($product);
        $this->replace($old, $product)->assertStatus(201);

        $this->get(route('files.raw', ['file' => $old->id]))->assertOk();
        $this->get(route('files.download', ['file' => $old->id]))->assertOk();
    }

    public function test_a_replaced_version_cannot_be_replaced_again(): void
    {
        $product = $this->product();
        $old = $this->uploadPlan($product);
        $this->replace($old, $product)->assertStatus(201);

        $this->replace($old->fresh(), $product, 'fork.pdf')->assertStatus(409);

        $this->assertSame(0, File::where('original_file_name', 'fork.pdf')->count());
    }

    public function test_no_version_of_a_lineage_can_be_deleted(): void
    {
        $product = $this->product();
        $old = $this->uploadPlan($product);
        $newId = $this->replace($old, $product)->json('file.id');

        foreach ([$old->id, $newId] as $id) {
            $this->deleteJson(route('files.json.destroy', ['file' => $id]), [
                'fileable_type' => 'product',
                'fileable_id' => $product->id,
            ])->assertStatus(409);
        }

        $this->assertSame(2, File::count());
        $this->assertSame(2, $product->files()->count());
    }

    public function test_a_plain_file_is_still_deleted_as_before(): void
    {
        $product = $this->product();
        $file = $this->uploadPlan($product);

        $this->deleteJson(route('files.json.destroy', ['file' => $file->id]), [
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
        ])->assertOk();

        $this->assertSame(0, File::count());
    }

    public function test_the_legacy_columns_follow_the_latest_version(): void
    {
        $product = $this->product();
        $old = $this->uploadPlan($product);
        $this->assertSame($old->name, $product->fresh()->drawing_file);

        $new = File::findOrFail($this->replace($old, $product)->json('file.id'));

        $this->assertSame($new->name, $product->fresh()->drawing_file);

        // Les anciennes URLs restent servies, l'ancienne comme la nouvelle.
        $this->get('/drawing/' . $new->name)->assertOk();
        $this->get('/drawing/' . $old->name)->assertOk();
    }

    public function test_every_attachment_of_the_old_version_is_copied(): void
    {
        $product = $this->product();
        $other = $this->product();
        $old = $this->uploadPlan($product);

        // Le même plan rattaché à un second article, en document secondaire.
        $other->files()->attach($old->id, ['role' => FileRole::PLAN, 'is_primary' => false]);

        $newId = $this->replace($old, $product)->json('file.id');

        $copy = $other->files()->whereKey($newId)->first();
        $this->assertNotNull($copy);
        $this->assertFalse((bool) $copy->pivot->is_primary);
        $this->assertTrue($other->files()->whereKey($old->id)->exists());
    }

    public function test_an_approved_control_plan_keeps_its_version(): void
    {
        $product = $this->product();
        $old = $this->uploadPlan($product);

        $plan = InspectionPlan::create([
            'products_id' => $product->id,
            'revision' => 'A',
            'file_id' => $old->id,
            'status' => InspectionPlan::STATUS_APPROVED,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);

        $this->replace($old, $product)->assertStatus(201);

        $plan->refresh();
        $this->assertSame($old->id, $plan->file_id);
        $this->assertSame(InspectionPlan::STATUS_APPROVED, $plan->status);
        $this->assertTrue($product->files()->whereKey($old->id)->exists());
        $this->get(route('files.raw', ['file' => $plan->file_id]))->assertOk();
    }

    public function test_replacing_an_item_drawing_requires_products_edit(): void
    {
        $product = $this->product();
        $old = $this->uploadPlan($product);

        $reader = User::factory()->create();
        $reader->givePermissionTo('products-menu');
        $this->actingAs($reader);

        $this->replace($old, $product)->assertStatus(403);

        $this->assertNull($old->fresh()->superseded_at);
    }

    public function test_another_writer_may_revise_a_drawing_they_did_not_upload(): void
    {
        $product = $this->product();
        $old = $this->uploadPlan($product);

        $colleague = User::factory()->create();
        $colleague->givePermissionTo(['products-menu', 'products-edit']);
        $this->actingAs($colleague);

        $this->replace($old, $product)->assertStatus(201);
    }

    public function test_the_history_of_another_entity_file_is_not_exposed(): void
    {
        $product = $this->product();
        $other = $this->product();
        $old = $this->uploadPlan($product);

        $this->getJson(route('files.json.versions', [
            'file' => $old->id,
            'fileable_type' => 'product',
            'fileable_id' => $other->id,
        ]))->assertNotFound();
    }
}
