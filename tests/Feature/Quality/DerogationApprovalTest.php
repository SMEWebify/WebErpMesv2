<?php

namespace Tests\Feature\Quality;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Quality\QualityDerogation;
use App\Models\Quality\QualityNonConformity;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Dérogations : validation sous preuve d'accord client, droits, verrou après
 * validation, et blocage des BL tant que le client n'a pas répondu.
 */
class DerogationApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Companies $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        Storage::fake(config('files.disk'));
        Permission::findOrCreate(QualityDerogation::VALIDATE_PERMISSION);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->company = Companies::factory()->create(['statu_customer' => 2]);
        CompaniesContacts::factory()->create(['companies_id' => $this->company->id]);
        CompaniesAddresses::factory()->create(['companies_id' => $this->company->id]);
    }

    private function validator(): User
    {
        $this->user->givePermissionTo(QualityDerogation::VALIDATE_PERMISSION);

        return $this->user;
    }

    private function external(array $attributes = []): QualityDerogation
    {
        return QualityDerogation::factory()->create(array_merge([
            'type'     => QualityDerogation::TYPE_EXTERNAL,
            'statu'    => QualityDerogation::STATU_WAITING_CUSTOMER,
            'user_id'  => $this->user->id,
        ], $attributes));
    }

    private function form(QualityDerogation $derogation, array $overrides = []): array
    {
        return array_merge([
            'label'    => $derogation->label,
            'statu'    => $derogation->statu,
            'user_id'  => $derogation->user_id,
            'pb_descp' => $derogation->pb_descp,
            'proposal' => $derogation->proposal,
        ], $overrides);
    }

    private function attachProof(QualityDerogation $derogation, string $role = FileRole::CUSTOMER_APPROVAL): void
    {
        $files = app(FileStorageService::class);
        $file = $files->storeContents('%PDF-1.4 accord', 'accord.pdf', 'application/pdf');
        $files->attach($file, $derogation, $role);
    }

    /* ───────────────────────────── Validation ───────────────────────────── */

    public function test_an_external_derogation_needs_the_customer_proof_to_be_validated(): void
    {
        $this->validator();
        $derogation = $this->external();

        $this->post(route('quality.derogation.update', ['id' => $derogation->id]), $this->form($derogation, [
            'statu'    => 3,
            'decision' => 'Accepté',
        ]))->assertSessionHasErrors('statu');

        $this->assertSame(QualityDerogation::STATU_WAITING_CUSTOMER, (int) $derogation->fresh()->statu);
    }

    public function test_an_external_derogation_with_its_proofs_is_validated_and_stamped(): void
    {
        $this->validator();
        $derogation = $this->external();
        $this->attachProof($derogation);

        $this->post(route('quality.derogation.update', ['id' => $derogation->id]), $this->form($derogation, [
            'statu'               => 3,
            'companies_id'        => $this->company->id,
            'reply'               => 'Accord pour 12 pièces',
            'customer_reply_date' => '2026-10-27',
            'customer_signatory'  => 'M. Dupont, responsable qualité',
            'decision'            => 'Livraison en l\'état',
        ]))->assertSessionHasNoErrors();

        $derogation->refresh();
        $this->assertTrue($derogation->isValidated());
        $this->assertSame($this->user->id, (int) $derogation->validated_by);
        $this->assertNotNull($derogation->validated_at);
        $this->assertSame('2026-10-27', $derogation->customer_reply_date->toDateString());

        // L'historique garde qui a validé.
        $this->assertTrue(Activity::forSubject($derogation)->where('causer_id', $this->user->id)->exists());
    }

    public function test_validating_requires_the_permission(): void
    {
        $derogation = QualityDerogation::factory()->create(['user_id' => $this->user->id]);

        $this->post(route('quality.derogation.update', ['id' => $derogation->id]), $this->form($derogation, [
            'statu'       => 3,
            'type_update' => 'on',
            'decision'    => 'Accepté en interne',
        ]))->assertSessionHasErrors('statu');

        $this->assertFalse($derogation->fresh()->isValidated());
    }

    public function test_an_internal_derogation_only_needs_the_decision(): void
    {
        $this->validator();
        $derogation = QualityDerogation::factory()->create(['user_id' => $this->user->id]);

        $this->post(route('quality.derogation.update', ['id' => $derogation->id]), $this->form($derogation, [
            'statu'       => 3,
            'type_update' => 'on',
            'decision'    => 'Accepté en interne',
        ]))->assertSessionHasNoErrors();

        $this->assertTrue($derogation->fresh()->isValidated());
    }

    public function test_a_validated_derogation_is_locked_for_users_without_the_permission(): void
    {
        $derogation = $this->external(['statu' => QualityDerogation::STATU_VALIDATED, 'decision' => 'OK']);

        $this->post(route('quality.derogation.update', ['id' => $derogation->id]), $this->form($derogation, [
            'label' => 'Modifié',
        ]))->assertSessionHasErrors('statu');

        $this->assertNotSame('Modifié', $derogation->fresh()->label);
    }

    public function test_company_and_order_line_are_taken_from_the_non_conformity(): void
    {
        $order = Orders::factory()->create(['companies_id' => $this->company->id]);
        $line = OrderLines::factory()->create(['orders_id' => $order->id]);
        $nc = QualityNonConformity::create([
            'code' => 'NC-1', 'label' => 'NC', 'statu' => 1, 'type' => 2, 'user_id' => $this->user->id,
            'companie_id' => $this->company->id, 'order_lines_id' => $line->id,
        ]);

        $this->post(route('quality.derogation.create'), [
            'code'                       => 'DER-100',
            'label'                      => 'Cote hors tolérance',
            'statu'                      => 1,
            'user_id'                    => $this->user->id,
            'quality_non_conformitie_id' => $nc->id,
            'pb_descp'                   => 'Perçage décalé de 0,2 mm',
            'proposal'                   => 'Livrer en l\'état',
        ])->assertRedirect();

        $derogation = QualityDerogation::where('code', 'DER-100')->sole();
        $this->assertTrue($derogation->isExternal());
        $this->assertSame($this->company->id, (int) $derogation->companies_id);
        $this->assertSame($line->id, (int) $derogation->order_lines_id);
    }

    public function test_the_record_page_renders(): void
    {
        $derogation = $this->external(['companies_id' => $this->company->id]);

        $this->get(route('quality.derogation.show', ['id' => $derogation->id]))
            ->assertOk()
            ->assertSee($derogation->code)
            ->assertSee(__('derogations.missing_proof'));

        $this->get(route('quality.derogation'))->assertOk()->assertSee(route('quality.derogation.show', ['id' => $derogation->id]), false);
    }

    /* ───────────────────────────── Livraison ───────────────────────────── */

    private function orderWithLine(): array
    {
        $order = Orders::factory()->create(['statu' => 1, 'companies_id' => $this->company->id]);
        $line = OrderLines::factory()->create([
            'orders_id' => $order->id, 'qty' => 10, 'delivered_qty' => 0, 'delivered_remaining_qty' => 10,
            'selling_price' => 0, 'invoiced_remaining_qty' => 0,
        ]);

        return [$order, $line];
    }

    public function test_a_line_waiting_for_the_customer_decision_cannot_be_delivered(): void
    {
        [$order, $line] = $this->orderWithLine();
        $derogation = $this->external(['order_lines_id' => $line->id]);

        $this->postJson(route('orders.lines.json.store-delivery', ['orderId' => $order->id]), ['line_ids' => [$line->id]])
            ->assertStatus(422)
            ->assertJsonPath('error', fn ($message) => str_contains($message, $derogation->code));

        $this->postJson(route('deliverys-request.store'), [
            'code'                   => 'BL-DER-001',
            'label'                  => 'BL',
            'companies_id'           => $this->company->id,
            'companies_addresses_id' => $order->companies_addresses_id,
            'companies_contacts_id'  => $order->companies_contacts_id,
            'user_id'                => $this->user->id,
            'lines'                  => [['order_line_id' => $line->id, 'qty' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors('lines');

        $this->assertSame(0.0, (float) $line->fresh()->delivered_qty);
    }

    public function test_a_derogation_reached_through_the_non_conformity_also_blocks(): void
    {
        [$order, $line] = $this->orderWithLine();
        $nc = QualityNonConformity::create([
            'code' => 'NC-2', 'label' => 'NC', 'statu' => 1, 'type' => 2, 'user_id' => $this->user->id,
            'order_lines_id' => $line->id,
        ]);
        $this->external(['quality_non_conformitie_id' => $nc->id]);

        $this->postJson(route('orders.lines.json.store-delivery', ['orderId' => $order->id]), ['line_ids' => [$line->id]])
            ->assertStatus(422);
    }

    public function test_validated_cancelled_or_internal_derogations_do_not_block(): void
    {
        [$order, $line] = $this->orderWithLine();
        $this->external(['order_lines_id' => $line->id, 'statu' => QualityDerogation::STATU_VALIDATED]);
        $this->external(['order_lines_id' => $line->id, 'statu' => QualityDerogation::STATU_CANCELED]);
        QualityDerogation::factory()->create(['order_lines_id' => $line->id, 'user_id' => $this->user->id]);

        $this->postJson(route('orders.lines.json.store-delivery', ['orderId' => $order->id]), ['line_ids' => [$line->id]])
            ->assertOk()
            ->assertJsonStructure(['redirect']);
    }

    /* ───────────────────────────── GED ───────────────────────────── */

    public function test_documents_can_be_attached_to_a_derogation(): void
    {
        $derogation = $this->external();

        $this->getJson(route('files.json.list', ['fileable_type' => 'derogation', 'fileable_id' => $derogation->id]))
            ->assertOk();

        $this->attachProof($derogation);
        $this->assertTrue(app(\App\Services\Quality\DerogationApproval::class)->hasProof($derogation));
    }
}
