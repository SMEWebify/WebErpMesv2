<?php

namespace Database\Seeders;

use App\Enums\SalesLineType;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingVat;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\User;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Trames de devis métier (sous-traitance laser, chaudronnerie, métallerie,
 * usinage, moule) : lignes, détails techniques et gamme chiffrée.
 *
 * php artisan db:seed --class=QuoteTemplatesSeeder
 *
 * - Relançable : une trame dont le libellé existe déjà (même supprimée) est ignorée.
 * - Les postes de charge sont repris par leur code (taux horaire et marge de
 *   l'instance) ; un code absent est créé avec les valeurs de SERVICES.
 * - Le client d'une trame est sans importance (choisi à l'instanciation) :
 *   on prend la première société ayant un contact et une adresse.
 * - Prix de vente d'une ligne = somme des prix de sa gamme (temps × taux horaire
 *   × (1 + marge du poste), matière et sous-traitance au coût × (1 + marge)).
 */
class QuoteTemplatesSeeder extends Seeder
{
    /** code => [libellé, type, taux horaire, marge %, couleur] */
    private const SERVICES = [
        'MAT'   => ['Matière tôle', 3, 0, 10, '#7d7373'],
        'PROF'  => ['Matière profilés / tubes', 4, 0, 10, '#8d6e63'],
        'BLOC'  => ['Matière barre / bloc', 5, 0, 10, '#a1887f'],
        'PUR'   => ['Fournitures / éléments standards', 6, 0, 15, '#ed07e6'],
        'CAO'   => ['Bureau d\'études / CFAO', 1, 70, 20, '#5c6bc0'],
        'LAS'   => ['Découpe laser', 1, 120, 20, '#f3f702'],
        'BEND'  => ['Pliage', 1, 75, 20, '#1af50a'],
        'ROUL'  => ['Roulage', 1, 70, 20, '#26a69a'],
        'SCIE'  => ['Sciage / débit', 1, 50, 20, '#90a4ae'],
        'WELD'  => ['Soudure', 1, 65, 25, '#f40101'],
        'EBAV'  => ['Ébavurage / finition', 1, 45, 20, '#bdbdbd'],
        'ASSY'  => ['Assemblage / ajustage', 1, 60, 20, '#ff7043'],
        'TOUR'  => ['Tournage CN', 1, 70, 25, '#42a5f5'],
        'FRAI'  => ['Fraisage CN 3 axes', 1, 80, 25, '#1e88e5'],
        'FR5X'  => ['Fraisage 5 axes', 1, 115, 25, '#0d47a1'],
        'EDM'   => ['Électroérosion enfonçage', 1, 90, 25, '#7e57c2'],
        'EDMF'  => ['Électroérosion fil', 1, 85, 25, '#9575cd'],
        'RECT'  => ['Rectification', 1, 75, 25, '#00897b'],
        'POLI'  => ['Polissage', 1, 60, 25, '#ffca28'],
        'CTRL'  => ['Contrôle / métrologie', 1, 70, 20, '#43a047'],
        'POSE'  => ['Pose sur chantier', 1, 55, 25, '#6d4c41'],
        'PAINT' => ['Thermolaquage / peinture', 7, 0, 15, '#411fea'],
        'GALVA' => ['Galvanisation à chaud', 7, 0, 15, '#78909c'],
        'TTH'   => ['Traitement thermique', 7, 0, 15, '#e53935'],
        'TSURF' => ['Traitement de surface', 7, 0, 15, '#00acc1'],
    ];

    /** @var array<string, MethodsServices> */
    private array $services = [];

    private int $statusId;
    private int $vatId;
    private int $defaultUnitId;
    private ?int $linearMeterUnitId = null;

    public function run(): void
    {
        $header = $this->headerDefaults();
        if ($header === null) {
            $this->command?->warn('Trames non créées : il faut au moins un utilisateur, une société avec contact et adresse, une TVA, une unité, une condition et un mode de paiement et un mode de livraison.');

            return;
        }

        $this->resolveServices();
        $this->statusId = (int) (Status::orderBy('order')->value('id') ?? 1);

        $created = 0;
        foreach ($this->templates() as $template) {
            $exists = Quotes::onlyTemplates()->withTrashed()->where('label', $template['label'])->exists();
            if ($exists) {
                $this->command?->line("  ignorée (existe déjà) : {$template['label']}");
                continue;
            }

            DB::transaction(fn () => $this->createTemplate($template, $header));
            $this->command?->info("  créée : {$template['label']}");
            $created++;
        }

        $this->command?->info("{$created} trame(s) de devis créée(s).");
    }

    // ---------------------------------------------------------------------
    // Référentiels
    // ---------------------------------------------------------------------

    private function headerDefaults(): ?array
    {
        $user = User::whereHas('roles', fn ($q) => $q->where('name', 'Admin'))->orderBy('id')->first()
            ?? User::orderBy('id')->first();

        $contact = CompaniesContacts::whereIn('companies_id', CompaniesAddresses::select('companies_id'))
            ->orderByDesc('default')->orderBy('id')->first();
        $address = $contact
            ? CompaniesAddresses::where('companies_id', $contact->companies_id)->orderByDesc('default')->orderBy('id')->first()
            : null;

        $vat  = AccountingVat::getDefault() ?? AccountingVat::orderBy('id')->first();
        $unit = MethodsUnits::getDefault() ?? MethodsUnits::where('code', 'UNIT')->first() ?? MethodsUnits::orderBy('id')->first();

        $condition = AccountingPaymentConditions::getDefault() ?? AccountingPaymentConditions::orderBy('id')->first();
        $method    = AccountingPaymentMethod::getDefault() ?? AccountingPaymentMethod::orderBy('id')->first();
        $delivery  = AccountingDelivery::getDefault() ?? AccountingDelivery::orderBy('id')->first();

        if (!$user || !$contact || !$address || !$vat || !$unit || !$condition || !$method || !$delivery) {
            return null;
        }

        $this->vatId         = $vat->id;
        $this->defaultUnitId = $unit->id;
        $this->linearMeterUnitId = MethodsUnits::firstOrCreate(['code' => 'ML'], ['label' => 'Mètre linéaire', 'type' => 2])->id;

        return [
            'companies_id'                     => $contact->companies_id,
            'companies_contacts_id'            => $contact->id,
            'companies_addresses_id'           => $address->id,
            'user_id'                          => $user->id,
            'accounting_payment_conditions_id' => $condition->id,
            'accounting_payment_methods_id'    => $method->id,
            'accounting_deliveries_id'         => $delivery->id,
        ];
    }

