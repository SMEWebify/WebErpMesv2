<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\File;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Vignette de repli des lignes de devis / commande : quand le détail n'a pas
 * d'image (`picture`), le JSON désigne le plan de la GED que le navigateur
 * dessinera (App\Services\Files\LineThumbnailFile).
 */
class LineThumbnailFileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
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
            'name' => 'Tôlerie', 'address' => '1 rue', 'zipcode' => '35000', 'city' => 'Rennes',
            'phone_number' => '0', 'mail' => 'a@b.test', 'curency' => 'EUR', 'pdf_header_font_color' => '#dddddd',
        ]));

        $this->vat  = AccountingVat::factory()->create(['rate' => 20, 'default' => 1]);
        $this->unit = MethodsUnits::factory()->create(['code' => 'U', 'label' => 'Unité', 'default' => 1]);
        // ProductsFactory pioche une famille existante.
        \App\Models\Methods\MethodsFamilies::factory()->create();
    }

    private function quoteLine(Quotes $quote, int $ordre, array $extra = []): QuoteLines
    {
        return QuoteLines::create(array_merge([
            'quotes_id' => $quote->id, 'ordre' => $ordre, 'code' => 'P-' . $ordre, 'label' => 'Pièce ' . $ordre,
            'qty' => 1, 'selling_price' => 10, 'discount' => 0,
            'methods_units_id' => $this->unit->id, 'accounting_vats_id' => $this->vat->id,
        ], $extra));
    }

    private function file(string $name, string $kind, array $extra = []): File
    {
        return File::create(array_merge([
            'user_id' => $this->user->id, 'name' => $name, 'original_file_name' => $name,
            'type' => 'application/octet-stream', 'kind' => $kind,
            'extension' => pathinfo($name, PATHINFO_EXTENSION), 'size' => 1000,
        ], $extra));
    }

    private function linesJson(Quotes $quote): array
    {
        return collect($this->getJson(route('quotes.lines.json.for-quote', ['quoteId' => $quote->id]))
            ->assertOk()->json('lines'))->keyBy('id')->all();
    }

    public function test_a_line_without_picture_points_to_its_ged_drawing(): void
    {
        $quote = Quotes::factory()->create(['statu' => 1]);
        $line  = $this->quoteLine($quote, 1);
        $dxf   = $this->file('piece.dxf', 'cad2d');
        $line->files()->attach($dxf->id, ['role' => 'plan', 'is_primary' => true]);

        $plan = $this->linesJson($quote)[$line->id]['plan_file'];

        $this->assertSame($dxf->id, $plan['id']);
        $this->assertSame('cad2d', $plan['kind']);
        $this->assertSame('piece.dxf', $plan['name']);
        $this->assertNull($plan['thumbnail_url']);
        $this->assertSame(route('files.json.thumbnail', ['file' => $dxf->id]), $plan['thumbnail_store_url']);
    }

    public function test_the_api_picture_keeps_priority(): void
    {
        $quote = Quotes::factory()->create(['statu' => 1]);
        $line  = $this->quoteLine($quote, 1);
        QuoteLineDetails::create(['quote_lines_id' => $line->id, 'picture' => 'radquote.png']);
        $line->files()->attach($this->file('piece.dxf', 'cad2d')->id, ['role' => 'plan', 'is_primary' => true]);

        $json = $this->linesJson($quote)[$line->id];

        $this->assertSame('radquote.png', $json['picture']);
        $this->assertNull($json['plan_file']);
    }

    public function test_the_geometry_is_chosen_over_a_document_of_another_role(): void
    {
        $quote = Quotes::factory()->create(['statu' => 1]);
        $line  = $this->quoteLine($quote, 1);
        // Cahier des charges : PDF, mais pas un plan → jamais en vignette.
        $line->files()->attach($this->file('cdc.pdf', 'doc')->id, ['role' => 'autre', 'is_primary' => true]);
        $geo = $this->file('piece.geo', 'other');
        $svg = $this->file('piece.svg', 'vector');
        $line->files()->attach($geo->id, ['role' => 'plan', 'is_primary' => true]);
        $line->files()->attach($svg->id, ['role' => 'vectoriel', 'is_primary' => true]);

        $other = $this->quoteLine($quote, 2);
        $other->files()->attach($this->file('cdc2.pdf', 'doc')->id, ['role' => 'autre', 'is_primary' => true]);

        $lines = $this->linesJson($quote);

        // Le .geo n'a pas de rendu : c'est le contour SVG généré à l'import qui sert.
        $this->assertSame($svg->id, $lines[$line->id]['plan_file']['id']);
        $this->assertNull($lines[$other->id]['plan_file']);
    }

    public function test_an_already_rendered_thumbnail_wins_and_the_product_is_a_fallback(): void
    {
        $quote = Quotes::factory()->create(['statu' => 1]);
        $line  = $this->quoteLine($quote, 1);
        $pdf   = $this->file('plan.pdf', 'doc', ['thumbnail_path' => 'thumbnails/files/1.png']);
        $line->files()->attach($this->file('piece.dxf', 'cad2d')->id, ['role' => 'plan', 'is_primary' => true]);
        $line->files()->attach($pdf->id, ['role' => 'plan', 'is_primary' => false]);

        $product = Products::factory()->create();
        $step    = $this->file('piece.step', 'brep');
        $product->files()->attach($step->id, ['role' => 'modele_3d', 'is_primary' => true]);
        $withProduct = $this->quoteLine($quote, 2, ['product_id' => $product->id]);

        $lines = $this->linesJson($quote);

        $this->assertSame($pdf->id, $lines[$line->id]['plan_file']['id']);
        $this->assertNotNull($lines[$line->id]['plan_file']['thumbnail_url']);
        $this->assertSame($step->id, $lines[$withProduct->id]['plan_file']['id']);
    }

    public function test_the_query_count_does_not_grow_with_the_lines(): void
    {
        $quote = Quotes::factory()->create(['statu' => 1]);
        $product = Products::factory()->create();
        $this->quoteLine($quote, 1, ['product_id' => $product->id])
            ->files()->attach($this->file('a.dxf', 'cad2d')->id, ['role' => 'plan', 'is_primary' => true]);

        $count = function () use ($quote): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->linesJson($quote);
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $queries;
        };

        $before = $count();

        foreach (range(2, 6) as $ordre) {
            $this->quoteLine($quote, $ordre, ['product_id' => $product->id])
                ->files()->attach($this->file("p{$ordre}.dxf", 'cad2d')->id, ['role' => 'plan', 'is_primary' => true]);
        }

        $this->assertSame($before, $count());
    }

    public function test_order_lines_get_the_same_fallback(): void
    {
        $order = Orders::factory()->create(['statu' => 1, 'type' => 1]);
        $line  = OrderLines::create([
            'orders_id' => $order->id, 'ordre' => 1, 'code' => 'P-1', 'label' => 'Pièce',
            'qty' => 1, 'delivered_qty' => 0, 'delivered_remaining_qty' => 1, 'invoiced_qty' => 0, 'invoiced_remaining_qty' => 1,
            'selling_price' => 10, 'discount' => 0, 'internal_delay' => '2026-11-20',
            'methods_units_id' => $this->unit->id, 'accounting_vats_id' => $this->vat->id,
        ]);
        $dxf = $this->file('piece.dxf', 'cad2d');
        $line->files()->attach($dxf->id, ['role' => 'plan', 'is_primary' => true]);

        $withPicture = OrderLines::create($line->only([
            'orders_id', 'code', 'label', 'qty', 'delivered_qty', 'delivered_remaining_qty', 'invoiced_qty',
            'invoiced_remaining_qty', 'selling_price', 'discount', 'internal_delay', 'methods_units_id', 'accounting_vats_id',
        ]) + ['ordre' => 2]);
        OrderLineDetails::create(['order_lines_id' => $withPicture->id, 'picture' => 'n4q.png']);

        $lines = collect($this->getJson(route('orders.lines.json.for-order', ['orderId' => $order->id]))
            ->assertOk()->json('lines'))->keyBy('id');

        $this->assertSame($dxf->id, $lines[$line->id]['plan_file']['id']);
        $this->assertNull($lines[$withPicture->id]['plan_file']);
    }
}
