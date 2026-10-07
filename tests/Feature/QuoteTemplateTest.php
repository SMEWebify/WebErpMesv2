<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\File;
use App\Models\User;
use App\Models\Planning\Task;
use App\Models\Planning\SubAssembly;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\QuoteLineDetails;
use App\Services\QuoteKPIService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuoteTemplateTest extends TestCase
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
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** Devis avec deux lignes, dont une portant une gamme sur deux niveaux. */
    private function quoteWithContent(array $attributes = []): Quotes
    {
        $quote = Quotes::factory()->create(array_merge(['statu' => 3, 'user_id' => $this->user->id], $attributes));

        $first = QuoteLines::factory()->create(['quotes_id' => $quote->id, 'ordre' => 1, 'statu' => 3, 'delivery_date' => '2026-12-01']);
        QuoteLineDetails::create(['quote_lines_id' => $first->id, 'material' => 'S235']);
        Task::factory()->create(['quote_lines_id' => $first->id, 'order_lines_id' => null, 'products_id' => null]);
        $root = SubAssembly::create(['ordre' => 1, 'quote_lines_id' => $first->id, 'child_id' => 1, 'qty' => 1, 'unit_price' => 1]);
        Task::factory()->create(['sub_assembly_id' => $root->id, 'quote_lines_id' => null, 'order_lines_id' => null, 'products_id' => null]);

        QuoteLines::factory()->create(['quotes_id' => $quote->id, 'ordre' => 2, 'statu' => 3]);

        $file = File::create(['user_id' => $this->user->id, 'name' => 'cdc.pdf', 'original_file_name' => 'cdc.pdf', 'type' => 'application/pdf', 'size' => '1']);
        $quote->files()->attach($file->id, ['role' => 'autre', 'is_primary' => false]);

        return $quote;
    }

    private function saveAsTemplate(Quotes $quote, string $label = 'Trame armoire'): Quotes
    {
        $this->post(route('quotes.save-template', ['id' => $quote->id]), ['template_label' => $label])->assertRedirect();

        return Quotes::onlyTemplates()->latest('id')->firstOrFail();
    }

    public function test_saving_as_template_copies_the_content_outside_quote_numbering(): void
    {
        $quote    = $this->quoteWithContent();
        $template = $this->saveAsTemplate($quote);

        $this->assertTrue($template->is_template);
        $this->assertSame('TRAME-' . $template->id, $template->code);
        $this->assertSame('Trame armoire', $template->label);
        $this->assertSame(1, (int) $template->statu);
        $this->assertNull($template->validity_date);

        $lines = QuoteLines::where('quotes_id', $template->id)->orderBy('ordre')->get();
        $this->assertCount(2, $lines);
        $this->assertSame([1, 1], $lines->pluck('statu')->map(fn ($s) => (int) $s)->all());
        $this->assertNull($lines[0]->delivery_date);
        $this->assertSame('S235', $lines[0]->QuoteLineDetails->material);
        $this->assertSame(1, Task::where('quote_lines_id', $lines[0]->id)->count());
        $this->assertSame(1, Task::where('sub_assembly_id', SubAssembly::where('quote_lines_id', $lines[0]->id)->value('id'))->count());
        $this->assertSame(1, $template->files()->count());

        // Le devis d'origine n'a pas bougé.
        $this->assertSame(2, QuoteLines::where('quotes_id', $quote->id)->count());
        $this->assertSame(3, (int) $quote->fresh()->statu);
    }

    public function test_templates_stay_out_of_quote_lists_and_indicators(): void
    {
        $quote    = $this->quoteWithContent(['statu' => 1]);
        $template = $this->saveAsTemplate($quote);

        $this->assertNull(Quotes::find($template->id));
        $this->assertSame(1, Quotes::count());

        $list = $this->getJson(route('quotes.json.list', ['statuses' => [1]]))->assertOk()->json('data');
        $this->assertSame([$quote->id], array_column($list, 'id'));

        $lines = $this->getJson(route('quote-lines.json.list'))->assertOk()->json('data');
        $this->assertEmpty(array_filter($lines, fn ($l) => $l['quotes_id'] === $template->id));

        $rates = app(QuoteKPIService::class)->getQuotesDataRate((int) now()->format('Y'));
        $this->assertSame(1, (int) $rates->sum('QuoteCountRate'));

        $templates = $this->getJson(route('quotes.json.templates'))->assertOk()->json('data');
        $this->assertSame([$template->id], array_column($templates, 'id'));
    }

    public function test_template_opens_with_the_quote_screen_and_its_lines(): void
    {
        $template = $this->saveAsTemplate($this->quoteWithContent());

        $this->get(route('quotes.show', ['id' => $template->id]))->assertOk()->assertSee('Trame armoire');
        $this->getJson(route('quotes.lines.json.for-quote', ['quoteId' => $template->id]))->assertOk();
    }

    public function test_new_quote_from_template_gets_its_lines_and_leaves_the_template_intact(): void
    {
        $template = $this->saveAsTemplate($this->quoteWithContent());
        $client   = Quotes::factory()->make();

        $response = $this->postJson(route('quotes.json.store'), [
            'code'                             => 'QT-FROM-TPL',
            'label'                            => 'Armoire client X',
            'companies_id'                     => $client->companies_id,
            'companies_contacts_id'            => $client->companies_contacts_id,
            'companies_addresses_id'           => $client->companies_addresses_id,
            'accounting_payment_conditions_id' => $client->accounting_payment_conditions_id,
            'accounting_payment_methods_id'    => $client->accounting_payment_methods_id,
            'accounting_deliveries_id'         => $client->accounting_deliveries_id,
            'user_id'                          => $this->user->id,
            'template_id'                      => $template->id,
        ]);

        $response->assertCreated();
        $quote = Quotes::where('code', 'QT-FROM-TPL')->sole();
        $this->assertFalse($quote->is_template);
        $this->assertSame($client->companies_id, $quote->companies_id);
        $this->assertSame(2, QuoteLines::where('quotes_id', $quote->id)->count());
        $this->assertSame(1, $quote->files()->count());
        $this->assertSame(2, QuoteLines::where('quotes_id', $template->id)->count());
    }

    public function test_a_quote_cannot_be_created_from_an_ordinary_quote_id(): void
    {
        $other  = $this->quoteWithContent();
        $client = Quotes::factory()->make();

        $this->postJson(route('quotes.json.store'), [
            'code'                             => 'QT-NOPE',
            'label'                            => 'x',
            'companies_id'                     => $client->companies_id,
            'companies_contacts_id'            => $client->companies_contacts_id,
            'companies_addresses_id'           => $client->companies_addresses_id,
            'accounting_payment_conditions_id' => $client->accounting_payment_conditions_id,
            'accounting_payment_methods_id'    => $client->accounting_payment_methods_id,
            'accounting_deliveries_id'         => $client->accounting_deliveries_id,
            'user_id'                          => $this->user->id,
            'template_id'                      => $other->id,
        ])->assertNotFound();

        $this->assertNull(Quotes::where('code', 'QT-NOPE')->first());
    }

    public function test_duplicating_a_quote_gives_a_fresh_draft_with_the_same_content(): void
    {
        $quote = $this->quoteWithContent(['review_decision' => 'approved']);

        $this->post(route('quotes.duplicate', ['id' => $quote->id]))->assertRedirect();

        $copy = Quotes::where('id', '!=', $quote->id)->sole();
        $this->assertNotSame($quote->code, $copy->code);
        $this->assertNotSame($quote->uuid, $copy->uuid);
        $this->assertSame(1, (int) $copy->statu);
        $this->assertNull($copy->review_decision);
        $this->assertSame($quote->companies_id, $copy->companies_id);
        $this->assertSame(2, QuoteLines::where('quotes_id', $copy->id)->count());
        // Un devis dupliqué garde les dates de ses lignes ; seule la trame les efface.
        $this->assertNotNull(QuoteLines::where('quotes_id', $copy->id)->orderBy('ordre')->first()->delivery_date);
    }

    public function test_lines_can_be_imported_from_another_quote_at_the_end(): void
    {
        $source = $this->quoteWithContent();
        $target = Quotes::factory()->create(['statu' => 1]);
        QuoteLines::factory()->create(['quotes_id' => $target->id, 'ordre' => 5]);

        $sources = $this->getJson(route('quotes.lines.json.import-sources', ['quoteId' => $target->id]))->assertOk()->json('data');
        $this->assertContains($source->id, array_column($sources, 'id'));
        $this->assertNotContains($target->id, array_column($sources, 'id'));

        $first = QuoteLines::where('quotes_id', $source->id)->orderBy('ordre')->first();
        $response = $this->postJson(route('quotes.lines.json.import-from', ['quoteId' => $target->id]), ['line_ids' => [$first->id]]);

        $response->assertCreated()->assertJsonCount(1, 'lines')->assertJsonPath('lines.0.ordre', 6);
        $copy = QuoteLines::findOrFail($response->json('lines.0.id'));
        $this->assertSame($target->id, $copy->quotes_id);
        $this->assertSame(1, (int) $copy->statu);
        $this->assertSame(1, Task::where('sub_assembly_id', SubAssembly::where('quote_lines_id', $copy->id)->value('id'))->count());
    }

    public function test_lines_cannot_be_imported_into_a_quote_that_left_draft(): void
    {
        $source = $this->quoteWithContent();
        $target = Quotes::factory()->create(['statu' => 3]);

        $this->postJson(route('quotes.lines.json.import-from', ['quoteId' => $target->id]), [
            'line_ids' => QuoteLines::where('quotes_id', $source->id)->pluck('id')->all(),
        ])->assertForbidden();
    }

    public function test_a_template_is_never_converted_into_an_order(): void
    {
        $template = $this->saveAsTemplate($this->quoteWithContent());

        $this->postJson(route('quotes.lines.json.store-order', ['quoteId' => $template->id]), [
            'line_ids' => QuoteLines::where('quotes_id', $template->id)->pluck('id')->all(),
        ])->assertStatus(422);
    }

    public function test_deleting_a_template_removes_it_and_its_lines_only(): void
    {
        $quote    = $this->quoteWithContent();
        $template = $this->saveAsTemplate($quote);

        $this->delete(route('quotes.template.destroy', ['id' => $template->id]))->assertRedirect();

        $this->assertSoftDeleted('quotes', ['id' => $template->id]);
        $this->assertSame(0, QuoteLines::where('quotes_id', $template->id)->count());
        $this->assertSame(2, QuoteLines::where('quotes_id', $quote->id)->count());

        // Un devis ordinaire ne passe pas par cette porte.
        $this->delete(route('quotes.template.destroy', ['id' => $quote->id]))->assertNotFound();
    }
}