    private function resolveServices(): void
    {
        $ordre = (int) MethodsServices::max('ordre');

        foreach (self::SERVICES as $code => [$label, $type, $rate, $margin, $color]) {
            $this->services[$code] = MethodsServices::where('code', $code)->first()
                ?? MethodsServices::create([
                    'code'        => $code,
                    'ordre'       => ++$ordre,
                    'label'       => $label,
                    'type'        => $type,
                    'hourly_rate' => $rate,
                    'margin'      => $margin,
                    'color'       => $color,
                ]);
        }
    }

    // ---------------------------------------------------------------------
    // Création
    // ---------------------------------------------------------------------

    private function createTemplate(array $template, array $header): void
    {
        $quote = new Quotes($header + [
            'label'   => $template['label'],
            'statu'   => 1,
            'comment' => $template['comment'] ?? null,
        ]);
        $quote->uuid        = (string) Str::uuid();
        $quote->is_template = true;
        $quote->code        = 'TRAME-' . Str::upper(Str::random(8));
        $quote->save();

        $quote->code = 'TRAME-' . $quote->id;
        $quote->save();

        foreach ($template['lines'] as $index => $line) {
            $ordre = $index + 1;

            match ($line['type'] ?? 'article') {
                'article' => $this->createArticle($quote->id, $ordre, $line),
                default   => $this->createPresentation($quote->id, $ordre, $line),
            };
        }
    }

    private function createPresentation(int $quoteId, int $ordre, array $line): void
    {
        QuoteLines::create([
            'quotes_id'          => $quoteId,
            'ordre'              => $ordre,
            'code'               => '',
            'label'              => $line['label'],
            'qty'                => 0,
            'methods_units_id'   => $this->defaultUnitId,
            'selling_price'      => 0,
            'discount'           => 0,
            'accounting_vats_id' => $this->vatId,
            'statu'              => 1,
            'line_type'          => SalesLineType::from($line['type'])->value,
            'pdf_package'        => $line['package'] ?? 0,
        ]);
    }

    private function createArticle(int $quoteId, int $ordre, array $line): void
    {
        $qty   = (float) $line['qty'];
        $tasks = [];
        $price = 0.0;
        $step  = 10;

        // Nomenclature : coût par pièce (qty 1), le libellé porte la quantité réelle.
        foreach ($line['bom'] ?? [] as [$code, $label, $unitCost]) {
            $service = $this->services[$code];
            $unitPrice = $unitCost * (1 + (float) $service->margin / 100);
            $tasks[] = $this->taskData($service, $label, $step, 0, 0, $unitCost, $unitPrice);
            $price += $unitPrice;
            $step  += 10;
        }

        // Gamme : [code, libellé, réglage h, temps unitaire h] ou [code, libellé, coût par pièce] (sous-traitance).
        foreach ($line['ops'] ?? [] as $op) {
            $service = $this->services[$op[0]];
            if (count($op) === 3) {
                [$setup, $unitTime, $unitCost] = [0, 0, (float) $op[2]];
            } else {
                [$setup, $unitTime] = [(float) $op[2], (float) $op[3]];
                $unitCost = ($setup / max($qty, 1) + $unitTime) * (float) $service->hourly_rate;
            }
            $unitPrice = $unitCost * (1 + (float) $service->margin / 100);
            $tasks[] = $this->taskData($service, $op[1], $step, $setup, $unitTime, $unitCost, $unitPrice);
            $price += $unitPrice;
            $step  += 10;
        }

        $quoteLine = QuoteLines::create([
            'quotes_id'            => $quoteId,
            'ordre'                => $ordre,
            'code'                 => $line['code'],
            'label'                => $line['label'],
            'qty'                  => $qty,
            'methods_units_id'     => ($line['unit'] ?? null) === 'ML' ? $this->linearMeterUnitId : $this->defaultUnitId,
            'selling_price'        => round($price, 2),
            'discount'             => 0,
            'accounting_vats_id'   => $this->vatId,
            'statu'                => 1,
            'use_calculated_price' => false,
        ]);

        QuoteLineDetails::create(['quote_lines_id' => $quoteLine->id] + ($line['details'] ?? []));

        foreach ($tasks as $task) {
            Task::create($task + ['quote_lines_id' => $quoteLine->id]);
        }
    }

    private function taskData(MethodsServices $service, string $label, int $ordre, float $setup, float $unitTime, float $unitCost, float $unitPrice): array
    {
        return [
            'code'                => $service->code,
            'label'               => $label,
            'ordre'               => $ordre,
            'methods_services_id' => $service->id,
            'type'                => $service->type,
            'seting_time'         => $setup,
            'unit_time'           => $unitTime,
            'qty'                 => 1,
            'qty_init'            => 1,
            'unit_cost'           => round($unitCost, 3),
            'unit_price'          => round($unitPrice, 3),
            'status_id'           => $this->statusId,
            'origin'              => '0',
        ];
    }

    // ---------------------------------------------------------------------
    // Contenu des trames
    // ---------------------------------------------------------------------

