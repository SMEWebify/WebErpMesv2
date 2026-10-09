<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Accounting\VatRegime;
use App\Models\Accounting\VatNature;
use App\Models\Accounting\AccountingVat;
use App\Models\Accounting\AccountingVatSalesRule;
use App\Models\Accounting\AccountingVatPurchaseRule;

/**
 * Matrice de TVA de départ pour une PME industrielle française.
 *
 * Crée les codes de TVA à 0 % qui portent une catégorie EN 16931 (intracom,
 * export, autoliquidation, franchise art. 275), puis une règle par couple
 * régime × nature, ventes et achats.
 *
 * Sans danger sur une base en production : ne fait qu'AJOUTER ce qui manque
 * (recherche par code de TVA, par couple régime × nature), ne modifie ni ne
 * supprime jamais une valeur existante. Relançable.
 *
 * Les comptes sont ceux du plan comptable général : un point de départ à faire
 * valider par l'expert-comptable de chaque société déployée.
 *
 *   php artisan db:seed --class=VatMatrixSeeder
 */
class VatMatrixSeeder extends Seeder
{
    /** Natures « bien » : la ligne suit le régime des livraisons de biens. */
    private const GOODS = ['fabrication', 'matiere', 'marchandise', 'port'];

    /** Natures « service » (façon sur matière client, sous-traitance, frais). */
    private const SERVICES = ['prestation', 'sous_traitance', 'frais'];

    /** Compte de vente par nature (classe 7). */
    private const SALES_ACCOUNTS = [
        'fabrication'    => '701000',
        'matiere'        => '707000',
        'marchandise'    => '707000',
        'port'           => '708500',
        'prestation'     => '706000',
        'sous_traitance' => '706000',
        'frais'          => '708800',
    ];

    /** Compte d'achat par nature (classe 6, ou 2 pour une immobilisation). */
    private const PURCHASE_ACCOUNTS = [
        'fabrication'    => '604000',
        'matiere'        => '601000',
        'marchandise'    => '607000',
        'port'           => '624100',
        'prestation'     => '604000',
        'sous_traitance' => '604000',
        'frais'          => '606000',
        'immobilisation' => '215400',
    ];

    public function run(): void
    {
        // Les deux axes doivent exister avant la matrice.
        $this->call(VatReferenceSeeder::class);

        $regimes = VatRegime::pluck('id', 'code');
        $natures = VatNature::pluck('id', 'code');
        $vats    = $this->vatCodes();

        $this->seedSales($regimes, $natures, $vats);
        $this->seedPurchases($regimes, $natures, $vats);
    }

    /**
     * Codes de TVA utilisés par la matrice. Le taux normal existant est repris
     * tel quel ; les codes à 0 % catégorisés sont créés s'ils manquent.
     *
     * @return array<string, AccountingVat>
     */
    private function vatCodes(): array
    {
        $standard = AccountingVat::where('rate', 20)->orderByDesc('default')->first()
            ?? AccountingVat::create([
                'code' => 'TVA20', 'label' => 'TVA 20 %', 'rate' => 20, 'en16931_category' => 'S',
            ]);

        $exempt = [
            'K' => [
                'code'                  => 'TVA0-UE',
                'label'                 => 'Livraison intracommunautaire (0 %)',
                'exemption_reason_code' => 'VATEX-EU-IC',
                'exemption_reason_text' => 'Exonération de TVA, article 262 ter I du CGI',
            ],
            'G' => [
                'code'                  => 'TVA0-EXP',
                'label'                 => 'Export hors UE (0 %)',
                'exemption_reason_code' => 'VATEX-EU-G',
                'exemption_reason_text' => 'Exonération de TVA, article 262 I du CGI',
            ],
            'AE' => [
                'code'                  => 'TVA0-AL',
                'label'                 => 'Autoliquidation (0 %)',
                'exemption_reason_code' => 'VATEX-EU-AE',
                'exemption_reason_text' => 'Autoliquidation',
            ],
            'E' => [
                'code'                  => 'TVA0-275',
                'label'                 => 'Franchise sur attestation, art. 275 (0 %)',
                'exemption_reason_code' => null,
                'exemption_reason_text' => 'Achats en franchise de TVA, article 275 du CGI',
            ],
        ];

        $codes = ['S' => $standard];
        foreach ($exempt as $category => $attributes) {
            $codes[$category] = AccountingVat::firstOrCreate(
                ['code' => $attributes['code']],
                $attributes + [
                    'rate'             => 0,
                    'en16931_category' => $category,
                    'legal_mention'    => $attributes['exemption_reason_text'],
                ]
            );
        }

        return $codes;
    }

