<?php

namespace Tests\Feature\Nest2Bend;

use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\Integrations\Nest2BendSetting;
use App\Models\Methods\MethodsUnits;
use App\Models\User;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Files\FileStorageService;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

/**
 * Faux NEST2BEND (Http::fake) et données communes aux tests de l'intégration.
 */
trait Nest2BendTestHelpers
{
    protected const N2B = 'https://app.nest2bend.test';
    protected const KEY = 'n2b_test_0123456789abcdefSECRET';

    protected User $user;

    protected function setUpNest2Bend(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        Storage::fake(config('files.disk'));

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(Permission::findOrCreate('your-company-menu'));
        $this->actingAs($this->user);

        app()->forgetInstance('Factory');
        app()->instance('Factory', Factory::create([
            'name' => 'Tôlerie', 'address' => '1 rue', 'zipcode' => '35000', 'city' => 'Rennes',
            'phone_number' => '0', 'mail' => 'a@b.test', 'curency' => 'EUR', 'pdf_header_font_color' => '#dddddd',
        ]));
    }

    protected function linkNest2Bend(string $status = Nest2BendSetting::STATUS_ACTIVE, array $extra = []): Nest2BendSetting
    {
        return Nest2BendSetting::create(array_merge([
            'base_url'    => self::N2B,
            'api_key'     => self::KEY,
            'status'      => $status,
            'etat'        => $status === Nest2BendSetting::STATUS_ACTIVE ? 'actif' : 'lecture_seule',
            'peut_ecrire' => $status === Nest2BendSetting::STATUS_ACTIVE,
            'societe'     => 'Tôlerie Martin',
            'checked_at'  => now(),
        ], $extra));
    }

    protected function account(array $droits = [], string $societe = 'Tôlerie Martin'): array
    {
        return [
            'societe' => $societe,
            'droits'  => array_merge([
                'etat' => 'actif', 'modules' => ['socle', 'collision'], 'presses_max' => 3,
                'echeance' => '2027-01-31T00:00:00+00:00', 'peut_ecrire' => true,
            ], $droits),
        ];
    }

    protected function quoteLine(array $details = []): QuoteLines
    {
        $vat  = AccountingVat::factory()->create(['rate' => 20, 'default' => 1]);
        $unit = MethodsUnits::factory()->create(['code' => 'U', 'label' => 'Unité', 'default' => 1]);
        $quote = Quotes::factory()->create(['statu' => 1, 'code' => 'DV-2026-0142']);

        $line = QuoteLines::create([
            'quotes_id' => $quote->id, 'ordre' => 3, 'code' => 'SUP-G', 'label' => 'Support gauche',
            'qty' => 50, 'selling_price' => 12, 'discount' => 0,
            'methods_units_id' => $unit->id, 'accounting_vats_id' => $vat->id,
        ]);
        QuoteLineDetails::create(['quote_lines_id' => $line->id] + $details);

        return $line;
    }

    /** STEP rangé dans la GED de la ligne ; contenu volontairement en Latin-1. */
    protected function attachStep(QuoteLines $line, string $name = 'support-gauche.step'): string
    {
        $contents = "ISO-10303-21;\nHEADER;\nFILE_NAME('pi\xE8ce');\nENDSEC;\nEND-ISO-10303-21;\n";
        $storage = app(FileStorageService::class);
        $file = $storage->storeContents($contents, $name, 'application/step');
        $storage->attach($file, $line, 'modele_3d', true);

        return $contents;
    }
}
