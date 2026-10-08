<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Accounting\VatRegime;
use App\Models\Accounting\VatNature;

/**
 * Vocabulaire initial des deux axes de la matrice de TVA : régimes (tiers) et
 * natures (ligne). Idempotent (firstOrCreate) : relançable sans doublon.
 * Les règles de la matrice elle-même restent à la main de l'administrateur.
 */
class VatReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $regimes = [
            'FR'        => 'France (assujetti)',
            'FRANCHISE' => 'Franchise sur attestation (art. 275 CGI)',
            'UE'        => 'Union européenne (B2B)',
            'EXPORT'    => 'Export hors UE',
            'AUTOLIQ'   => 'Autoliquidation',
        ];
        foreach ($regimes as $code => $label) {
            VatRegime::firstOrCreate(['code' => $code], ['label' => $label]);
        }

        $natures = [
            'fabrication'    => 'Fabrication (matière + usinage, bien)',
            'prestation'     => 'Prestation (façon sur matière client, service)',
            'port'           => 'Port / frais de transport',
            'acompte'        => 'Acompte',
            'matiere'        => 'Matière',
            'marchandise'    => 'Marchandise',
            'sous_traitance' => 'Sous-traitance',
            'frais'          => 'Frais / prestation ponctuelle',
            'immobilisation' => 'Immobilisation',
        ];
        foreach ($natures as $code => $label) {
            VatNature::firstOrCreate(['code' => $code], ['label' => $label]);
        }
    }
}
