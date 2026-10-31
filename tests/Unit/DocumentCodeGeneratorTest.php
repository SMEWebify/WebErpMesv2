<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DocumentCodeGenerator;
use App\Models\DocumentCodeTemplate;
use App\Models\Workflow\Deliverys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class DocumentCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_code_from_template(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 2));
        DocumentCodeTemplate::create([
            'document_type' => 'invoice',
            'template' => 'INV-{year}{month}{day}-{id}',
        ]);

        $generator = new DocumentCodeGenerator();
        $code = $generator->generateDocumentCode('invoice', 5);

        $this->assertSame('INV-20240102-6', $code);
    }

    public function test_it_uses_default_template_when_none_exists(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 2));
        $generator = new DocumentCodeGenerator();

        $code = $generator->generateDocumentCode('invoice');

        $this->assertSame('INVOICE-1', $code);
    }

    /**
     * Un écran enregistre le code lu par peekNextCode() sans consommer le compteur : le code
     * suivant était alors déjà pris (422, ou 500 pour une facture créée depuis un BL).
     */
    public function test_a_code_already_taken_is_skipped(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 2));
        DocumentCodeTemplate::updateOrCreate(['document_type' => 'delivery'], ['template' => 'DELIVERY-{id}', 'reset_period' => 'none']);
        $generator = new DocumentCodeGenerator();
        Deliverys::factory()->create(['code' => 'DELIVERY-1']);
        Deliverys::factory()->create(['code' => 'DELIVERY-2']);

        $this->assertSame('DELIVERY-3', $generator->peekNextCode('delivery'));
        $this->assertSame('DELIVERY-3', $generator->generateDocumentCode('delivery'));
        $this->assertSame('DELIVERY-4', $generator->peekNextCode('delivery'));
    }

    public function test_the_proposed_code_is_consumed_and_a_typed_one_is_not(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 2));
        DocumentCodeTemplate::updateOrCreate(['document_type' => 'delivery'], ['template' => 'DELIVERY-{id}', 'reset_period' => 'none']);
        $generator = new DocumentCodeGenerator();

        $generator->consumeIfProposed('delivery', 'BL-SAISI-A-LA-MAIN');
        $this->assertSame('DELIVERY-1', $generator->peekNextCode('delivery'));

        // Consommé avant l'enregistrement : le compteur avance même sans document en base.
        $generator->consumeIfProposed('delivery', 'DELIVERY-1');
        $this->assertSame('DELIVERY-2', $generator->peekNextCode('delivery'));
    }
}