    /**
     * Ventes : le code de TVA dépend du régime du client et, pour l'UE, de la
     * nature (bien → intracom, service → autoliquidation par le client).
     * Acompte et immobilisation ne sont pas couverts : leur traitement se
     * décide avec l'expert-comptable.
     */
    private function seedSales($regimes, $natures, array $vats): void
    {
        foreach (array_merge(self::GOODS, self::SERVICES) as $nature) {
            $isGood = in_array($nature, self::GOODS, true);

            $byRegime = [
                'FR'        => ['vat' => $vats['S'],  'vat_account' => '445710'],
                'FRANCHISE' => ['vat' => $vats['E'],  'vat_account' => null],
                'UE'        => ['vat' => $isGood ? $vats['K'] : $vats['AE'], 'vat_account' => null],
                'EXPORT'    => ['vat' => $vats['G'],  'vat_account' => null],
                'AUTOLIQ'   => ['vat' => $vats['AE'], 'vat_account' => null],
            ];

            foreach ($byRegime as $regime => $rule) {
                if (! isset($regimes[$regime], $natures[$nature])) {
                    continue; // valeur renommée ou supprimée par l'administrateur
                }

                AccountingVatSalesRule::firstOrCreate(
                    ['vat_regime_id' => $regimes[$regime], 'vat_nature_id' => $natures[$nature]],
                    [
                        'accounting_vats_id' => $rule['vat']->id,
                        'sales_account'      => self::SALES_ACCOUNTS[$nature],
                        'vat_account'        => $rule['vat_account'],
                    ]
                );
            }
        }
    }

    /**
     * Achats : TVA déductible en France ; acquisitions UE, importations et
     * autoliquidation signalées en écriture manuelle (`manual_vat`), la double
     * écriture TVA due ↔ déductible n'étant pas automatisée.
     * Le régime FRANCHISE ne concerne que les ventes (attestation du client).
     */
    private function seedPurchases($regimes, $natures, array $vats): void
    {
        foreach (self::PURCHASE_ACCOUNTS as $nature => $account) {
            $deductible = $nature === 'immobilisation' ? '445620' : '445660';

            $byRegime = [
                'FR'      => ['vat' => $vats['S'],  'autoliq' => null,     'manual' => false],
                'UE'      => ['vat' => $vats['AE'], 'autoliq' => '445200', 'manual' => true],
                'EXPORT'  => ['vat' => $vats['AE'], 'autoliq' => null,     'manual' => true],
                'AUTOLIQ' => ['vat' => $vats['AE'], 'autoliq' => null,     'manual' => true],
            ];

            foreach ($byRegime as $regime => $rule) {
                if (! isset($regimes[$regime], $natures[$nature])) {
                    continue;
                }

                AccountingVatPurchaseRule::firstOrCreate(
                    ['vat_regime_id' => $regimes[$regime], 'vat_nature_id' => $natures[$nature]],
                    [
                        'accounting_vats_id'  => $rule['vat']->id,
                        'purchase_account'    => $account,
                        'vat_account'         => $deductible,
                        'vat_account_autoliq' => $rule['autoliq'],
                        'manual_vat'          => $rule['manual'],
                    ]
                );
            }
        }
    }
}
