<?php

namespace Tests\Feature;

use App\Casts\EquipmentTags;
use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\Methods\MethodsUnits;
use App\Models\User;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Documents\ProductionSheetBuilder;
use App\Services\Documents\SalesPrintLayout;
use App\Services\OrderCalculatorService;
use App\Services\OrderConfirmationCalculatorService;
use App\Services\OrderConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Tests\TestCase;

/**
 * Repères d'équipement (tags) : saisie, propagation devis → commande → ARC,
 * recherche dans l'index des commandes, impression seulement s'ils existent.
 * (La non-régression des PDF sans repère est aussi portée par
 * SalesPdfLegacyRenderingTest.)
 */
class EquipmentTagTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    private AccountingVat $vat;
    private MethodsUnits $unit;

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

        app()->forgetInstance('Factory');
        app()->instance('Factory', Factory::create([
            'name' => 'Chaudronnerie', 'address' => '1 rue', 'zipcode' => '35000', 'city' => 'Rennes',
            'phone_number' => '0', 'mail' => 'a@b.test', 'curency' => 'EUR', 'pdf_header_font_color' => '#dddddd',
        ]));

        $this->vat  = AccountingVat::factory()->create(['rate' => 20, 'default' => 1]);
        $this->unit = MethodsUnits::factory()->create(['code' => 'U', 'label' => 'Unité', 'default' => 1]);
    }

    // ---------------------------------------------------------------------
    // Fixtures
    // ---------------------------------------------------------------------

    private function quoteLine(Quotes $quote, int $ordre, array $extra = []): QuoteLines
    {
        return QuoteLines::create(array_merge([
            'quotes_id' => $quote->id, 'ordre' => $ordre, 'code' => 'VAN-' . $ordre, 'label' => 'Vanne ' . $ordre,
            'qty' => 2, 'selling_price' => 100, 'discount' => 0,
            'methods_units_id' => $this->unit->id, 'accounting_vats_id' => $this->vat->id,
        ], $extra));
    }

    private function orderLine(Orders $order, int $ordre, array $extra = []): OrderLines
    {
        return OrderLines::create(array_merge([
            'orders_id' => $order->id, 'ordre' => $ordre, 'code' => 'VAN-' . $ordre, 'label' => 'Vanne ' . $ordre,
            'qty' => 2, 'delivered_qty' => 0, 'delivered_remaining_qty' => 2, 'invoiced_qty' => 0, 'invoiced_remaining_qty' => 2,
            'selling_price' => 100, 'discount' => 0, 'internal_delay' => '2026-11-20',
            'methods_units_id' => $this->unit->id, 'accounting_vats_id' => $this->vat->id,
        ], $extra));
    }

    private function order(array $attributes = []): Orders
    {
        return Orders::factory()->create(['statu' => 1, 'type' => 1] + $attributes);
    }

    // ---------------------------------------------------------------------
    // Stockage
    // ---------------------------------------------------------------------

    public function test_tags_are_normalised_and_an_empty_list_is_stored_as_null(): void
    {
        $line = $this->orderLine($this->order(), 1, ['equipment_tag' => " 1RCP/001 ;1RCP/002\n1RCP/001,, "]);

        $this->assertSame(['1RCP/001', '1RCP/002'], $line->fresh()->equipment_tag);
        // Ni « / » échappé ni accent encodé : la recherche LIKE porte sur le texte brut.
        $this->assertSame('["1RCP/001","1RCP/002"]', DB::table('order_lines')->where('id', $line->id)->value('equipment_tag'));

        $line->update(['equipment_tag' => ['  ', '']]);
        $this->assertNull(DB::table('order_lines')->where('id', $line->id)->value('equipment_tag'));
        $this->assertSame([], $line->fresh()->equipment_tag);
    }

    public function test_a_line_saved_before_the_feature_has_no_tag(): void
    {
        $line = $this->orderLine($this->order(), 1);

        $this->assertSame([], $line->fresh()->equipment_tag);
        $this->assertSame('', $line->fresh()->equipmentTagsText());
    }

    public function test_a_presentation_line_never_carries_a_tag(): void
    {
        $line = $this->quoteLine(Quotes::factory()->create(['statu' => 1]), 1, [
            'line_type' => 'section', 'equipment_tag' => ['TAG-1'],
        ]);

        $this->assertNull(DB::table('quote_lines')->where('id', $line->id)->value('equipment_tag'));
    }

    // ---------------------------------------------------------------------
    // Saisie (écrans de lignes)
    // ---------------------------------------------------------------------

    public function test_tags_are_entered_on_quote_and_order_lines(): void
    {
        $quote = Quotes::factory()->create(['statu' => 1, 'user_id' => $this->user->id]);
        $payload = ['ordre' => 1, 'label' => 'Vanne', 'qty' => 2, 'selling_price' => 10, 'discount' => 0];

        $created = $this->postJson(route('quotes.lines.json.store', ['quoteId' => $quote->id]), $payload + ['equipment_tag' => ['1RCV001VP', '1RCV002VP']])
            ->assertCreated()
            ->json('line');
        $this->assertSame(['1RCV001VP', '1RCV002VP'], $created['equipment_tag']);

        $this->putJson(route('quotes.lines.json.update', ['quoteId' => $quote->id, 'id' => $created['id']]), $payload + ['equipment_tag' => ['1RCV003VP']])
            ->assertOk()
            ->assertJsonPath('line.equipment_tag', ['1RCV003VP']);

        // Création d'une ligne de commande par l'écran : impossible sous SQLite tant que
        // order_lines.delivered_qty n'a pas retrouvé son default(0) (cf. CLAUDE.md, « À vérifier »).
        $order = $this->order();
        $line  = $this->orderLine($order, 1);
        $this->putJson(route('orders.lines.json.update', ['orderId' => $order->id, 'id' => $line->id]), $payload + ['equipment_tag' => ['2RRA001PO']])
            ->assertOk()
            ->assertJsonPath('line.equipment_tag', ['2RRA001PO']);
        $this->assertSame(['2RRA001PO'], $line->fresh()->equipment_tag);

        // Un enregistrement qui n'envoie pas le champ ne l'efface pas.
        $this->putJson(route('orders.lines.json.update', ['orderId' => $order->id, 'id' => $line->id]), ['label' => 'Vanne modifiée'] + $payload)
            ->assertOk()
            ->assertJsonPath('line.equipment_tag', ['2RRA001PO']);
    }

    public function test_an_oversized_tag_is_refused(): void
    {
        $quote = Quotes::factory()->create(['statu' => 1]);

        $this->postJson(route('quotes.lines.json.store', ['quoteId' => $quote->id]), [
            'ordre' => 1, 'label' => 'Vanne', 'qty' => 1, 'selling_price' => 10, 'discount' => 0,
            'equipment_tag' => [str_repeat('X', 101)],
        ])->assertUnprocessable()->assertJsonValidationErrors('equipment_tag.0');

        $order = $this->order();
        $line  = $this->orderLine($order, 1);
        $this->putJson(route('orders.lines.json.update', ['orderId' => $order->id, 'id' => $line->id]), [
            'ordre' => 1, 'label' => 'Vanne', 'qty' => 1, 'selling_price' => 10, 'discount' => 0,
            'equipment_tag' => 'pas une liste',
        ])->assertUnprocessable()->assertJsonValidationErrors('equipment_tag');
    }

    public function test_duplicating_a_line_does_not_copy_its_tags(): void
    {
        $order = $this->order();
        $line  = $this->orderLine($order, 1, ['equipment_tag' => ['1RCP001PO']]);

        $copy = $this->postJson(route('orders.lines.json.duplicate', ['orderId' => $order->id, 'id' => $line->id]))
            ->assertCreated()
            ->json('line');
        $this->assertSame([], $copy['equipment_tag']);
        $this->assertSame(['1RCP001PO'], $line->fresh()->equipment_tag);

        $quote     = Quotes::factory()->create(['statu' => 1]);
        $quoteLine = $this->quoteLine($quote, 1, ['equipment_tag' => ['1RCP001PO']]);
        $this->postJson(route('quotes.lines.json.duplicate', ['quoteId' => $quote->id, 'id' => $quoteLine->id]))
            ->assertCreated()
            ->assertJsonPath('line.equipment_tag', []);
    }

    // ---------------------------------------------------------------------
    // Propagation
    // ---------------------------------------------------------------------

    public function test_tags_follow_the_quote_into_the_order_and_the_order_confirmation(): void
    {
        $quote  = Quotes::factory()->create(['statu' => 1, 'user_id' => $this->user->id]);
        $tagged = $this->quoteLine($quote, 1, ['equipment_tag' => ['1RCP001PO', '1RCP002PO']]);
        $plain  = $this->quoteLine($quote, 2);

        $this->postJson(route('quotes.lines.json.store-order', ['quoteId' => $quote->id]), ['line_ids' => [$tagged->id, $plain->id]])
            ->assertOk();

        $order = Orders::where('quotes_id', $quote->id)->firstOrFail();
        $lines = $order->OrderLines()->orderBy('ordre')->get();
        $this->assertSame(['1RCP001PO', '1RCP002PO'], $lines[0]->equipment_tag);
        $this->assertSame([], $lines[1]->equipment_tag);

        $confirmation = app(OrderConfirmationService::class)->createFromOrder($order);
        $frozen       = $confirmation->OrderConfirmationLines()->orderBy('ordre')->get();
        $this->assertSame(['1RCP001PO', '1RCP002PO'], $frozen[0]->equipment_tag);
        $this->assertSame([], $frozen[1]->equipment_tag);

        // L'ARC est figé : un repère corrigé ensuite sur la commande ne le modifie pas.
        $lines[0]->update(['equipment_tag' => ['1RCP009PO']]);
        $this->assertSame(['1RCP001PO', '1RCP002PO'], $frozen[0]->fresh()->equipment_tag);
    }

    // ---------------------------------------------------------------------
    // Recherche
    // ---------------------------------------------------------------------

    public function test_the_order_index_finds_an_order_by_one_of_its_tags(): void
    {
        $nuclear = $this->order(['label' => 'Tranche 2']);
        $this->orderLine($nuclear, 1, ['equipment_tag' => ['2RCP001PO', '2RCP002PO']]);
        $this->orderLine($nuclear, 2, ['equipment_tag' => ['2RRA010VP']]);
        $other = $this->order(['label' => 'Garde-corps']);
        $this->orderLine($other, 1);

        $search = fn (string $q) => collect($this->getJson(route('orders.json.list', ['search' => $q, 'statuses' => [1]]))->assertOk()->json('data'));

        // Recherche partielle, insensible à la casse.
        $found = $search('2rcp00');
        $this->assertSame([$nuclear->id], $found->pluck('id')->all());
        $this->assertSame(['2RCP001PO', '2RCP002PO'], $found[0]['matched_equipment_tags']);

        // La recherche par libellé fonctionne comme avant, sans repère affiché.
        $byLabel = $search('Garde');
        $this->assertSame([$other->id], $byLabel->pluck('id')->all());
        $this->assertSame([], $byLabel[0]['matched_equipment_tags']);

        $this->assertCount(0, $search('9ZZZ'));
    }

    public function test_like_wildcards_in_the_search_are_taken_literally(): void
    {
        $order = $this->order(['label' => 'Tranche 1']);
        $this->orderLine($order, 1, ['equipment_tag' => ['1RCP001PO']]);

        $ids = fn (string $q) => collect($this->getJson(route('orders.json.list', ['search' => $q, 'statuses' => [1]]))->json('data'))->pluck('id')->all();

        $this->assertSame([], $ids('1RCP_01'));
        $this->assertSame([], $ids('1RCP%PO'));
        $this->assertSame([$order->id], $ids('RCP001'));
    }

    // ---------------------------------------------------------------------
    // Impression
    // ---------------------------------------------------------------------

    private function renderSales($Document, $calculator, string $linesKey): string
    {
        $Document->Lines = $Document->$linesKey;
        unset($Document->$linesKey);

        return view('print/pdf-sales', [
            'typeDocumentName'    => 'Document',
            'Document'            => $Document,
            'Factory'             => app('Factory'),
            'formattedTotalPrice' => Number::currency($calculator->getTotalPrice(), 'EUR', config('app.locale')),
            'formattedSubPrice'   => Number::currency($calculator->getSubTotal(), 'EUR', config('app.locale')),
            'vatPrice'            => $calculator->getVatTotal(),
            'image'               => null,
            'customCss'           => null,
            'normalizeCurrency'   => fn ($v) => (string) $v,
            'printRows'           => app(SalesPrintLayout::class)->apply($Document),
        ])->render();
    }

    private function renderDelivery(Orders $order): string
    {
        $delivery = Deliverys::factory()->create();
        foreach ($order->OrderLines as $i => $line) {
            DeliveryLines::create(['deliverys_id' => $delivery->id, 'order_line_id' => $line->id, 'ordre' => $i + 1, 'qty' => 2, 'statu' => 1]);
        }

        return view('print/pdf-delivery', [
            'typeDocumentName' => 'BL',
            'Document'         => tap($delivery->fresh(), fn ($d) => $d->Lines = $d->DeliveryLines),
            'Factory'          => app('Factory'),
            'image'            => null,
            'customCss'        => null,
        ])->render();
    }

    public function test_the_order_confirmation_and_delivery_note_print_the_tags(): void
    {
        $order = $this->order();
        $this->orderLine($order, 1, ['equipment_tag' => ['1RCP001PO', '1RCP002PO']]);
        $this->orderLine($order, 2);
        $label = __('general_content.equipment_tag_trans_key') . ' : 1RCP001PO, 1RCP002PO';

        $confirmation = app(OrderConfirmationService::class)->createFromOrder($order->fresh());
        $html = $this->renderSales($confirmation->fresh(), new OrderConfirmationCalculatorService($confirmation->fresh()), 'OrderConfirmationLines');
        $this->assertSame(1, substr_count($html, $label));

        $html = $this->renderSales($order->fresh(), new OrderCalculatorService($order->fresh()), 'OrderLines');
        $this->assertSame(1, substr_count($html, $label));

        $html = $this->renderDelivery($order->fresh());
        $this->assertSame(1, substr_count($html, $label));
        // La ligne sans repère garde son rendu : libellé directement suivi de la fin de cellule.
        $this->assertStringContainsString('Vanne 2</td>', $html);
    }

    public function test_documents_without_tags_print_no_tag_mention(): void
    {
        $order = $this->order();
        $this->orderLine($order, 1);
        $label = __('general_content.equipment_tag_trans_key');

        $sales = $this->renderSales($order->fresh(), new OrderCalculatorService($order->fresh()), 'OrderLines');
        $this->assertStringNotContainsString($label, $sales);
        $this->assertStringContainsString("VAN-1</span>\n                        </td>", str_replace("\r\n", "\n", $sales));

        $delivery = $this->renderDelivery($order->fresh());
        $this->assertStringNotContainsString($label, $delivery);
        $this->assertStringContainsString('Vanne 1</td>', $delivery);
    }

    public function test_the_production_sheet_prints_the_tags_only_when_present(): void
    {
        $order = $this->order();
        $this->orderLine($order, 1, ['equipment_tag' => ['1RCP001PO', '1RCP002PO']]);
        $this->orderLine($order, 2);

        $data = app(ProductionSheetBuilder::class)->build($order->fresh());
        $this->assertSame(['1RCP001PO', '1RCP002PO'], $data['sheets'][0]['equipment_tags']);
        $this->assertSame([], $data['sheets'][1]['equipment_tags']);

        $html = view('print/production-sheet', $data)->render();
        $this->assertSame(1, substr_count($html, __('production_sheet.fields.equipment_tags')));
        $this->assertStringContainsString('<strong>1RCP001PO, 1RCP002PO</strong>', $html);
        // L'image de la pièce s'étend sur la ligne ajoutée, et seulement sur cette fiche.
        $this->assertSame(1, substr_count($html, 'rowspan="7"'));
        $this->assertSame(1, substr_count($html, 'rowspan="6"'));
    }

    public function test_normalisation_ignores_nested_values(): void
    {
        $this->assertSame(['A', 'B'], EquipmentTags::normalize(['A', ' B ', 'A', null, ['nested']]));
        $this->assertSame([], EquipmentTags::normalize(null));
    }
}
