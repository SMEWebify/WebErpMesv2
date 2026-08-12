<?php

namespace App\Console\Commands;

use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use Illuminate\Console\Command;

/**
 * Prépare WEM pour un test d'émission dans le bac à sable SUPER PDP.
 *
 * Le bac à sable fournit deux sociétés fictives : Burger Queen (le vendeur, sur
 * laquelle l'application OAuth est créée) et Tricatel (l'acheteur). Pour qu'un
 * dépôt aboutisse, le Factur-X produit par WEM doit porter exactement leurs
 * identifiants — SIREN, TVA et surtout adresse électronique de facturation,
 * qui en bac à sable ne se déduit pas du SIREN.
 *
 * Ces valeurs viennent de la facture de référence de la plateforme
 * (GET /v1.beta/invoices/generate_test_invoice), pas d'une supposition.
 *
 * ⚠️ Écrase l'identité de la société dans `factory` : à ne lancer que sur une
 * base de développement.
 */
class PdpSeedSandboxCommand extends Command
{
    protected $signature = 'wem:pdp:seed-sandbox {--force : Ne pas demander confirmation}';

    protected $description = "Renseigne l'identité bac à sable SUPER PDP (vendeur Burger Queen + client Tricatel) pour tester l'émission";

    /** Vendeur : la société de WEM (table factory). */
    private const SELLER = [
        'name'               => 'Burger Queen',
        'siren'              => '000000002',
        'vat_num'            => 'FR18000000002',
        'electronic_address' => '315143296_59359',
        'address'            => '809 avenue du Languedoc',
        'zipcode'            => '12100',
        'city'               => 'Millau',
        'country'            => 'FR',
        'mail'               => 'contact@burger-queen.test',
    ];

    /** Acheteur : une société cliente dans WEM. */
    private const BUYER = [
        'label'               => 'Tricatel',
        'siren'               => '000000001',
        'intra_community_vat' => 'FR15000000001',
        'electronic_address'  => '315143296_59358',
    ];

    public function handle(): int
    {
        if (config('services.pdp.default') !== 'superpdp') {
            $this->error('PDP_DRIVER ne vaut pas « superpdp » : rien à préparer.');
            return self::FAILURE;
        }

        $factory = Factory::first();

        $this->line('Identité qui sera écrite dans <options=bold>factory</> (vendeur) :');
        $this->table(['Champ', 'Actuel', 'Nouveau'], collect(self::SELLER)
            ->map(fn ($value, $field) => [$field, $factory?->$field ?: '—', $value])
            ->values()
            ->all());

        $this->newLine();
        $this->warn("L'identité actuelle de la société sera remplacée : elle apparaît sur tous les PDF.");

        if (! $this->option('force') && ! $this->confirm('Continuer ?', false)) {
            $this->line('Annulé.');
            return self::SUCCESS;
        }

        $this->seedSeller($factory);
        $buyer = $this->seedBuyer();

        $this->newLine();
        $this->info('Bac à sable prêt.');
        $this->line("  Vendeur : Burger Queen (factory)");
        $this->line("  Client  : Tricatel (société #{$buyer->id}, code {$buyer->code})");
        $this->newLine();
        $this->line('Étapes suivantes :');
        $this->line("  1. Créer une facture de type 1 sur « Tricatel » avec au moins une ligne");
        $this->line("  2. Cliquer « Déposer sur SUPER PDP » sur la fiche facture");
        $this->line('  3. php artisan wem:pdp:sync   → les statuts fr:200 à fr:202 doivent remonter');

        return self::SUCCESS;
    }

    private function seedSeller(?Factory $factory): void
    {
        // Le singleton app('Factory') lit Factory::first() : on renseigne cette
        // ligne plutôt que d'en ajouter une seconde, qui ne serait jamais lue.
        $factory ??= new Factory();
        $factory->fill(self::SELLER + ['electronic_address_scheme' => '0225'])->save();

        app()->forgetInstance('Factory');
    }

    /** Crée ou met à jour la société cliente, avec son adresse postale (BG-8). */
    private function seedBuyer(): Companies
    {
        $buyer = Companies::firstOrNew(['label' => self::BUYER['label']]);

        $buyer->fill(self::BUYER + [
            'electronic_address_scheme' => '0225',
            'code'                      => $buyer->code ?: 'TRICATEL',
            'statu_customer'            => 2,
            'active'                    => 1,
        ])->save();

        // Sans adresse postale, le document est rejeté (BR-10 : l'acheteur doit
        // avoir une adresse, avec au minimum un code pays).
        if (! $buyer->Addresses()->exists()) {
            CompaniesAddresses::create([
                'companies_id' => $buyer->id,
                'ordre'        => 1,
                'label'        => 'Siège',
                'adress'       => '12 rue de la Gastronomie',
                'zipcode'      => '12100',
                'city'         => 'Millau',
                'country'      => 'FR',
                'mail'         => 'facturation@tricatel.test',
                'default'      => 1,
            ]);
        }

        // Le contact n'a aucune portée normative sur le Factur-X, mais WEM
        // l'exige pour créer une facture (invoices.companies_contacts_id).
        if (! $buyer->Contacts()->exists()) {
            CompaniesContacts::create([
                'companies_id' => $buyer->id,
                'ordre'        => 1,
                'civility'     => 'M.',
                'first_name'   => 'Jacques',
                'name'         => 'Tricatel',
                'function'     => 'Service comptabilité',
                'number'       => '0565600000',
                'mobile'       => '0600000000',
                'mail'         => 'facturation@tricatel.test',
                'default'      => 1,
            ]);
        }

        return $buyer;
    }
}
