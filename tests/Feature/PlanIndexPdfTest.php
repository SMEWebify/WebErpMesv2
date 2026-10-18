<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\Methods\MethodsUnits;
use App\Models\User;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Documents\SalesPrintLayout;
use App\Services\OrderCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Tests\TestCase;

/**
 * L'indice du plan s'imprime sous la référence, seulement s'il existe.
 * (La non-régression du rendu sans indice est portée par SalesPdfLegacyRenderingTest.)
 */
class PlanIndexPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        app()->forgetInstance('Factory');
        app()->instance('Factory', Factory::create([
            'name' => 'Atelier Test', 'address' => '1 rue', 'zipcode' => '35000', 'city' => 'Rennes',
            'phone_number' => '0', 'mail' => 'a@b.test', 'curency' => 'EUR', 'pdf_header_font_color' => '#dddddd',
        ]));
    }

    private function orderWithLine(?string $ind): Orders
    {
        $order = Orders::factory()->create();
        OrderLines::create([
            'orders_id' => $order->id, 'ordre' => 1, 'code' => 'PIECE-9', 'ind' => $ind, 'label' => 'Pièce tournée',
            'qty' => 3, 'delivered_qty' => 0, 'delivered_remaining_qty' => 3, 'invoiced_qty' => 0, 'invoiced_remaining_qty' => 3,
            'methods_units_id' => MethodsUnits::factory()->create()->id, 'selling_price' => 10, 'discount' => 0,
            'accounting_vats_id' => AccountingVat::factory()->create(['rate' => 20])->id, 'internal_delay' => '2026-11-20',
        ]);

        return $order->fresh();
    }

    private function renderOrder(Orders $order): string
    {
        $calculator = new OrderCalculatorService($order);
        $Document   = $order;
        $Document->Lines = $Document->OrderLines;
        unset($Document->OrderLines);

        return view('print/pdf-sales', [
            'typeDocumentName'    => 'Commande',
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

    public function test_the_order_pdf_prints_the_index_under_the_reference(): void
    {
        $html = $this->renderOrder($this->orderWithLine('B'));

        $this->assertStringContainsString('PIECE-9 · ' . __('general_content.plan_index_short_trans_key') . ' B', $html);
    }

    public function test_the_order_pdf_prints_nothing_without_an_index(): void
    {
        $html = $this->renderOrder($this->orderWithLine(null));

        $this->assertStringContainsString('PIECE-9</span>', $html);
        $this->assertStringNotContainsString(__('general_content.plan_index_short_trans_key') . ' ', $html);
    }

    public function test_the_delivery_note_pdf_prints_the_snapshot_index(): void
    {
        $order = $this->orderWithLine('B');
        $line  = $order->OrderLines()->first();

        $delivery = Deliverys::factory()->create();
        DeliveryLines::create(['deliverys_id' => $delivery->id, 'order_line_id' => $line->id, 'ordre' => 1, 'qty' => 3, 'ind' => 'A', 'statu' => 1]);

        $html = view('print/pdf-delivery', [
            'typeDocumentName' => 'BL',
            'Document'         => tap($delivery->fresh(), fn ($d) => $d->Lines = $d->DeliveryLines),
            'Factory'          => app('Factory'),
            'image'            => null,
            'customCss'        => null,
        ])->render();

        // Le BL redit l'indice livré (A), pas celui, corrigé depuis, de la commande (B).
        $this->assertStringContainsString(__('general_content.plan_index_short_trans_key') . ' A', $html);
        $this->assertStringNotContainsString(__('general_content.plan_index_short_trans_key') . ' B', $html);
    }
}
