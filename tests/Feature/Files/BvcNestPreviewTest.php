<?php

namespace Tests\Feature\Files;

use App\Models\User;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Files\FileKindResolver;
use App\Services\Files\FileRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Unit\Cad\BvcFixture;

class BvcNestPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        Storage::fake(config('files.disk'));

        $this->actingAs(User::factory()->create());
    }

    public function test_a_bvc_dropped_on_a_quote_gets_one_svg_per_nest(): void
    {
        $quote = Quotes::factory()->create();

        $response = $this->postJson(route('files.json.store'), [
            'fileable_type' => 'quote',
            'fileable_id' => $quote->id,
            'files' => [UploadedFile::fake()->createWithContent('JOB.bvc', BvcFixture::archive())],
        ]);

        $response->assertCreated();

        $files = $quote->files()->get()->keyBy('original_file_name');
        $this->assertEqualsCanonicalizing(['JOB.bvc', 'JOB-01.svg', 'JOB-02.svg'], $files->keys()->all());

        $this->assertSame(FileKindResolver::KIND_OTHER, $files['JOB.bvc']->kind);

        $nest = $files['JOB-01.svg'];
        $this->assertSame(FileKindResolver::KIND_VECTOR, $nest->kind);
        $this->assertSame(FileRole::VECTOR, $nest->pivot->role);
        $this->assertContains('imbrication', $nest->hashtags);
        $this->assertSame(
            'S235 10mm — tôle 3000x1500 — 2 pièces — chute 90 % — coupe estimée 2 min — reste 2700x1500 (depuis JOB.bvc)',
            $nest->comment,
        );
        $this->assertStringContainsString('class="residual"', Storage::disk($nest->disk)->get($nest->path));

        // The second nest is cut twice.
        $this->assertStringContainsString('× 2 tôles', $files['JOB-02.svg']->comment);

        // Every file of the batch is returned, so the GED shows them at once.
        $this->assertCount(3, $response->json('files'));
    }

    public function test_a_bvc_dropped_on_a_line_is_kept_without_nest_drawing(): void
    {
        $line = QuoteLines::factory()->create();

        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'quote-line',
            'fileable_id' => $line->id,
            'files' => [UploadedFile::fake()->createWithContent('JOB.bvc', BvcFixture::archive())],
        ])->assertCreated();

        // A nest SVG in the vector role would pass for the drawing of the part.
        $this->assertSame(['JOB.bvc'], $line->files()->pluck('original_file_name')->all());
    }

    public function test_an_unreadable_bvc_is_still_stored(): void
    {
        $quote = Quotes::factory()->create();

        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'quote',
            'fileable_id' => $quote->id,
            'files' => [UploadedFile::fake()->createWithContent('JOB.bvc', 'not a zip')],
        ])->assertCreated();

        $this->assertSame(['JOB.bvc'], $quote->files()->pluck('original_file_name')->all());
    }
}
