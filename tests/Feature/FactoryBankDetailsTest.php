<?php

namespace Tests\Feature;

use App\Models\Admin\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Les IBAN / BIC chiffrés autrefois avec encrypt() (qui sérialise) s'affichaient
 * « s:25:"FR76 …"; » et « N; » sur l'écran Réglage société.
 */
class FactoryBankDetailsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function legacy_serialized_values_are_read_back_as_plain_text()
    {
        $factory = Factory::first();
        DB::table('factory')->where('id', $factory->id)->update([
            'iban' => Crypt::encryptString(serialize('FR76 3000 6000 0112 3456 7890 189')),
            'bic'  => Crypt::encryptString(serialize(null)),
        ]);

        $factory = Factory::find($factory->id);
        $this->assertSame('FR76 3000 6000 0112 3456 7890 189', $factory->iban);
        $this->assertNull($factory->bic);
    }

    /** @test */
    public function new_values_are_encrypted_and_read_back()
    {
        $factory = Factory::first();
        $factory->iban = 'FR76 1111 2222 3333';
        $factory->bic = 'AGRIFRPP';
        $factory->save();

        $raw = DB::table('factory')->where('id', $factory->id)->first();
        $this->assertNotSame('FR76 1111 2222 3333', $raw->iban);
        $this->assertSame('FR76 1111 2222 3333', Factory::find($factory->id)->iban);
        $this->assertSame('AGRIFRPP', Factory::find($factory->id)->bic);
    }
}
