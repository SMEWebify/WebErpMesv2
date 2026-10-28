<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Planning\Task;
use App\Models\Planning\SubAssembly;
use App\Models\Methods\MethodsServices;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\AffairCategory;
use App\Models\Workflow\RequiredOperationsOverride;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Opérations obligatoires d'une catégorie d'affaire : contrôle de la gamme des
 * lignes article à la conversion du devis en commande, avertissement sur le
 * devis, passe-droit motivé et tracé.
 */
class RequiredOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected MethodsServices $cnd;
    protected MethodsServices $painting;
    protected AffairCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->cnd      = MethodsServices::factory()->create(['code' => 'CND', 'label' => 'Contrôle non destructif']);
        $this->painting = MethodsServices::factory()->create(['code' => 'PEINT', 'label' => 'Peinture']);

        $this->category = AffairCategory::create(['code' => 'DEBIT', 'label' => 'Débitmétrie', 'ordre' => 10, 'active' => true]);
        $this->category->requiredServices()->sync([$this->cnd->id, $this->painting->id]);
    }

    private function quote(?AffairCategory $category = null): Quotes
    {
        return Quotes::factory()->create([
            'statu'              => 1,
            'user_id'            => $this->user->id,
            'affair_category_id' => ($category ?? $this->category)->id,
        ]);
    }

    private function line(Quotes $quote, array $attributes = []): QuoteLines
    {
        return QuoteLines::factory()->create(['quotes_id' => $quote->id, 'statu' => 1] + $attributes);
    }

    private function task(MethodsServices $service, array $attributes): Task
    {
        return Task::factory()->create($attributes + [
            'methods_services_id' => $service->id,
            'quote_lines_id'      => null,
            'order_lines_id'      => null,
            'products_id'         => null,
        ]);
    }

    private function convert(Quotes $quote, array $lineIds, array $extra = [])
    {
        return $this->postJson(route('quotes.lines.json.store-order', ['quoteId' => $quote->id]), ['line_ids' => $lineIds] + $extra);
    }

    private function grantOverride(): void
    {
        Permission::firstOrCreate(['name' => 'required-operations-override', 'guard_name' => 'web']);
        $this->user->givePermissionTo('required-operations-override');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_a_missing_operation_refuses_the_conversion_with_the_list(): void
    {
        $quote = $this->quote();
        $line  = $this->line($quote, ['label' => 'Débitmètre DN50']);
        $this->task($this->cnd, ['quote_lines_id' => $line->id]);

        $response = $this->convert($quote, [$line->id]);

        $response->assertStatus(422)
            ->assertJsonPath('required_operations.missing.0.line_id', $line->id)
            ->assertJsonPath('required_operations.missing.0.services.0.code', 'PEINT')
            ->assertJsonCount(1, 'required_operations.missing.0.services')
            ->assertJsonPath('required_operations.can_override', false);

        $this->assertSame(0, Orders::count());
        $this->assertSame(1, (int) $quote->fresh()->statu);
    }

    public function test_operations_found_deep_in_the_bill_of_materials_allow_the_conversion(): void
    {
        $quote = $this->quote();
        $line  = $this->line($quote);
        $this->task($this->cnd, ['quote_lines_id' => $line->id]);

        // La peinture n'est que sur un sous-ensemble de second niveau, qui ne porte que sub_assembly_id.
        $root  = SubAssembly::create(['ordre' => 1, 'quote_lines_id' => $line->id, 'child_id' => 1, 'qty' => 1, 'unit_price' => 1]);
        $child = SubAssembly::create(['ordre' => 1, 'sub_assembly_id' => $root->id, 'child_id' => 1, 'qty' => 2, 'unit_price' => 1]);
        $this->task($this->painting, ['sub_assembly_id' => $child->id]);

        $this->convert($quote, [$line->id])->assertOk()->assertJsonStructure(['redirect']);

        $this->assertSame(1, Orders::where('quotes_id', $quote->id)->count());
        $this->assertSame(0, RequiredOperationsOverride::count());
    }

    public function test_presentation_lines_and_unconverted_lines_are_not_checked(): void
    {
        $quote    = $this->quote();
        $section  = $this->line($quote, ['line_type' => 'section', 'label' => 'Lot 1', 'ordre' => 1]);
        $complete = $this->line($quote, ['ordre' => 2]);
        $this->task($this->cnd, ['quote_lines_id' => $complete->id]);
        $this->task($this->painting, ['quote_lines_id' => $complete->id]);
        $incomplete = $this->line($quote, ['ordre' => 3]);

        // Le devis signale la ligne incomplète (avertissement), pas la section.
        $warning = $this->getJson(route('quotes.lines.json.required-operations', ['quoteId' => $quote->id]))->assertOk();
        $this->assertSame([$incomplete->id], array_column($warning->json('missing'), 'line_id'));

        // Seules les lignes converties comptent.
        $this->convert($quote, [$section->id, $complete->id])->assertOk();
    }

    public function test_a_quote_without_category_or_requirement_is_not_checked(): void
    {
        $free  = AffairCategory::create(['code' => 'MECA', 'label' => 'Mécanique', 'ordre' => 20, 'active' => true]);
        $quote = $this->quote($free);
        $line  = $this->line($quote);

        $this->getJson(route('quotes.lines.json.required-operations', ['quoteId' => $quote->id]))
            ->assertOk()->assertJsonCount(0, 'missing');
        $this->convert($quote, [$line->id])->assertOk();

        $uncategorised = Quotes::factory()->create(['statu' => 1, 'user_id' => $this->user->id]);
        $this->convert($uncategorised, [$this->line($uncategorised)->id])->assertOk();
    }

    public function test_override_requires_the_permission_and_a_reason(): void
    {
        $quote = $this->quote();
        $line  = $this->line($quote);

        $this->convert($quote, [$line->id], ['required_operations_override' => 1, 'required_operations_override_reason' => 'Client pressé'])
            ->assertForbidden();

        $this->grantOverride();

        $this->convert($quote, [$line->id], ['required_operations_override' => 1, 'required_operations_override_reason' => ' '])
            ->assertStatus(422)
            ->assertJsonValidationErrors('required_operations_override_reason');

        $this->assertSame(0, Orders::count());
        $this->assertSame(0, RequiredOperationsOverride::count());
    }

    public function test_an_override_is_traced_with_the_created_order(): void
    {
        $this->grantOverride();
        $quote = $this->quote();
        $line  = $this->line($quote);
        $this->task($this->cnd, ['quote_lines_id' => $line->id]);

        $this->convert($quote, [$line->id])->assertStatus(422)->assertJsonPath('required_operations.can_override', true);

        $this->convert($quote, [$line->id], [
            'required_operations_override'        => 1,
            'required_operations_override_reason' => 'Peinture faite par le client',
        ])->assertOk();

        $order    = Orders::where('quotes_id', $quote->id)->sole();
        $override = RequiredOperationsOverride::sole();

        $this->assertSame($quote->id, $override->quotes_id);
        $this->assertSame($order->getMorphClass(), $override->document_type);
        $this->assertSame($order->id, (int) $override->document_id);
        $this->assertSame($this->category->id, $override->affair_category_id);
        $this->assertSame($this->user->id, $override->user_id);
        $this->assertSame('Peinture faite par le client', $override->reason);
        $this->assertSame($line->id, $override->missing[0]['line_id']);
        $this->assertSame(['PEINT'], array_column($override->missing[0]['services'], 'code'));

        // Le devis rappelle le passe-droit.
        $this->getJson(route('quotes.lines.json.required-operations', ['quoteId' => $quote->id]))
            ->assertOk()
            ->assertJsonPath('overrides.0.reason', 'Peinture faite par le client')
            ->assertJsonPath('overrides.0.order_code', $order->code);
    }

    public function test_required_operations_are_edited_on_the_category_screen(): void
    {
        Permission::firstOrCreate(['name' => 'methods-menu', 'guard_name' => 'web']);
        $this->user->givePermissionTo('methods-menu');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->post(route('methods.affair-category.update', ['id' => $this->category->id]), [
            'label' => 'Débitmétrie', 'ordre' => 10, 'active' => 1, 'required_services' => [$this->cnd->id],
        ])->assertRedirect(route('methods.affair-category'));
        $this->assertSame([$this->cnd->id], $this->category->requiredServices()->pluck('methods_services.id')->all());

        // Liste vide (aucune option cochée, champ absent) : plus aucune exigence.
        $this->post(route('methods.affair-category.update', ['id' => $this->category->id]), [
            'label' => 'Débitmétrie', 'ordre' => 10, 'active' => 1,
        ])->assertRedirect();
        $this->assertSame(0, $this->category->requiredServices()->count());

        $this->post(route('methods.affair-category.create'), [
            'code' => 'PYRO', 'label' => 'Pyrométrie', 'required_services' => [$this->painting->id],
        ])->assertRedirect();
        $this->assertSame(['PEINT'], AffairCategory::where('code', 'PYRO')->sole()->requiredServices()->pluck('code')->all());

        $this->get(route('methods.affair-category'))->assertOk()->assertSee('PEINT');
    }
}