    private function templates(): array
    {
        return [
            ...$this->laserTemplates(),
            ...$this->boilermakingTemplates(),
            ...$this->metalworkTemplates(),
            ...$this->machiningTemplates(),
            ...$this->mouldTemplates(),
        ];
    }

    private function laserTemplates(): array
    {
        return [
            [
                'label'   => 'Sous-traitance laser – découpe acier S235',
                'comment' => "Prix départ atelier, matière fournie par nos soins.\nTolérances de découpe selon ISO 9013 classe 2. Fichiers DXF ou STEP à fournir.",
                'lines'   => [
                    ['type' => 'section', 'label' => 'Découpe laser acier S235JR'],
                    [
                        'code' => 'LAS-PLAT-10', 'label' => 'Platine 200 × 200 ép. 10 – 4 trous Ø18', 'qty' => 50,
                        'details' => ['material' => 'S235JR', 'thickness' => 10, 'x_size' => 200, 'y_size' => 200, 'weight' => 3.1, 'finishing' => 'Brut de découpe, ébavuré'],
                        'bom' => [['MAT', 'Tôle S235JR ép. 10 (≈ 3,4 kg par pièce, chute comprise)', 4.10]],
                        'ops' => [['LAS', 'Découpe laser', 0.25, 0.03], ['EBAV', 'Ébavurage', 0, 0.02]],
                    ],
                    [
                        'code' => 'LAS-GOUS-8', 'label' => 'Gousset triangulaire 150 × 150 ép. 8', 'qty' => 100,
                        'details' => ['material' => 'S235JR', 'thickness' => 8, 'x_size' => 150, 'y_size' => 150, 'weight' => 0.7, 'finishing' => 'Brut de découpe, ébavuré'],
                        'bom' => [['MAT', 'Tôle S235JR ép. 8 (≈ 0,9 kg par pièce)', 1.30]],
                        'ops' => [['LAS', 'Découpe laser', 0.15, 0.012], ['EBAV', 'Ébavurage', 0, 0.01]],
                    ],
                    [
                        'code' => 'LAS-FLAS-3', 'label' => 'Flasque ajourée Ø300 ép. 3', 'qty' => 30,
                        'details' => ['material' => 'S235JR', 'thickness' => 3, 'diameter' => 300, 'weight' => 1.2, 'finishing' => 'Brut de découpe, ébavuré'],
                        'bom' => [['MAT', 'Tôle S235JR ép. 3 (≈ 2,2 kg par pièce)', 2.20]],
                        'ops' => [['LAS', 'Découpe laser', 0.2, 0.05], ['EBAV', 'Ébavurage', 0, 0.02]],
                    ],
                    ['type' => 'subtotal', 'label' => 'Sous-total découpe'],
                    ['type' => 'text', 'label' => 'Délai : 5 jours ouvrés après réception de la commande et des fichiers.'],
                ],
            ],
            [
                'label'   => 'Sous-traitance laser – découpe et pliage inox 304L',
                'comment' => "Inox livré avec film de protection sur la face visible.\nRayons de pliage selon outillage standard ; plans cotés à fournir.",
                'lines'   => [
                    [
                        'code' => 'LP-CAPOT-15', 'label' => 'Capot inox 304L ép. 1,5 – 4 plis', 'qty' => 20,
                        'details' => ['material' => '304L', 'thickness' => 1.5, 'x_size' => 600, 'y_size' => 400, 'bend_count' => 4, 'weight' => 3.4, 'finishing' => 'Brossé grain 240 face visible'],
                        'bom' => [['MAT', 'Tôle inox 304L ép. 1,5 (≈ 3,8 kg par pièce)', 9.50]],
                        'ops' => [['LAS', 'Découpe laser', 0.25, 0.06], ['BEND', 'Pliage 4 plis', 0.5, 0.08], ['EBAV', 'Ébavurage et brossage', 0, 0.10]],
                    ],
                    [
                        'code' => 'LP-EQUE-3', 'label' => 'Équerre de fixation inox ép. 3 – 1 pli', 'qty' => 200,
                        'details' => ['material' => '304L', 'thickness' => 3, 'x_size' => 80, 'y_size' => 60, 'bend_count' => 1, 'weight' => 0.2, 'finishing' => 'Ébavuré'],
                        'bom' => [['MAT', 'Tôle inox 304L ép. 3', 0.85]],
                        'ops' => [['LAS', 'Découpe laser', 0.15, 0.01], ['BEND', 'Pliage 1 pli', 0.3, 0.008], ['EBAV', 'Ébavurage', 0, 0.005]],
                    ],
                    [
                        'code' => 'LP-TABL-2', 'label' => 'Tablette pliée inox ép. 2 – 3 plis', 'qty' => 10,
                        'details' => ['material' => '304L', 'thickness' => 2, 'x_size' => 1200, 'y_size' => 400, 'bend_count' => 3, 'weight' => 8.5, 'finishing' => 'Brossé grain 240 face visible'],
                        'bom' => [['MAT', 'Tôle inox 304L ép. 2 (≈ 9 kg par pièce)', 18.00]],
                        'ops' => [['LAS', 'Découpe laser', 0.2, 0.08], ['BEND', 'Pliage 3 plis', 0.4, 0.12], ['EBAV', 'Ébavurage et brossage', 0, 0.15]],
                    ],
                    ['type' => 'text', 'label' => 'Tolérances de pliage ± 0,5 mm, angles ± 1°.'],
                ],
            ],
            [
                'label'   => 'Sous-traitance laser – kit tôlerie aluminium thermolaqué',
                'comment' => "Thermolaquage polyester sous-traité, teinte RAL au choix, aspect satiné.\nPrévoir 5 jours ouvrés de plus pour le thermolaquage.",
                'lines'   => [
                    ['type' => 'section', 'label' => 'Pièces aluminium 5754 thermolaquées'],
                    [
                        'code' => 'ALU-PANN-2', 'label' => 'Panneau de façade alu 5754 ép. 2 – 2 plis', 'qty' => 50,
                        'details' => ['material' => 'AlMg3 5754', 'thickness' => 2, 'x_size' => 500, 'y_size' => 300, 'bend_count' => 2, 'weight' => 0.9, 'finishing' => 'Thermolaqué RAL 7016'],
                        'bom' => [['MAT', 'Tôle alu 5754 ép. 2', 6.80]],
                        'ops' => [['LAS', 'Découpe laser', 0.25, 0.05], ['BEND', 'Pliage 2 plis', 0.4, 0.04], ['EBAV', 'Ébavurage', 0, 0.03], ['PAINT', 'Thermolaquage RAL 7016 (sous-traité)', 4.50]],
                    ],
                    [
                        'code' => 'ALU-SUPP-3', 'label' => 'Support alu ép. 3 – 2 plis', 'qty' => 50,
                        'details' => ['material' => 'AlMg3 5754', 'thickness' => 3, 'x_size' => 150, 'y_size' => 100, 'bend_count' => 2, 'weight' => 0.15, 'finishing' => 'Thermolaqué RAL 7016'],
                        'bom' => [['MAT', 'Tôle alu 5754 ép. 3', 1.90]],
                        'ops' => [['LAS', 'Découpe laser', 0.15, 0.02], ['BEND', 'Pliage 2 plis', 0.3, 0.02], ['PAINT', 'Thermolaquage RAL 7016 (sous-traité)', 1.80]],
                    ],
                    [
                        'code' => 'ALU-COUV-15', 'label' => 'Couvercle alu ép. 1,5 – 4 plis', 'qty' => 50,
                        'details' => ['material' => 'AlMg3 5754', 'thickness' => 1.5, 'x_size' => 400, 'y_size' => 300, 'bend_count' => 4, 'weight' => 0.5, 'finishing' => 'Thermolaqué RAL 7016'],
                        'bom' => [['MAT', 'Tôle alu 5754 ép. 1,5', 3.20]],
                        'ops' => [['LAS', 'Découpe laser', 0.2, 0.03], ['BEND', 'Pliage 4 plis', 0.4, 0.06], ['PAINT', 'Thermolaquage RAL 7016 (sous-traité)', 3.20]],
                    ],
                    ['type' => 'subtotal', 'label' => 'Sous-total kit'],
                ],
            ],
        ];
    }

