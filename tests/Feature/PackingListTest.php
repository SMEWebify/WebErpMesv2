<?php

namespace Tests\Feature;

use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Models\Methods\MethodsUnits;
use App\Models\User;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Packaging;
use App\Models\Workflow\PackagingLine;
use App\Services\Documents\DocumentPdfService;
use App\Services\Documents\PdfLayoutRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Liste de colisage du BL : modèle « packing-list » du menu d'impression, contenu
 * des colis saisi sur la fiche BL (PackagingContent). Le BL par défaut est couvert
 * par SalesPdfLegacyRenderingTest.
 */
class PackingListTest extends TestCase
{
    use RefreshDatabase;

    private Deliverys $delivery;
    private DeliveryLines $railing;
    private DeliveryLines $handrail;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        app()->forgetInstance('Factory');
        app()->instance('Factory', Factory::create([
            'name' => 'Métallerie Dupont', 'curency' => 'EUR', 'pdf_header_font_color' => '#dddddd',
        ]));

        [$this->delivery, $this->railing, $this->handrail] = $this->makeDelivery();
    }

    // --- PDF -----------------------------------------------------------------

    public function test_the_packing_list_prints_each_package_its_content_and_the_totals(): void
    {
        $crate  = $this->package('CAISSE-1', net: 40, gross: 50);
        $pallet = $this->package('PAL-2', net: 35, gross: 42.5);
        PackagingLine::create(['packaging_id' => $crate->id,  'delivery_line_id' => $this->railing->id,  'qty' => 6]);
        PackagingLine::create(['packaging_id' => $pallet->id, 'delivery_line_id' => $this->railing->id,  'qty' => 4]);
        PackagingLine::create(['packaging_id' => $pallet->id, 'delivery_line_id' => $this->handrail->id, 'qty' => 5]);

        $html = app(DocumentPdfService::class)->html($this->delivery->fresh(), 'packing-list', 'en');

        $this->assertStringContainsString('<h2>Packing list</h2>', $html);
        $this->assertStringContainsString('Package 1 / 2', $html);
        $this->assertStringContainsString('Package 2 / 2', $html);
        $this->assertStringContainsString('CAISSE-1', $html);
        $this->assertStringContainsString('1,200 × 800 × 600', $html);
        $this->assertStringContainsString('Number of packages</strong> : 2', $html);
        $this->assertStringContainsString('Total net weight</strong> : 75 kg', $html);
        $this->assertStringContainsString('Total gross weight</strong> : 92.5 kg', $html);
        $this->assertStringContainsString('1RCP/001, 1RCP/002', $html, "Repères d'équipement de la ligne.");

        // Main courante : 5 colisés sur 8 livrés → 3 hors colis ; garde-corps entièrement colisé.
        $unpacked = substr($html, strpos($html, 'Not packed'));
        $this->assertStringContainsString('Main courante inox', $unpacked);
        $this->assertMatchesRegularExpression('#Main courante inox.*?<td align="center">3</td>#s', $unpacked);
        $this->assertStringNotContainsString('Garde-corps acier', $unpacked);
    }

    public function test_a_delivery_note_without_package_lists_every_line_as_not_packed(): void
    {
        $html = app(DocumentPdfService::class)->html($this->delivery->fresh(), 'packing-list', 'fr');

        $this->assertStringContainsString('<h2>Liste de colisage</h2>', $html);
        $this->assertStringContainsString(__('packing_list.no_package', [], 'fr'), $html);
        $this->assertStringContainsString('Nombre de colis</strong> : 0', $html);
        $this->assertStringContainsString('Hors colis', $html);
        $this->assertMatchesRegularExpression('#Garde-corps acier.*?<td align="center">10</td>#s', $html);
        $this->assertMatchesRegularExpression('#Main courante inox.*?<td align="center">8</td>#s', $html);
    }

    public function test_the_packing_list_prints_no_amount(): void
    {
        $html = app(DocumentPdfService::class)->html($this->delivery->fresh(), 'packing-list', 'fr');

        $this->assertStringNotContainsString('€', $html);
    }

    public function test_the_print_route_offers_the_packing_list_and_keeps_the_delivery_note_by_default(): void
    {
        $url = fn (array $query) => route('pdf.delivery', ['Document' => $this->delivery->id] + $query);

        $this->get($url([]))->assertOk()->assertHeader('content-disposition', 'attachment; filename=DN-0001.pdf');
        $this->get($url(['layout' => 'packing-list', 'lang' => 'fr']))->assertOk();
        $this->get($url(['layout' => 'long']))->assertNotFound();
        $this->get($url(['layout' => 'unknown']))->assertNotFound();
        $this->get($url(['lang' => 'de']))->assertNotFound();
    }

    public function test_the_packing_list_is_offered_for_the_delivery_note_only(): void
    {
        $registry = new PdfLayoutRegistry();

        $this->assertSame(['short', 'packing-list'], array_keys($registry->available('print/pdf-delivery')));
        $this->assertArrayNotHasKey('packing-list', $registry->available('print/pdf-sales'));

        $this->blade('<x-pdf-print-menu route="/print/delivery/1" base="print/pdf-delivery" />')
            ->assertSee(__('packing_list.delivery_note'))
            ->assertSee(__('packing_list.layout'));
    }

    // --- Saisie du contenu ----------------------------------------------------

    public function test_a_package_is_created_with_its_content(): void
    {
        $this->post(route('deliverys.packagings.store', ['id' => $this->delivery->id]), $this->packageForm() + [
            'deliverys_id' => $this->delivery->id,
            'content'      => [$this->railing->id => 6, $this->handrail->id => ''],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $package = Packaging::sole();
        $this->assertSame([$this->railing->id => 6.0], $package->lines()->pluck('qty', 'delivery_line_id')->all());
    }

    public function test_a_line_cannot_be_packed_beyond_its_delivered_quantity(): void
    {
        $crate = $this->package('CAISSE-1', net: 40, gross: 50);
        PackagingLine::create(['packaging_id' => $crate->id, 'delivery_line_id' => $this->railing->id, 'qty' => 6]);

        // 6 déjà colisés sur 10 : 5 de plus dans un nouveau colis, refusé, et le colis n'est pas créé.
        $this->post(route('deliverys.packagings.store', ['id' => $this->delivery->id]), $this->packageForm() + [
            'deliverys_id' => $this->delivery->id,
            'content'      => [$this->railing->id => 5],
        ])->assertSessionHasErrors("content.{$this->railing->id}");

        $this->assertSame(1, Packaging::count());
        $this->assertSame(1, PackagingLine::count());
    }

    public function test_updating_a_package_replaces_its_content_within_the_delivered_quantity(): void
    {
        $crate = $this->package('CAISSE-1', net: 40, gross: 50);
        PackagingLine::create(['packaging_id' => $crate->id, 'delivery_line_id' => $this->railing->id, 'qty' => 6]);
        $update = fn (array $content) => $this->post(
            route('deliverys.packagings.update', ['id' => $this->delivery->id]),
            $this->packageForm() + ['id' => $crate->id, 'content' => $content]
        );

        // La quantité déjà dans ce colis ne compte pas contre lui-même : 10 sur 10.
        $update([$this->railing->id => 10, $this->handrail->id => 8])->assertSessionHasNoErrors();
        $this->assertEqualsCanonicalizing([10.0, 8.0], $crate->lines()->pluck('qty')->all());

        $update([$this->railing->id => 11])->assertSessionHasErrors("content.{$this->railing->id}");
        $this->assertSame(10.0, (float) $crate->lines()->where('delivery_line_id', $this->railing->id)->value('qty'));

        // Quantité vidée : la ligne sort du colis.
        $update([$this->handrail->id => ''])->assertSessionHasNoErrors();
        $this->assertSame([$this->railing->id], $crate->lines()->pluck('delivery_line_id')->all());
    }

    public function test_a_line_of_another_delivery_note_cannot_be_packed(): void
    {
        [, $foreignLine] = $this->makeDelivery('DN-0002');

        $this->post(route('deliverys.packagings.store', ['id' => $this->delivery->id]), $this->packageForm() + [
            'deliverys_id' => $this->delivery->id,
            'content'      => [$foreignLine->id => 1],
        ])->assertSessionHasErrors("content.{$foreignLine->id}");

        $this->assertSame(0, Packaging::count());
    }

    public function test_a_package_of_another_delivery_note_cannot_be_updated_through_this_one(): void
    {
        [$other] = $this->makeDelivery('DN-0002');
        $foreign = Packaging::create($this->packageForm() + ['deliverys_id' => $other->id, 'user_id' => auth()->id()]);

        $this->post(route('deliverys.packagings.update', ['id' => $this->delivery->id]), $this->packageForm() + [
            'id' => $foreign->id, 'code' => 'PIRATE',
        ])->assertNotFound();

        $this->assertSame('C-NEW', $foreign->fresh()->code);
    }

    public function test_the_delivery_note_page_shows_the_content_fields_and_the_print_menu(): void
    {
        $this->package('CAISSE-1', net: 40, gross: 50);

        $this->get(route('deliverys.show', ['id' => $this->delivery->id]))
            ->assertOk()
            ->assertSee('name="content[' . $this->railing->id . ']"', false)
            ->assertSee(__('packing_list.layout'));
    }

    // -------------------------------------------------------------------------

    private function package(string $code, float $net, float $gross): Packaging
    {
        return Packaging::create([
            'deliverys_id' => $this->delivery->id, 'code' => $code, 'type' => 'Caisse bois', 'user_id' => auth()->id(),
            'net_weight' => $net, 'gross_weight' => $gross, 'length' => 1200, 'width' => 800, 'height' => 600,
        ]);
    }

    private function packageForm(): array
    {
        return [
            'code' => 'C-NEW', 'type' => 'Palette', 'gross_weight' => 20, 'net_weight' => 15,
            'length' => 1200, 'width' => 800, 'height' => 500,
        ];
    }

    /**
     * BL de deux lignes : garde-corps 10 livrés (repères), main courante 8 livrés.
     *
     * @return array{0: Deliverys, 1: DeliveryLines, 2: DeliveryLines}
     */
    private function makeDelivery(string $code = 'DN-0001'): array
    {
        $company = Companies::factory()->create();
        $unit    = MethodsUnits::factory()->create(['code' => 'U' . $code, 'label' => 'Unité']);
        $order   = Orders::factory()->create(['companies_id' => $company->id, 'code' => 'OR-' . $code, 'statu' => 1, 'type' => 1]);

        $delivery = Deliverys::factory()->create([
            'code' => $code, 'label' => 'Escalier', 'companies_id' => $company->id, 'statu' => 1,
            'user_id' => auth()->id(), 'comment' => null, 'tracking_number' => null,
        ]);

        $lines = [];
        foreach ([['GC-01', 'Garde-corps acier', 10, ['1RCP/001', '1RCP/002']], ['MC-02', 'Main courante inox', 8, null]] as $i => [$lineCode, $label, $qty, $tags]) {
            $orderLine = OrderLines::factory()->create([
                'orders_id' => $order->id, 'ordre' => $i + 1, 'code' => $lineCode, 'label' => $label, 'qty' => $qty,
                'methods_units_id' => $unit->id, 'delivered_qty' => $qty, 'delivered_remaining_qty' => 0,
                'invoiced_qty' => 0, 'invoiced_remaining_qty' => $qty, 'equipment_tag' => $tags,
            ]);
            $lines[] = DeliveryLines::create([
                'deliverys_id' => $delivery->id, 'order_line_id' => $orderLine->id, 'ordre' => $i + 1, 'qty' => $qty, 'statu' => 1,
            ]);
        }

        return [$delivery, ...$lines];
    }
}
