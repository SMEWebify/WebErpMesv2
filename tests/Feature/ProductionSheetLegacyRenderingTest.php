<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Non-régression de la fiche suiveuse (print/production-sheet).
 *
 * Les fichiers de référence de tests/Fixtures/production-sheet ont été rendus
 * AVANT l'impression des lots consommés (coulées). Une fiche dont aucune tâche
 * n'a consommé de lot doit produire exactement le même HTML.
 *
 * Si une référence manque, elle est écrite et le test est marqué incomplet :
 * ne jamais régénérer une référence existante pour faire passer ce test.
 */
class ProductionSheetLegacyRenderingTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/../Fixtures/production-sheet';

    public function test_a_sheet_without_consumed_batches_renders_as_before(): void
    {
        app()->setLocale('fr');

        $this->assertMatchesReference('production-sheet-fr.html', $this->render());
    }

    public function test_a_sheet_without_consumed_batches_renders_as_before_in_english(): void
    {
        app()->setLocale('en');

        $this->assertMatchesReference('production-sheet-en.html', $this->render());
    }

    /**
     * Données telles que ProductionSheetBuilder les produit quand aucun lot n'est
     * lié : `batches` vide sur chaque opération et chaque composant.
     */
    public static function data(): array
    {
        return [
            'orderCode' => 'CMD-2026-042',
            'customerName' => 'ACME',
            'customerReference' => 'PO-77',
            'generatedAt' => Carbon::parse('2026-10-01 08:00'),
            'orderQrSrc' => 'data:image/png;base64,ORDERQR',
            'sheets' => collect([
                self::sheet([
                    'ordre' => 1,
                    'code' => 'LINE-1',
                    'product_code' => 'PLAT-01',
                    'equipment_tags' => ['1RCP001PO'],
                    'material' => 'INOX 304L',
                    'thickness' => 2,
                    'x_size' => 300,
                    'y_size' => 150,
                    'weight' => 1.25,
                    'bend_count' => 4,
                    'notes' => 'Ébavurer soigneusement',
                    'planned_start' => '2026-11-02 08:00:00',
                    'planned_end' => '2026-11-03 17:00:00',
                    'operations' => [
                        self::operation(10, 'Découpe laser', 'LAS', 'Laser 1', 30, 'data:image/png;base64,BAR10'),
                        self::operation(20, 'Pliage', 'PLI', 'Plieuse 1', 90, 'data:image/png;base64,BAR20'),
                    ],
                    'components' => [
                        self::bomComponent(1, 'Tôle', 'TOLE-304-2 Tôle inox 2 mm', 1.5),
                        self::bomComponent(2, 'Vis', 'VIS-M6 Vis M6', 4),
                    ],
                ]),
                self::sheet([
                    'ordre' => 2,
                    'code' => 'LINE-2',
                    'operations' => [
                        self::operation(10, 'Découpe', null, null, 15, null),
                    ],
                ]),
            ]),
            'totalRequired' => 10.0,
            'totalDelivered' => 0.0,
        ];
    }

    private function render(): string
    {
        return view('print/production-sheet', self::data())->render();
    }

    private static function operation(int $ordre, string $label, ?string $service, ?string $workcenter, float $minutes, ?string $barcode): array
    {
        return [
            'ordre' => $ordre,
            'label' => $label,
            'service' => $service,
            'workcenter' => $workcenter,
            'planned_time_min' => $minutes,
            'barcode_src' => $barcode,
            'batches' => [],
        ];
    }

    private static function bomComponent(int $ordre, string $label, string $component, float $qty): array
    {
        return ['ordre' => $ordre, 'label' => $label, 'component' => $component, 'qty' => $qty, 'batches' => []];
    }

    private static function sheet(array $overrides): array
    {
        return array_merge([
            'ordre' => 1,
            'code' => null,
            'label' => 'Platine',
            'product_code' => null,
            'revision' => 'B',
            'equipment_tags' => [],
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

    private function assertMatchesReference(string $name, string $html): void
    {
        $path = self::FIXTURES . '/' . $name;

        if (!is_file($path)) {
            if (!is_dir(self::FIXTURES)) {
                mkdir(self::FIXTURES, 0777, true);
            }
            file_put_contents($path, $html);
            $this->markTestIncomplete("Référence {$name} créée : relancer le test.");
        }

        // Fins de ligne normalisées : elles dépendent du checkout (autocrlf), pas du rendu.
        $normalize = fn (string $s) => str_replace("\r\n", "\n", $s);

        $this->assertSame($normalize(file_get_contents($path)), $normalize($html), "Le rendu de {$name} a changé.");
    }
}