    private function boilermakingTemplates(): array
    {
        return [
            [
                'label'   => 'Chaudronnerie – cuve inox 304L 1 000 L',
                'comment' => "Cuve non soumise à la DESP (pression atmosphérique).\nNote de calcul et marquage CE non compris.",
                'lines'   => [
                    ['type' => 'section', 'label' => 'Fabrication de la cuve'],
                    [
                        'code' => 'CUV-1000-304L', 'label' => 'Cuve verticale inox 304L 1 000 L – Ø1 000 × H1 400, fond bombé GRC, piquages DN50 / DN80', 'qty' => 1,
                        'details' => ['material' => '304L', 'thickness' => 3, 'diameter' => 1000, 'z_size' => 1400, 'weight' => 185, 'finishing' => 'Décapée passivée, soudures brossées',
                            'internal_comment' => 'Adapter les piquages et pieds au cahier des charges du client.'],
                        'bom' => [
                            ['MAT', 'Tôles inox 304L ép. 3 (virole, toit)', 980],
                            ['PUR', 'Fonds bombés GRC Ø1 000 ép. 3', 620],
                            ['PUR', 'Piquages, brides DN50 / DN80, pieds réglables', 380],
                        ],
                        'ops' => [
                            ['CAO', 'Plan de fabrication et développés', 3, 0],
                            ['LAS', 'Découpe des développés', 0.5, 1.5],
                            ['ROUL', 'Roulage de la virole', 0.5, 2],
                            ['WELD', 'Soudure TIG virole et fonds', 1, 14],
                            ['WELD', 'Pose et soudure des piquages', 0, 5],
                            ['CTRL', 'Épreuve hydraulique et contrôle par ressuage', 1, 3],
                            ['TSURF', 'Décapage et passivation (sous-traités)', 450],
                        ],
                    ],
                    ['type' => 'text', 'label' => 'Livrée avec PV d\'épreuve hydraulique et certificats matière 3.1.'],
                ],
            ],
            [
                'label'   => 'Chaudronnerie – trémie acier S235 avec châssis',
                'comment' => 'Ensemble galvanisé à chaud. Transport et levage sur site non compris.',
                'lines'   => [
                    [
                        'code' => 'TREM-1500', 'label' => 'Trémie pyramidale S235 ép. 5 – 1 500 × 1 500, sortie 300 × 300', 'qty' => 1,
                        'details' => ['material' => 'S235JR', 'thickness' => 5, 'x_size' => 1500, 'y_size' => 1500, 'z_size' => 1200, 'weight' => 260, 'finishing' => 'Galvanisé à chaud'],
                        'bom' => [['MAT', 'Tôle S235JR ép. 5 (≈ 300 kg)', 420]],
                        'ops' => [
                            ['CAO', 'Plan et développés', 2, 0],
                            ['LAS', 'Découpe des panneaux', 0.5, 1],
                            ['BEND', 'Pliage des panneaux et brides', 0.5, 1.5],
                            ['WELD', 'Assemblage et soudure MAG', 1, 10],
                            ['EBAV', 'Meulage et finition', 0, 2],
                        ],
                    ],
                    [
                        'code' => 'TREM-CHAS', 'label' => 'Châssis support en profilés HEA 120', 'qty' => 1,
                        'details' => ['material' => 'S235JR', 'weight' => 140, 'finishing' => 'Galvanisé à chaud'],
                        'bom' => [['PROF', 'Profilés HEA 120 et cornières', 290]],
                        'ops' => [['SCIE', 'Débit des profilés', 0.5, 1.5], ['WELD', 'Soudure MAG', 0.5, 8]],
                    ],
                    [
                        'code' => 'TREM-GALVA', 'label' => 'Galvanisation à chaud de l\'ensemble (≈ 400 kg)', 'qty' => 1,
                        'ops' => [['GALVA', 'Galvanisation à chaud (sous-traitée)', 380]],
                    ],
                ],
            ],
            [
                'label'   => 'Chaudronnerie – bâti mécano-soudé S355 usiné',
                'comment' => 'Bâti détensionné avant usinage. PV de contrôle dimensionnel fourni.',
                'lines'   => [
                    [
                        'code' => 'BATI-2000', 'label' => 'Bâti mécano-soudé S355 – 2 000 × 800 × 900, faces d\'appui usinées', 'qty' => 1,
                        'details' => ['material' => 'S355J2', 'x_size' => 2000, 'y_size' => 800, 'z_size' => 900, 'weight' => 420, 'finishing' => 'Primaire + finition RAL 7035'],
                        'bom' => [
                            ['PROF', 'Tubes carrés et profilés S355', 450],
                            ['MAT', 'Tôles S355 ép. 15 / 20 (platines)', 310],
                        ],
                        'ops' => [
                            ['CAO', 'Plans de mécano-soudure et d\'usinage', 4, 0],
                            ['SCIE', 'Débit des profilés', 0.5, 2],
                            ['LAS', 'Découpe des platines', 0.5, 1],
                            ['WELD', 'Assemblage et soudure MAG', 1, 16],
                            ['TTH', 'Détensionnement (sous-traité)', 350],
                            ['FRAI', 'Usinage des faces d\'appui et taraudages', 2, 6],
                            ['CTRL', 'Contrôle dimensionnel', 0.5, 1.5],
                            ['PAINT', 'Peinture primaire et finition (sous-traitée)', 280],
                        ],
                    ],
                    ['type' => 'text', 'label' => 'Tolérances générales ISO 13920 classe B / F.'],
                ],
            ],
        ];
    }

