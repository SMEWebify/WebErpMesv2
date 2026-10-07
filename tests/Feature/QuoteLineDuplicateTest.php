<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\File;
use App\Models\User;
use App\Models\Planning\Task;
use App\Models\Planning\SubAssembly;
use App\Models\Planning\TaskResources;
use App\Models\Methods\MethodsRessources;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\QuoteLineDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuoteLineDuplicateTest extends TestCase
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

    private function duplicate(Quotes $quote, QuoteLines $line)
    {
        return $this->postJson(route('quotes.lines.json.duplicate', ['quoteId' => $quote->id, 'id' => $line->id]));
    }

    public function test_duplicate_copies_the_whole_bill_of_materials_onto_the_new_line(): void
    {
        $quote = Quotes::factory()->create(['statu' => 1]);
        $line  = QuoteLines::factory()->create(['quotes_id' => $quote->id, 'ordre' => 1]);
        QuoteLineDetails::create(['quote_lines_id' => $line->id, 'internal_comment' => 'note']);

        $lineTask = Task::factory()->create(['quote_lines_id' => $line->id, 'order_lines_id' => null, 'products_id' => null]);
        $resource = MethodsRessources::factory()->create();
        $lineTask->resources()->attach($resource->id, [
            'role' => TaskResources::ROLE_MACHINE, 'source' => TaskResources::SOURCE_MANUAL, 'load_factor' => 1,
        ]);

        $root  = SubAssembly::create(['ordre' => 1, 'quote_lines_id' => $line->id, 'child_id' => 1, 'qty' => 2, 'unit_price' => 10]);
        $child = SubAssembly::create(['ordre' => 1, 'sub_assembly_id' => $root->id, 'child_id' => 1, 'qty' => 3, 'unit_price' => 5]);
        Task::factory()->create(['sub_assembly_id' => $root->id, 'quote_lines_id' => null, 'order_lines_id' => null, 'products_id' => null]);
        Task::factory()->create(['sub_assembly_id' => $child->id, 'quote_lines_id' => null, 'order_lines_id' => null, 'products_id' => null]);

        $file = File::create([
            'user_id' => $this->user->id, 'name' => 'plan.pdf', 'original_file_name' => 'plan.pdf', 'type' => 'application/pdf', 'size' => '10',
        ]);
        $line->files()->attach($file->id, ['role' => 'plan', 'is_primary' => true]);

        $response = $this->duplicate($quote, $line);
        $response->assertCreated();

        $copy = QuoteLines::findOrFail($response->json('line.id'));
        $this->assertSame('note', QuoteLineDetails::where('quote_lines_id', $copy->id)->value('internal_comment'));

        $copyTask = Task::where('quote_lines_id', $copy->id)->sole();
        $this->assertSame([$resource->id], $copyTask->resources->pluck('id')->all());

        $copyRoot  = SubAssembly::where('quote_lines_id', $copy->id)->sole();
        $copyChild = SubAssembly::where('sub_assembly_id', $copyRoot->id)->sole();
        $this->assertNotSame($root->id, $copyRoot->id);
        $this->assertSame(1, Task::where('sub_assembly_id', $copyRoot->id)->count());
        $this->assertSame(1, Task::where('sub_assembly_id', $copyChild->id)->count());

        // La nomenclature d'origine n'a rien gagné ni perdu.
        $this->assertSame(1, SubAssembly::where('sub_assembly_id', $root->id)->count());
        $this->assertSame(1, Task::where('sub_assembly_id', $root->id)->count());
        $this->assertSame(1, Task::where('sub_assembly_id', $child->id)->count());

        $this->assertSame('plan', $copy->files()->first()->pivot->role);
    }

    public function test_duplicate_is_inserted_right_below_the_source_line(): void
    {
        $quote  = Quotes::factory()->create(['statu' => 1]);
        $first  = QuoteLines::factory()->create(['quotes_id' => $quote->id, 'ordre' => 1]);
        $second = QuoteLines::factory()->create(['quotes_id' => $quote->id, 'ordre' => 2]);

        $response = $this->duplicate($quote, $first);

        $response->assertCreated()->assertJsonPath('line.ordre', 2);
        $this->assertSame(3, $second->fresh()->ordre);
    }

    public function test_duplicate_is_refused_once_the_quote_left_draft(): void
    {
        $quote = Quotes::factory()->create(['statu' => 3]);
        $line  = QuoteLines::factory()->create(['quotes_id' => $quote->id]);

        $this->duplicate($quote, $line)->assertForbidden();
        $this->assertSame(1, QuoteLines::where('quotes_id', $quote->id)->count());
    }
}
