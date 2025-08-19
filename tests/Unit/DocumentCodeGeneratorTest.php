<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DocumentCodeGenerator;
use App\Models\DocumentCodeTemplate;
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

        $this->assertSame('INVOICE-0', $code);
    }
}
