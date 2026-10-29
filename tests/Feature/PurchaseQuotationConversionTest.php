<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseQuotationLines;
use App\Models\Purchases\Purchases;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Demande de prix → commande d'achat, et reprise des contacts / adresses
 * inversés par l'écran « Demande d'achat » (correctif : PR #1213,
 * test PurchaseRequestDefaultContactTest).
 */
class PurchaseQuotationConversionTest extends TestCase
{
    use RefreshDatabase;

    private Companies $supplier;
    private CompaniesContacts $contact;
    private CompaniesAddresses $address;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $user = User::factory()->create();
        $this->actingAs($user);

        AccountingVat::factory()->create(['default' => 1]);
        foreach (['Open', 'Supplied', 'RFQ in progress'] as $order => $title) {
            Status::create(['title' => $title, 'order' => $order]);
        }

        $this->supplier = Companies::factory()->create();
        // Contacts et adresses d'une autre société autour de ceux du fournisseur :
        // ids différents (contact 5, adresse 3), et chaque id inversé existe
        // dans l'autre table (clés étrangères).
        // Société explicite : la fabrique d'adresses en prend une au hasard,
        // parfois le fournisseur.
        $other = ['companies_id' => Companies::factory()->create()->id];
        CompaniesContacts::factory()->count(4)->create($other);
        CompaniesAddresses::factory()->count(2)->create($other);
        $this->contact = CompaniesContacts::factory()->create(['companies_id' => $this->supplier->id, 'default' => 1]);
        $this->address = CompaniesAddresses::factory()->create(['companies_id' => $this->supplier->id, 'default' => 1]);
        CompaniesAddresses::factory()->count(2)->create($other);
        $this->assertNotSame($this->contact->id, $this->address->id);
    }

    private function quotation(): PurchasesQuotation
    {
        return PurchasesQuotation::create([
            'code' => 'DP-1',
            'label' => 'DP-1',
            'companies_id' => $this->supplier->id,
            'companies_contacts_id' => $this->contact->id,
            'companies_addresses_id' => $this->address->id,
            'user_id' => auth()->id(),
        ]);
    }

    public function test_converting_only_a_later_line_keeps_its_own_task_and_price(): void
    {
        $quotation = $this->quotation();
        $task = Task::factory()->create(['unit_cost' => 5]);
        $taskStatus = $task->status_id;

        $withTask = PurchaseQuotationLines::create([
            'purchases_quotation_id' => $quotation->id, 'tasks_id' => $task->id, 'ordre' => 10,
            'label' => 'Ligne tâche', 'qty_to_order' => 3, 'unit_price' => 10, 'total_price' => 30,
        ]);
        $generic = PurchaseQuotationLines::create([
            'purchases_quotation_id' => $quotation->id, 'tasks_id' => 0, 'ordre' => 20,
            'label' => 'Ligne générique', 'qty_to_order' => 4, 'unit_price' => 20, 'total_price' => 80,
        ]);

        // Seule la seconde ligne est cochée ; les prix de toutes les lignes partent.
        $this->post(route('purchases.orders.store', ['id' => $quotation->id]), [
            'PurchaseQuotationLine' => [$generic->id],
            'PurchaseQuotationLinePrice' => [$withTask->id => 11, $generic->id => 22],
        ])->assertRedirect();

        $lines = PurchaseLines::all();
        $this->assertCount(1, $lines);
        $this->assertSame(0, (int) $lines[0]->tasks_id);
        $this->assertSame('Ligne générique', $lines[0]->label);
        $this->assertEquals(22, $lines[0]->getRawOriginal('selling_price'));

        $this->assertEquals(4, $generic->fresh()->qty_accepted);
        $this->assertEquals(0, (int) $withTask->fresh()->qty_accepted);
        $this->assertSame($taskStatus, $task->fresh()->status_id);
    }

    public function test_a_line_of_another_quotation_is_ignored(): void
    {
        $quotation = $this->quotation();
        $other = $this->quotation();
        $foreign = PurchaseQuotationLines::create([
            'purchases_quotation_id' => $other->id, 'tasks_id' => 0, 'ordre' => 10,
            'label' => 'Autre', 'qty_to_order' => 1, 'unit_price' => 1, 'total_price' => 1,
        ]);

        $this->post(route('purchases.orders.store', ['id' => $quotation->id]), [
            'PurchaseQuotationLine' => [$foreign->id],
        ])->assertSessionHasErrors('msg');

        $this->assertSame(0, Purchases::count());
    }

    public function test_repair_command_swaps_back_only_certain_inversions(): void
    {
        $swapped = $this->quotation();
        DB::table('purchases_quotations')->where('id', $swapped->id)->update([
            'companies_contacts_id' => $this->address->id,
            'companies_addresses_id' => $this->contact->id,
        ]);
        $correct = $this->quotation();

        $this->artisan('wem:purchases:fix-swapped-contacts', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame($this->address->id, (int) $swapped->fresh()->companies_contacts_id);

        $this->artisan('wem:purchases:fix-swapped-contacts')->assertSuccessful();

        foreach ([$swapped, $correct] as $document) {
            $document = $document->fresh();
            $this->assertSame($this->contact->id, (int) $document->companies_contacts_id);
            $this->assertSame($this->address->id, (int) $document->companies_addresses_id);
        }
    }
}