    private function metalworkTemplates(): array
    {
        return [
            [
                'label'   => 'Métallerie – garde-corps acier thermolaqué',
                'comment' => "Garde-corps conforme à la NF P01-012.\nRelevé sur site, accès chantier et support béton à la charge du client.",
                'lines'   => [
                    ['type' => 'section', 'label' => 'Fabrication'],
                    [
                        'code' => 'GC-BARR', 'label' => 'Garde-corps barreaudé acier H1 000 – main courante tube 40 × 40, barreaux 20 × 20', 'qty' => 25, 'unit' => 'ML',
                        'details' => ['material' => 'S235JR', 'z_size' => 1000, 'weight' => 12, 'finishing' => 'Thermolaqué RAL au choix'],
                        'bom' => [['PROF', 'Tubes 40 × 40 et 20 × 20 (par ml)', 28]],
                        'ops' => [
                            ['SCIE', 'Débit', 0.3, 0.25],
                            ['WELD', 'Assemblage et soudure', 0.5, 1.2],
                            ['EBAV', 'Meulage des soudures', 0, 0.3],
                            ['PAINT', 'Thermolaquage (sous-traité)', 18],
                        ],
                    ],
                    [
                        'code' => 'GC-PLAT', 'label' => 'Platine de fixation 150 × 100 ép. 10 percée', 'qty' => 20,
                        'details' => ['material' => 'S235JR', 'thickness' => 10, 'x_size' => 150, 'y_size' => 100, 'weight' => 1.2],
                        'bom' => [['MAT', 'Tôle S235JR ép. 10', 2.50]],
                        'ops' => [['LAS', 'Découpe laser', 0.2, 0.03]],
                    ],
                    ['type' => 'subtotal', 'label' => 'Sous-total fabrication'],
                    ['type' => 'section', 'label' => 'Pose'],
                    [
                        'code' => 'GC-POSE', 'label' => 'Pose sur chantier – fixation par scellement chimique', 'qty' => 25, 'unit' => 'ML',
                        'bom' => [['PUR', 'Scellement chimique, tiges filetées inox (par ml)', 6]],
                        'ops' => [['POSE', 'Pose (2 compagnons)', 2, 0.6]],
                    ],
                    ['type' => 'subtotal', 'label' => 'Sous-total pose'],
                ],
            ],
            [
                'label'   => 'Métallerie – escalier droit limons acier',
                'comment' => 'Escalier galvanisé à chaud. Relevé de cotes sur site compris ; génie civil non compris.',
                'lines'   => [
                    [
                        'code' => 'ESC-14M', 'label' => 'Escalier droit 14 marches – limons UPN 200, marches tôle larmée ép. 5', 'qty' => 1,
                        'details' => ['material' => 'S235JR', 'x_size' => 3200, 'y_size' => 1000, 'z_size' => 2600, 'weight' => 380, 'finishing' => 'Galvanisé à chaud'],
                        'bom' => [
                            ['PROF', 'UPN 200 et cornières de marches', 520],
                            ['MAT', 'Tôle larmée ép. 5 / 7 (marches)', 380],
                        ],
                        'ops' => [
                            ['CAO', 'Relevé et plan d\'exécution', 4, 0],
                            ['SCIE', 'Débit des limons', 0.5, 2],
                            ['LAS', 'Découpe des marches', 0.5, 1.5],
                            ['BEND', 'Pliage des marches', 0.5, 2],
                            ['WELD', 'Assemblage et soudure', 1, 14],
                            ['EBAV', 'Meulage et finition', 0, 3],
                            ['GALVA', 'Galvanisation à chaud (sous-traitée)', 420],
                        ],
                    ],
                    [
                        'code' => 'ESC-GC', 'label' => 'Garde-corps rampant', 'qty' => 4, 'unit' => 'ML',
                        'details' => ['material' => 'S235JR', 'finishing' => 'Galvanisé à chaud'],
                        'bom' => [['PROF', 'Tubes main courante et barreaux (par ml)', 30]],
                        'ops' => [['SCIE', 'Débit', 0.3, 0.3], ['WELD', 'Soudure', 0.5, 1.5], ['GALVA', 'Galvanisation (sous-traitée)', 25]],
                    ],
                    [
                        'code' => 'ESC-POSE', 'label' => 'Pose et fixation', 'qty' => 1,
                        'bom' => [['PUR', 'Chevilles et scellements', 60]],
                        'ops' => [['POSE', 'Pose (2 compagnons)', 1, 12]],
                    ],
                ],
            ],
            [
                'label'   => 'Métallerie – portail coulissant et portillon',
                'comment' => "Génie civil (longrine, massifs, fourreaux) non compris.\nMotorisation en option.",
                'lines'   => [
                    [
                        'code' => 'PORT-COUL', 'label' => 'Portail coulissant autoportant 4 000 × 1 600 – cadre tube 80 × 40, remplissage barreaudé', 'qty' => 1,
                        'details' => ['material' => 'S235JR', 'x_size' => 4000, 'z_size' => 1600, 'weight' => 210, 'finishing' => 'Galvanisé + thermolaqué RAL au choix'],
                        'bom' => [
                            ['PROF', 'Tubes 80 × 40, 40 × 40 et barreaux', 640],
                            ['PUR', 'Kit rail autoportant, galets, butées', 780],
                        ],
                        'ops' => [
                            ['SCIE', 'Débit', 0.5, 3],
                            ['WELD', 'Assemblage et soudure', 1, 16],
                            ['EBAV', 'Meulage et préparation', 0, 3],
                            ['PAINT', 'Thermolaquage (sous-traité)', 380],
                        ],
                    ],
                    [
                        'code' => 'PORT-PIET', 'label' => 'Portillon 1 000 × 1 600 avec serrure', 'qty' => 1,
                        'details' => ['material' => 'S235JR', 'x_size' => 1000, 'z_size' => 1600, 'weight' => 45, 'finishing' => 'Galvanisé + thermolaqué RAL au choix'],
                        'bom' => [
                            ['PROF', 'Tubes et barreaux', 140],
                            ['PUR', 'Serrure, gâche, paumelles, ferme-porte', 210],
                        ],
                        'ops' => [
                            ['SCIE', 'Débit', 0.3, 1],
                            ['WELD', 'Assemblage et soudure', 0.5, 4],
                            ['EBAV', 'Meulage et préparation', 0, 1],
                            ['PAINT', 'Thermolaquage (sous-traité)', 110],
                        ],
                    ],
                    ['type' => 'section', 'label' => 'Options'],
                    [
                        'code' => 'PORT-MOT', 'label' => 'Motorisation 24 V avec photocellules, feu et 2 télécommandes', 'qty' => 1,
                        'bom' => [['PUR', 'Moteur 24 V, photocellules, feu clignotant, télécommandes', 690]],
                        'ops' => [['ASSY', 'Câblage et mise en service', 0, 4]],
                    ],
                    [
                        'code' => 'PORT-POSE', 'label' => 'Pose du portail et du portillon', 'qty' => 1,
                        'bom' => [['PUR', 'Fixations et scellements', 80]],
                        'ops' => [['POSE', 'Pose (2 compagnons)', 1, 10]],
                    ],
                ],
            ],
        ];
    }

