<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Accounting\AccountingVat;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AllocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Créer les enregistrements de TVA
        $vat0 = AccountingVat::factory()->create(['rate' => 0]);
        $vat5 = AccountingVat::factory()->create(['rate' => 5]);
        $vat10 = AccountingVat::factory()->create(['rate' => 10]);
        $vat20 = AccountingVat::factory()->create(['rate' => 20]);

        // Insérer les enregistrements dans la table allocations
        DB::table('allocations')->insert([
            // Exonéré de TVA (0%) - Vente
            [
                'account' => '707000',
                'label' => 'Vente Exonérée de TVA',
                'accounting_vats_id' => $vat0->id,  // Utilisation de l'ID de la TVA 0
                'vat_account' => '445700',
                'code_account' => '701',
                'type_imputation' => 1
            ],
            // Exonéré de TVA (0%) - Achat
            [
                'account' => '607000',
                'label' => 'Achat Exonéré de TVA',
                'accounting_vats_id' => $vat0->id,  // Utilisation de l'ID de la TVA 0
                'vat_account' => '445700',
                'code_account' => '601 ',
                'type_imputation' => 2
            ],
            // TVA 5% - Vente
            [
                'account' => '707002',
                'label' => 'Vente à taux réduit (5%)',
                'accounting_vats_id' => $vat5->id,  // Utilisation de l'ID de la TVA 5
                'vat_account' => '445711',
                'code_account' => '701',
                'type_imputation' => 1
            ],
            // TVA 5% - Achat
            [
                'account' => '607002',
                'label' => 'Achat à taux réduit (5%)',
                'accounting_vats_id' => $vat5->id,  // Utilisation de l'ID de la TVA 5
                'vat_account' => '445711',
                'code_account' => '601 ',
                'type_imputation' => 2
            ],
            // TVA 10% - Vente
            [
                'account' => '707010',
                'label' => 'Vente à taux intermédiaire (10%)',
                'accounting_vats_id' => $vat10->id,  // Utilisation de l'ID de la TVA 10
                'vat_account' => '445712',
                'code_account' => '701',
                'type_imputation' => 1
            ],
            // TVA 10% - Achat
            [
                'account' => '607010',
                'label' => 'Achat à taux intermédiaire (10%)',
                'accounting_vats_id' => $vat10->id,  // Utilisation de l'ID de la TVA 10
                'vat_account' => '445712',
                'code_account' => '601 ',
                'type_imputation' => 2
            ],
            // TVA 20% - Vente
            [
                'account' => '707020',
                'label' => 'Vente à taux normal (20%)',
                'accounting_vats_id' => $vat20->id,  // Utilisation de l'ID de la TVA 20
                'vat_account' => '445713',
                'code_account' => '701',
                'type_imputation' => 1
            ],
            // TVA 20% - Achat
            [
                'account' => '607020',
                'label' => 'Achat à taux normal (20%)',
                'accounting_vats_id' => $vat20->id,  // Utilisation de l'ID de la TVA 20
                'vat_account' => '445713',
                'code_account' => '601 ',
                'type_imputation' => 2
            ],
        ]);
    }
}
