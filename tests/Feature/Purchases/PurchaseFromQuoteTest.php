<?php

namespace Tests\Feature\Purchases;

use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseQuotationLines;
use App\Models\Purchases\Purchases;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\User;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Affairs\AffairScope;
use App\Services\Affairs\AffairSummaryService;
use App\Services\Purchases\PurchaseAffairLink;
use App\Services\Quotes\QuoteLineToOrderLineConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Achats liés aux documents existants : demande de prix depuis un devis,
 * prix retenu pour le devis, rattachement à la conversion, lien d'en-tête.
 */
class PurchaseFromQuoteTest extends TestCase
{
    use RefreshDatabase;

    private Companies $supplier;
    private Companies $customer;
    private Opportunities $opportunity;
    private array $statuses = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $user = User::factory()->create();
        $this->actingAs($user);

        AccountingVat::factory()->create(['default' => 1]);
        foreach (['Open', 'RFQ in progress', 'Supplied'] as $order => $title) {
            $this->statuses[$title] = Status::create(['title' => $title, 'order' => $order])->id;
        }

        $this->supplier = Companies::factory()->create();
        CompaniesContacts::factory()->create(['companies_id' => $this->supplier->id, 'default' => 1]);
        CompaniesAddresses::factory()->create(['companies_id' => $this->supplier->id, 'default' => 1]);

