<?php

namespace Tests\Feature;

use App\Models\File;
use App\Services\Documents\ProductionSheetBuilder;
use App\Services\Files\FileKindResolver;
use App\Services\Files\FileRole;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Rendu de la fiche suiveuse (print/production-sheet) à partir des tableaux
 * préparés par ProductionSheetBuilder : aucune base nécessaire.
 */
class ProductionSheetRenderingTest extends TestCase
{
    public function test_traveler_renders_a_cover_then_one_sheet_per_line(): void
    {
        $html = view('print/production-sheet', [
            'orderCode' => 'CMD-2026-042',
            'customerName' => 'ACME',
            'customerReference' => 'PO-77',
            'generatedAt' => Carbon::parse('2026-10-01 08:00'),
            'orderQrSrc' => 'data:image/png;base64,ORDERQR',
            'sheets' => collect([
                $this->sheet([
                    'code' => 'LINE-TRAVELER-1',
                    'material' => 'INOX 304L',
                    'thickness' => 2,
                    'x_size' => 3000,
                    'y_size' => 1500,
                    'operations' => [[
                        'ordre' => 10,
                        'label' => 'Pliage',
                        'service' => 'PLI',
                        'workcenter' => 'Plieuse 1',
                        'planned_time_min' => 90,
                        'barcode_src' => 'data:image/png;base64,TASKBARCODE',
                    ]],
                    'components' => [[
                        'ordre' => 1,
                        'label' => 'Tôle',
                        'component' => 'TOLE-304-2',
                        'qty' => 1.5,
                    ]],
                ]),
                $this->sheet([
                    'code' => 'LINE-TRAVELER-2',
                    'operations' => [[
                        'ordre' => 10,
                        'label' => 'Découpe',
                        'service' => null,
                        'workcenter' => null,
                        'planned_time_min' => 15,
                        'barcode_src' => null,
                    ]],
                ]),
            ]),
            'totalRequired' => 17.0,
            'totalDelivered' => 0.0,
        ])->render();

        // Page de garde commande en tête, puis une fiche par ligne.
        $this->assertSame(1, substr_count($html, 'class="sheet order-cover"'));
        $this->assertLessThan(strpos($html, 'class="sheet"'), strpos($html, 'order-cover'));
        $this->assertSame(2, substr_count($html, 'class="sheet"'));
        $this->assertStringContainsString(__('production_sheet.title'), $html);
        $this->assertStringContainsString(__('production_sheet.cover.title'), $html);
        $this->assertStringContainsString('CMD-2026-042', $html);
        $this->assertStringContainsString('LINE-TRAVELER-1', $html);
        $this->assertStringContainsString('LINE-TRAVELER-2', $html);
        $this->assertStringContainsString('INOX 304L', $html);
        $this->assertStringContainsString('3 000 × 1 500 mm', $html);
        $this->assertStringContainsString('Plieuse 1', $html);
        $this->assertStringContainsString('1h30', $html);
        $this->assertStringContainsString('15 min', $html);
        $this->assertStringContainsString('TOLE-304-2', $html);
        $this->assertStringContainsString('data:image/png;base64,ORDERQR', $html);
        $this->assertStringContainsString('data:image/png;base64,TASKBARCODE', $html);
        // La colonne de scan n'apparaît que sur la fiche dont une opération a un code-barres.
        $this->assertSame(1, substr_count($html, '>' . __('production_sheet.ops.declare') . '<'));
    }

    public function test_the_part_photo_is_taken_from_the_ged(): void
    {
        Storage::fake(config('files.disk'));
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        Storage::disk(config('files.disk'))->put('private/files/2026/10/photo.png', $png);
        Storage::disk(config('files.disk'))->put('private/files/2026/10/plan.png', 'not the photo');

        $plan = $this->gedFile('plan.png', FileRole::PLAN, true);
        $photo = $this->gedFile('photo.png', FileRole::PHOTO, false);

        $builder = app(ProductionSheetBuilder::class);
        $gedImage = (new \ReflectionMethod($builder, 'gedImage'))->getClosure($builder);

        $this->assertSame('data:image/png;base64,' . base64_encode($png), $gedImage(collect([$plan, $photo])));
        $this->assertNull($gedImage(collect()));
    }

    public function test_a_cad_svg_is_resized_on_its_viewbox_with_origin_at_zero(): void
    {
        // Export CAO réel : width="100%", origine négative, Y retourné.
        $svg = '<?xml version="1.0"?><svg viewBox="-4.9 -79.9 499.8 84.8" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">'
            . '<g transform="scale(1,-1)"><path d="M 0,0 L 0,75 L 490,75 L 490,0 Z"/></g></svg>';

        $builder = app(ProductionSheetBuilder::class);
        $fitted = (new \ReflectionMethod($builder, 'fitSvg'))->getClosure($builder)($svg);

        // php-svg-lib dessine en unités du viewBox : width / height doivent
        // lui être égaux, et son origine nulle.
        $this->assertStringContainsString('width="499.8" height="84.8"', $fitted);
        $this->assertStringContainsString('viewBox="0 0 499.8 84.8"', $fitted);
        $this->assertStringNotContainsString('100%', $fitted);
        $this->assertStringContainsString('<g transform="translate(4.9 79.9)">', $fitted);
        $this->assertStringEndsWith('</g></g></svg>', $fitted);
    }

    private function gedFile(string $name, string $role, bool $primary): File
    {
        $file = new File([
            'name' => $name,
            'kind' => FileKindResolver::KIND_IMAGE,
            'extension' => 'png',
            'disk' => config('files.disk'),
            'path' => 'private/files/2026/10/' . $name,
        ]);
        $file->setRelation('pivot', (object) ['role' => $role, 'is_primary' => $primary]);

        return $file;
    }

    private function sheet(array $overrides): array
    {
        return array_merge([
            'ordre' => 1,
            'code' => null,
            'label' => 'Platine',
            'product_code' => null,
            'revision' => 'B',
            'qty' => 5,
            'delivered_qty' => 0,
            'unit' => 'U',
            'due_date' => '2026-11-15',
            'internal_delay' => '2026-11-10',
            'material' => null,
            'thickness' => null,
            'x_size' => null,
            'y_size' => null,
            'z_size' => null,
            'weight' => null,
            'bend_count' => null,
            'finishing' => null,
            'cad_file' => null,
            'notes' => null,
            'image_src' => null,
            'planned_start' => null,
            'planned_end' => null,
            'operations' => [],
            'components' => [],
        ], $overrides);
    }
}
