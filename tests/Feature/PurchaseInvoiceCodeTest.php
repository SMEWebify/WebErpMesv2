<?php

namespace Tests\Feature;

use App\Models\Companies\Companies;
use App\Models\Purchases\PurchaseInvoice;
use App\Models\User;
use App\Services\DocumentCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « En attente de facturation » proposait toujours le même code de facture d'achat : le code
 * proposé n'était jamais consommé, et la deuxième facture était refusée (« code déjà pris »).
 */
class PurchaseInvoiceCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->actingAs(User::factory()->create());
    }

    /** @test */
    public function a_code_already_taken_is_never_proposed()
    {
        $taken = app(DocumentCodeGenerator::class)->peekNextCode('purchase-invoice');
        PurchaseInvoice::create([
            'code' => $taken,
            'label' => $taken,
            'companies_id' => Companies::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $proposed = $this->getJson(route('purchases.waiting.invoice.json.init'))
            ->assertOk()
            ->json('initial_code');

        $this->assertNotSame($taken, $proposed);
        $this->assertFalse(PurchaseInvoice::where('code', $proposed)->exists());
    }
}