        $this->customer = Companies::factory()->create();
        $this->opportunity = Opportunities::factory()->create([
            'companies_id'           => $this->customer->id,
            'companies_contacts_id'  => CompaniesContacts::factory()->create(['companies_id' => $this->customer->id])->id,
            'companies_addresses_id' => CompaniesAddresses::factory()->create(['companies_id' => $this->customer->id])->id,
            'user_id'                => $user->id,
            'leads_id'               => null,
            'statu'                  => 1,
        ]);
    }

    // ------------------------------------------------------------------
    // Fabriques
    // ------------------------------------------------------------------

    private function quoteLine(int $statu = 1): QuoteLines
    {
        $quote = Quotes::factory()->create([
            'companies_id'     => $this->customer->id,
            'opportunities_id' => $this->opportunity->id,
            'statu'            => $statu,
        ]);

        return QuoteLines::factory()->create(['quotes_id' => $quote->id, 'qty' => 2, 'selling_price' => 100, 'discount' => 0]);
    }

    /** Opération sous-traitée d'une ligne de devis ou de commande. */
    private function task(array $attributes): Task
    {
        return Task::factory()->create($attributes + [
            'quote_lines_id' => null,
            'order_lines_id' => null,
            'products_id'    => null,
            'sub_assembly_id'=> null,
            'component_id'   => null,
            'type'           => 7,
            'status_id'      => $this->statuses['Open'],
            'qty'            => 1,
            'planned_qty'    => null,
            'unit_cost'      => 5,
        ]);
    }

    private function quotationFor(Task $task, float $price = 12): PurchaseQuotationLines
    {
        $quotation = PurchasesQuotation::create([
            'code' => 'DP-' . uniqid(), 'label' => 'DP', 'companies_id' => $this->supplier->id,
            'companies_contacts_id'  => CompaniesContacts::where('companies_id', $this->supplier->id)->value('id'),
            'companies_addresses_id' => CompaniesAddresses::where('companies_id', $this->supplier->id)->value('id'),
            'user_id' => auth()->id(),
        ]);

        return PurchaseQuotationLines::create([
            'purchases_quotation_id' => $quotation->id, 'tasks_id' => $task->id, 'ordre' => 10,
            'label' => $task->label, 'qty_to_order' => 2, 'unit_price' => $price, 'total_price' => 2 * $price,
        ]);
    }

    private function order(): Orders
    {
        return Orders::factory()->create(['companies_id' => $this->customer->id, 'type' => 1, 'statu' => 1]);
    }

    // ------------------------------------------------------------------
    // Brique 1 : demande de prix depuis un devis
    // ------------------------------------------------------------------

    public function test_quote_source_lists_operations_of_open_quotes_only(): void
    {
        $open = $this->task(['quote_lines_id' => $this->quoteLine(1)->id]);
        $lost = $this->task(['quote_lines_id' => $this->quoteLine(4)->id]);
        $converted = $this->quoteLine(1);
        $this->task(['quote_lines_id' => $converted->id]);
        app(QuoteLineToOrderLineConverter::class)->convert($converted, $this->order()->id, 0);

        $ids = collect($this->getJson(route('purchases.request.tasks', ['source' => 'quotes']))->assertOk()->json('tasks'))
            ->pluck('id')->all();

        $this->assertSame([$open->id], $ids);
        $this->assertNotContains($lost->id, $ids);
    }

    public function test_request_for_quotation_from_a_quote_is_linked_to_the_quote(): void
    {
        $quoteLine = $this->quoteLine();
        $task = $this->task(['quote_lines_id' => $quoteLine->id]);

        $this->postJson(route('purchases.request.store'), [
            'document_type' => 'PQ', 'code' => 'GRP-1', 'label' => 'Chiffrage',
            'selected_companies' => [$this->supplier->id], 'task_ids' => [$task->id],
        ])->assertOk();

        $quotation = PurchasesQuotation::sole();
        $this->assertSame($quoteLine->quotes_id, (int) $quotation->quotes_id);
        $this->assertSame($task->id, (int) PurchaseQuotationLines::sole()->tasks_id);
        $this->assertSame($this->statuses['RFQ in progress'], (int) $task->fresh()->status_id);
    }

    public function test_no_purchase_order_on_a_quote_operation(): void
    {
        $task = $this->task(['quote_lines_id' => $this->quoteLine()->id]);

        $this->postJson(route('purchases.request.store'), [
            'document_type' => 'PU', 'code' => 'PU-1', 'label' => 'PU',
            'companies_id' => $this->supplier->id, 'task_ids' => [$task->id],
        ])->assertStatus(422);

        $line = $this->quotationFor($task);
        $this->post(route('purchases.orders.store', ['id' => $line->purchases_quotation_id]), [
            'PurchaseQuotationLine' => [$line->id],
        ])->assertSessionHas('error');

        $this->assertSame(0, Purchases::count());
    }

    // ------------------------------------------------------------------
    // Brique 2 : prix retenu pour le devis
    // ------------------------------------------------------------------

    public function test_retaining_an_answer_sets_the_quote_operation_cost(): void
    {
        $task = $this->task(['quote_lines_id' => $this->quoteLine()->id]);
        $first = $this->quotationFor($task, 12);
        $second = $this->quotationFor($task, 9.5);

        $this->postJson(route('purchases.quotation.api.lines.retain', ['quotation' => $first->purchases_quotation_id, 'line' => $first->id]))
            ->assertOk();
        $this->postJson(route('purchases.quotation.api.lines.retain', ['quotation' => $second->purchases_quotation_id, 'line' => $second->id]))
            ->assertOk()
            ->assertJsonPath('lines.0.retained', true);

        $this->assertEquals(9.5, (float) $task->fresh()->unit_cost);
        $this->assertNull($first->fresh()->retained_at);
        $this->assertNotNull($second->fresh()->retained_at);
    }

    public function test_retaining_is_refused_on_a_won_quote_or_an_order_operation(): void
    {
        $won = $this->quotationFor($this->task(['quote_lines_id' => $this->quoteLine(3)->id]));
        $this->postJson(route('purchases.quotation.api.lines.retain', ['quotation' => $won->purchases_quotation_id, 'line' => $won->id]))
            ->assertStatus(422);

        $orderTask = $this->task(['order_lines_id' => \App\Models\Workflow\OrderLines::factory()->create(['orders_id' => $this->order()->id])->id]);
        $onOrder = $this->quotationFor($orderTask);
        $this->postJson(route('purchases.quotation.api.lines.retain', ['quotation' => $onOrder->purchases_quotation_id, 'line' => $onOrder->id]))
            ->assertStatus(422);

        $this->assertEquals(5, (float) $orderTask->fresh()->unit_cost);
    }

    // ------------------------------------------------------------------
    // Brique 3 : rattachement à la conversion
    // ------------------------------------------------------------------

    public function test_quotation_lines_follow_the_operations_when_the_quote_is_ordered(): void
    {
        $quoteLine = $this->quoteLine();
        $task = $this->task(['quote_lines_id' => $quoteLine->id]);
        $line = $this->quotationFor($task);

        $orderLine = app(QuoteLineToOrderLineConverter::class)->convert($quoteLine, $this->order()->id, 0);

        $orderTask = Task::where('order_lines_id', $orderLine->id)->sole();
        $this->assertSame($orderTask->id, (int) $line->fresh()->tasks_id);

        // Devenue opération de commande ferme : la commande d'achat est possible
        $this->post(route('purchases.orders.store', ['id' => $line->purchases_quotation_id]), [
            'PurchaseQuotationLine' => [$line->id],
        ])->assertRedirect();
        $this->assertSame($orderTask->id, (int) PurchaseLines::sole()->tasks_id);
    }

    // ------------------------------------------------------------------
    // Brique 4 : rattachement d'en-tête
    // ------------------------------------------------------------------

    public function test_header_link_values_are_parsed_and_checked(): void
    {
        $link = app(PurchaseAffairLink::class);
        $order = $this->order();

        $this->assertSame(['opportunities_id' => null, 'quotes_id' => null, 'orders_id' => $order->id], $link->attributes('order:' . $order->id));
        $this->assertSame(['opportunities_id' => null, 'quotes_id' => null, 'orders_id' => null], $link->attributes(''));

        $this->expectException(ValidationException::class);
        $link->attributes('order:999999');
    }

    public function test_quotation_header_link_is_saved_from_the_screen(): void
    {
        $line = $this->quotationFor($this->task(['quote_lines_id' => $this->quoteLine()->id]));
        $quotation = $line->purchaseQuotation;

        $this->patchJson(route('purchases.quotation.api.update', ['quotation' => $quotation->id]), [
            'label' => 'DP', 'affair_link' => 'opportunity:' . $this->opportunity->id,
        ])->assertOk();
        $this->assertSame($this->opportunity->id, (int) $quotation->fresh()->opportunities_id);

        // Sans le champ : inchangé
        $this->patchJson(route('purchases.quotation.api.update', ['quotation' => $quotation->id]), ['label' => 'DP2'])->assertOk();
        $this->assertSame($this->opportunity->id, (int) $quotation->fresh()->opportunities_id);
    }

    public function test_a_purchase_linked_by_its_header_belongs_to_the_affair(): void
    {
        $purchase = Purchases::factory()->create(['statu' => 2, 'opportunities_id' => $this->opportunity->id]);
        $line = PurchaseLines::create([
            'purchases_id' => $purchase->id, 'tasks_id' => 0, 'ordre' => 1, 'label' => 'Outillage dédié',
            'qty' => 1, 'selling_price' => 300, 'discount' => 0, 'unit_price_after_discount' => 300,
            'total_selling_price' => 300, 'receipt_qty' => 0,
        ]);
        Purchases::factory()->create(['statu' => 2]); // achat d'une autre affaire

        $this->assertSame([$line->id], (new AffairScope($this->opportunity))->purchaseLineIds());

        $summary = (new AffairSummaryService())->summarize($this->opportunity->fresh());
        $this->assertSame([$purchase->id], array_column($summary['sections']['purchases'], 'id'));
    }
}