    private function machiningTemplates(): array
    {
        return [
            [
                'label'   => 'Usinage – tournage CN série (42CrMo4)',
                'comment' => "Tolérances générales ISO 2768-mK.\nCertificat matière 3.1 sur demande.",
                'lines'   => [
                    [
                        'code' => 'TOUR-AXE-40', 'label' => 'Axe Ø40 × 180 – 42CrMo4 traité, épaulements et gorge de circlip', 'qty' => 100,
                        'details' => ['material' => '42CrMo4', 'diameter' => 40, 'x_size' => 180, 'weight' => 1.8, 'finishing' => 'Trempé revenu 30-35 HRC, Ra 1,6 sur portées'],
                        'bom' => [['BLOC', 'Barre 42CrMo4 Ø45 (débit 185 mm)', 3.40]],
                        'ops' => [
                            ['SCIE', 'Débit', 0.25, 0.02],
                            ['TOUR', 'Tournage CN', 1.5, 0.12],
                            ['TTH', 'Trempe et revenu 30-35 HRC (sous-traités)', 2.10],
                            ['CTRL', 'Contrôle', 0.5, 0.01],
                        ],
                    ],
                    [
                        'code' => 'TOUR-BAG-60', 'label' => 'Bague Ø60 / Ø40 × 30 – 42CrMo4', 'qty' => 200,
                        'details' => ['material' => '42CrMo4', 'diameter' => 60, 'x_size' => 30, 'weight' => 0.4],
                        'bom' => [['BLOC', 'Barre 42CrMo4 Ø65 (débit 34 mm)', 1.20]],
                        'ops' => [['SCIE', 'Débit', 0.25, 0.01], ['TOUR', 'Tournage CN', 1.5, 0.06], ['CTRL', 'Contrôle', 0.25, 0.005]],
                    ],
                    [
                        'code' => 'TOUR-ENT-30', 'label' => 'Entretoise Ø30 × 15 – inox 316L', 'qty' => 500,
                        'details' => ['material' => '316L', 'diameter' => 30, 'x_size' => 15, 'weight' => 0.08],
                        'bom' => [['BLOC', 'Barre inox 316L Ø32', 0.55]],
                        'ops' => [['TOUR', 'Tournage CN (embarreur)', 1, 0.02]],
                    ],
                ],
            ],
            [
                'label'   => 'Usinage – pièces prismatiques aluminium anodisées',
                'comment' => "Tolérances générales ISO 2768-mK. Arêtes cassées 0,2 × 45°.\nAnodisation sous-traitée.",
                'lines'   => [
                    [
                        'code' => 'FRAI-SUPP-7075', 'label' => 'Support 120 × 80 × 40 – alu 7075, poches et taraudages M6', 'qty' => 25,
                        'details' => ['material' => '7075 T651', 'x_size' => 120, 'y_size' => 80, 'z_size' => 40, 'weight' => 0.6, 'finishing' => 'Anodisé noir 15 µm'],
                        'bom' => [['BLOC', 'Bloc 7075 (débit 125 × 85 × 45)', 11]],
                        'ops' => [
                            ['SCIE', 'Débit', 0.25, 0.03],
                            ['FRAI', 'Fraisage 3 axes – 2 phases', 2, 0.45],
                            ['EBAV', 'Ébavurage', 0, 0.08],
                            ['TSURF', 'Anodisation noire 15 µm (sous-traitée)', 3.80],
                            ['CTRL', 'Contrôle', 0.5, 0.05],
                        ],
                    ],
                    [
                        'code' => 'FRAI-PLAQ-5083', 'label' => 'Plaque de bridage 200 × 150 × 20 – alu 5083', 'qty' => 10,
                        'details' => ['material' => '5083', 'x_size' => 200, 'y_size' => 150, 'z_size' => 20, 'weight' => 1.4, 'finishing' => 'Brut d\'usinage'],
                        'bom' => [['BLOC', 'Plaque 5083 ép. 25', 14]],
                        'ops' => [['SCIE', 'Débit', 0.25, 0.05], ['FRAI', 'Surfaçage et perçages', 1.5, 0.6], ['EBAV', 'Ébavurage', 0, 0.1]],
                    ],
                    ['type' => 'text', 'label' => 'Programmation FAO et outillages compris.'],
                ],
            ],
            [
                'label'   => 'Usinage – prototype 5 axes avec rapport de contrôle',
                'comment' => 'Prototype réalisé d\'après modèle 3D STEP. Rapport premier article (AS9102) sur demande.',
                'lines'   => [
                    ['type' => 'section', 'label' => 'Prototype'],
                    [
                        'code' => 'PROTO-FAO', 'label' => 'Programmation FAO 5 axes et préparation', 'qty' => 1,
                        'ops' => [['CAO', 'Programmation FAO et simulation', 6, 0]],
                    ],
                    [
                        'code' => 'PROTO-PIECE', 'label' => 'Pièce prototype – inox 17-4PH, usinage 5 axes', 'qty' => 3,
                        'details' => ['material' => '17-4PH', 'x_size' => 150, 'y_size' => 90, 'z_size' => 60, 'weight' => 1.1, 'finishing' => 'H1025, Ra 0,8 sur portées'],
                        'bom' => [['BLOC', 'Bloc 17-4PH (débit 160 × 100 × 70)', 65]],
                        'ops' => [
                            ['SCIE', 'Débit', 0.3, 0.1],
                            ['FR5X', 'Usinage 5 axes', 3, 2.5],
                            ['EBAV', 'Ébavurage', 0, 0.4],
                            ['TTH', 'Traitement thermique H1025 (sous-traité)', 40],
                            ['CTRL', 'Contrôle', 1, 0.5],
                        ],
                    ],
                    [
                        'code' => 'PROTO-MMT', 'label' => 'Rapport de contrôle dimensionnel (MMT)', 'qty' => 1,
                        'ops' => [['CTRL', 'Mesure tridimensionnelle et rapport', 0, 3]],
                    ],
                    ['type' => 'subtotal', 'label' => 'Sous-total prototype'],
                ],
            ],
        ];
    }

