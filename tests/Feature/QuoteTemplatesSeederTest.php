<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Planning\Task;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\QuoteLines;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsServices;
use App\Models\Accounting\AccountingVat;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Services\Quotes\QuoteMargin;
use Database\Seeders\QuoteTemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuoteTemplatesSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create();
        $contact = CompaniesContacts::factory()->create();
        CompaniesAddresses::factory()->create(['companies_id' => $contact->companies_id]);
        AccountingVat::factory()->create(['default' => 1]);
        MethodsUnits::create(['code' => 'UNIT', 'label' => 'Unit', 'type' => 5, 'default' => 1]);
        AccountingPaymentConditions::factory()->create();
        AccountingPaymentMethod::factory()->create();
        AccountingDelivery::factory()->create();
        // Poste déjà présent : repris tel quel, pas recréé.
        MethodsServices::create(['code' => 'LAS', 'ordre' => 1, 'label' => 'Laser maison', 'type' => 1, 'hourly_rate' => 200, 'margin' => 0, 'color' => '#000']);
    }

    public function test_it_creates_business_templates_with_routing_and_prices(): void
    {
        $this->seed(QuoteTemplatesSeeder::class);

        $templates = Quotes::onlyTemplates()->get();
        $this->assertCount(15, $templates);
        $this->assertSame(0, Quotes::count(), 'une trame reste hors des devis');
        $this->assertTrue($templates->every(fn ($t) => $t->code === 'TRAME-' . $t->id));

        $this->assertSame(1, MethodsServices::where('code', 'LAS')->count());
        $this->assertSame('Laser maison', MethodsServices::where('code', 'LAS')->value('label'));

        $articles = QuoteLines::whereIn('quotes_id', $templates->pluck('id'))->articles()->get();
        $this->assertTrue($articles->every(fn ($l) => $l->selling_price > 0 && Task::where('quote_lines_id', $l->id)->exists()));

        // Prix de la ligne = somme des prix de sa gamme : la marge interne est calculable.
        $quote  = $templates->firstWhere('label', 'Sous-traitance laser – découpe acier S235');
        $margin = app(QuoteMargin::class)->forQuote($quote);
        $this->assertNotEmpty($margin);
    }

    public function test_it_can_be_run_twice(): void
    {
        $this->seed(QuoteTemplatesSeeder::class);
        $this->seed(QuoteTemplatesSeeder::class);

        $this->assertSame(15, Quotes::onlyTemplates()->count());
    }
}