    private function mouldTemplates(): array
    {
        return [
            [
                'label'   => 'Moule – injection plastique 1 empreinte',
                'comment' => "Matière plastique pour les essais fournie par le client.\nModifications demandées après les essais T0 facturées en sus.\nLe moule reste la propriété du client après paiement intégral.",
                'lines'   => [
                    ['type' => 'section', 'label' => 'Moule 1 empreinte'],
                    [
                        'code' => 'MOU1-CAO', 'label' => 'Étude et conception CAO du moule (plan d\'implantation, 3D)', 'qty' => 1,
                        'ops' => [['CAO', 'Conception du moule', 0, 40]],
                    ],
                    [
                        'code' => 'MOU1-CARC', 'label' => 'Carcasse standard 1.1730 et éléments normalisés (éjecteurs, colonnes, bagues)', 'qty' => 1,
                        'details' => ['material' => '1.1730', 'x_size' => 296, 'y_size' => 346],
                        'bom' => [
                            ['PUR', 'Carcasse standard 296 × 346', 1450],
                            ['PUR', 'Éléments normalisés', 480],
                        ],
                        'ops' => [['FRAI', 'Reprise de la carcasse', 2, 8]],
                    ],
                    [
                        'code' => 'MOU1-EMPR', 'label' => 'Empreinte et noyau acier 1.2343 trempé 48-52 HRC', 'qty' => 1,
                        'details' => ['material' => '1.2343', 'finishing' => 'Polissage SPI A2 sur faces visibles'],
                        'bom' => [['BLOC', 'Blocs 1.2343', 420]],
                        'ops' => [
                            ['FR5X', 'Ébauche', 2, 16],
                            ['TTH', 'Trempe sous vide 48-52 HRC (sous-traitée)', 280],
                            ['FRAI', 'Finition UGV', 2, 12],
                            ['EDM', 'Électroérosion des nervures', 2, 14],
                            ['EDMF', 'Découpe fil des inserts', 1, 4],
                            ['RECT', 'Rectification des plans de joint', 2, 4],
                            ['POLI', 'Polissage', 0, 10],
                        ],
                    ],
                    [
                        'code' => 'MOU1-AJUS', 'label' => 'Ajustage, montage et circuits de régulation', 'qty' => 1,
                        'ops' => [['ASSY', 'Ajustage et montage', 0, 24]],
                    ],
                    [
                        'code' => 'MOU1-T0', 'label' => 'Essais T0 sur presse, échantillons et rapport', 'qty' => 1,
                        'ops' => [['CTRL', 'Essais et rapport', 0, 8]],
                    ],
                    ['type' => 'subtotal', 'label' => 'Total moule'],
                    ['type' => 'text', 'label' => 'Garantie : 100 000 cycles hors pièces d\'usure, sous réserve d\'un entretien conforme.'],
                ],
            ],
            [
                'label'   => 'Moule – 2 empreintes à tiroirs avec canaux chauds',
                'comment' => "Prix au forfait : le détail reste interne, seul le montant de la section est imprimé.\nMatière plastique pour les essais fournie par le client.",
                'lines'   => [
                    ['type' => 'section', 'label' => 'Moule 2 empreintes à tiroirs, canaux chauds', 'package' => 1],
                    [
                        'code' => 'MOU2-CAO', 'label' => 'Étude et conception CAO du moule', 'qty' => 1,
                        'ops' => [['CAO', 'Conception du moule', 0, 70]],
                    ],
                    [
                        'code' => 'MOU2-CARC', 'label' => 'Carcasse standard et éléments normalisés, 2 tiroirs', 'qty' => 1,
                        'bom' => [
                            ['PUR', 'Carcasse standard 396 × 446', 2600],
                            ['PUR', 'Éléments normalisés et guidages de tiroirs', 1100],
                        ],
                        'ops' => [['FRAI', 'Reprise de la carcasse', 3, 14]],
                    ],
                    [
                        'code' => 'MOU2-BCC', 'label' => 'Système canaux chauds 2 buses et régulation', 'qty' => 1,
                        'bom' => [['PUR', 'Bloc chaud 2 buses et armoire de régulation', 3200]],
                        'ops' => [['ASSY', 'Intégration du système canaux chauds', 0, 8]],
                    ],
                    [
                        'code' => 'MOU2-EMPR', 'label' => 'Empreintes et noyaux ×2 acier 1.2343 trempé', 'qty' => 1,
                        'details' => ['material' => '1.2343'],
                        'bom' => [['BLOC', 'Blocs 1.2343', 780]],
                        'ops' => [
                            ['FR5X', 'Ébauche', 3, 30],
                            ['TTH', 'Trempe sous vide (sous-traitée)', 450],
                            ['FRAI', 'Finition UGV', 3, 22],
                            ['EDM', 'Électroérosion', 3, 28],
                            ['EDMF', 'Découpe fil', 1, 8],
                            ['RECT', 'Rectification', 2, 8],
                            ['POLI', 'Polissage', 0, 18],
                        ],
                    ],
                    [
                        'code' => 'MOU2-TIR', 'label' => 'Tiroirs et coulisseaux', 'qty' => 1,
                        'bom' => [['BLOC', 'Blocs 1.2343 pour tiroirs', 260]],
                        'ops' => [['FRAI', 'Usinage des tiroirs', 2, 16], ['RECT', 'Rectification', 1, 4]],
                    ],
                    [
                        'code' => 'MOU2-AJUS', 'label' => 'Ajustage et montage', 'qty' => 1,
                        'ops' => [['ASSY', 'Ajustage et montage', 0, 40]],
                    ],
                    [
                        'code' => 'MOU2-ESSAI', 'label' => 'Essais T0 et T1, échantillons et rapport', 'qty' => 1,
                        'ops' => [['CTRL', 'Essais et rapport', 0, 14]],
                    ],
                    ['type' => 'subtotal', 'label' => 'Total moule'],
                ],
            ],
            [
                'label'   => 'Moule – remise en état et modification',
                'comment' => "Devis estimatif établi avant démontage : il sera confirmé après expertise.\nEnlèvement et retour du moule à la charge du client.",
                'lines'   => [
                    [
                        'code' => 'REP-EXP', 'label' => 'Expertise et démontage du moule', 'qty' => 1,
                        'ops' => [['ASSY', 'Démontage et nettoyage', 0, 6], ['CTRL', 'Expertise et relevé des usures', 0, 2]],
                    ],
                    [
                        'code' => 'REP-EMPR', 'label' => 'Rechargement et reprise d\'empreinte', 'qty' => 1,
                        'ops' => [
                            ['TSURF', 'Rechargement par soudure laser (sous-traité)', 350],
                            ['EDM', 'Reprise par électroérosion', 2, 6],
                            ['POLI', 'Polissage', 0, 6],
                        ],
                    ],
                    [
                        'code' => 'REP-USURE', 'label' => 'Remplacement des éléments d\'usure (éjecteurs, joints, ressorts)', 'qty' => 1,
                        'bom' => [['PUR', 'Éjecteurs, joints toriques, ressorts', 280]],
                        'ops' => [['ASSY', 'Remplacement', 0, 4]],
                    ],
                    [
                        'code' => 'REP-ESSAI', 'label' => 'Remontage, essai et validation', 'qty' => 1,
                        'ops' => [['ASSY', 'Remontage', 0, 6], ['CTRL', 'Essai et validation', 0, 4]],
                    ],
                ],
            ],
        ];
    }
}
