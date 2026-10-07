<?php

namespace Tests\Feature;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\User;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\OpportunitiesEventsLogs;
use App\Models\Workflow\OpportunityVisits;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpportunityVisitTest extends TestCase
{
    private User $owner;
    private Opportunities $opportunity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);
        $this->withoutVite();

        $this->owner = User::factory()->create();
        $this->actingAs($this->owner);

        $company = Companies::factory()->create(['user_id' => $this->owner->id]);
        $this->opportunity = Opportunities::factory()->create([
            'companies_id'           => $company->id,
            'companies_contacts_id'  => CompaniesContacts::factory()->create(['companies_id' => $company->id])->id,
            'companies_addresses_id' => CompaniesAddresses::factory()->create(['companies_id' => $company->id])->id,
            'user_id'                => $this->owner->id,
            'leads_id'               => null,
        ]);

        config([
            'ai.providers.ovh.api_key' => 'ovh-test-key',
            'ai.providers.ovh.base_url' => 'https://ovh.test/v1',
            'ai.providers.claude.api_key' => 'claude-test-key',
        ]);
    }

    private function draft(array $attributes = []): OpportunityVisits
    {
        return $this->opportunity->visits()->create($attributes + [
            'user_id' => $this->owner->id,
            'visited_at' => '2026-10-07 10:00:00',
            'statu' => OpportunityVisits::STATU_DRAFT,
        ]);
    }

    public function test_visit_page_renders_and_resumes_the_users_draft(): void
    {
        $this->get(route('opportunities.visit', $this->opportunity->id))
            ->assertOk()
            ->assertSee('data-react="opportunity-visit"', false);

        $visit = $this->draft(['notes' => 'Accès par le quai nord']);

        $this->get(route('opportunities.visit', $this->opportunity->id))
            ->assertOk()
            ->assertSee('"photo_hashtag":"visite-' . $visit->id . '"', false);

        $this->assertSame($visit->id, $this->opportunity->visits()->sole()->id);
    }

    public function test_starting_a_visit_creates_a_draft(): void
    {
        $this->postJson(route('opportunities.visits.store', $this->opportunity->id))
            ->assertCreated()
            ->assertJsonPath('visit.is_draft', true);

        $this->assertDatabaseHas('opportunity_visits', [
            'opportunities_id' => $this->opportunity->id,
            'user_id' => $this->owner->id,
            'statu' => OpportunityVisits::STATU_DRAFT,
        ]);
    }

    public function test_measurements_are_saved_and_empty_rows_dropped(): void
    {
        $visit = $this->draft();

        $this->patchJson(route('opportunities.visits.update', $visit), [
            'notes' => 'Portail coulissant',
            'measurements' => [
                ['label' => 'Largeur portail', 'value' => '4200', 'unit' => 'mm', 'note' => ''],
                ['label' => '', 'value' => '', 'unit' => 'mm', 'note' => ''],
            ],
        ])->assertOk();

        $visit->refresh();
        $this->assertSame('Portail coulissant', $visit->notes);
        $this->assertSame([
            ['label' => 'Largeur portail', 'value' => '4200', 'unit' => 'mm', 'note' => null],
        ], $visit->measurements);
    }

    public function test_someone_else_cannot_edit_a_draft(): void
    {
        $visit = $this->draft();

        $this->actingAs(User::factory()->create())
            ->patchJson(route('opportunities.visits.update', $visit), ['notes' => 'x'])
            ->assertForbidden();
    }

    public function test_voice_memo_is_transcribed_appended_and_not_kept(): void
    {
        Http::fake(['https://ovh.test/v1/audio/transcriptions' => Http::response(['text' => 'Deux garde-corps de trois mètres.'])]);

        $visit = $this->draft(['transcript' => 'Premier mémo.']);
        $before = $this->storedFiles();

        $this->post(route('opportunities.visits.transcribe', $visit), [
            'audio' => UploadedFile::fake()->create('blob', 40, 'audio/webm'),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('text', 'Deux garde-corps de trois mètres.');

        $this->assertSame("Premier mémo.\n\nDeux garde-corps de trois mètres.", $visit->fresh()->transcript);

        Http::assertSent(fn (HttpRequest $request) => $request->hasHeader('Authorization', 'Bearer ovh-test-key')
            && $request->isMultipart());

        // Aucun fichier audio n'est écrit dans le stockage de l'application.
        $this->assertSame($before, $this->storedFiles());
    }

    public function test_transcription_is_refused_without_ovh_key(): void
    {
        config(['ai.providers.ovh.api_key' => null]);
        Http::fake();

        $visit = $this->draft();

        $this->post(route('opportunities.visits.transcribe', $visit), [
            'audio' => UploadedFile::fake()->create('blob', 40, 'audio/webm'),
        ], ['Accept' => 'application/json'])->assertStatus(502);

        Http::assertNothingSent();
    }

    public function test_report_is_drafted_from_the_visit(): void
    {
        Http::fake(['https://api.anthropic.com/*' => Http::response([
            'model' => 'claude-haiku-4-5-20251001',
            'content' => [['type' => 'text', 'text' => "BESOIN EXPRIMÉ\n- Garde-corps"]],
            'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
        ])]);

        $visit = $this->draft([
            'measurements' => [['label' => 'Longueur', 'value' => '3000', 'unit' => 'mm', 'note' => null]],
            'transcript' => 'Deux garde-corps.',
        ]);

        $this->postJson(route('opportunities.visits.report', $visit))
            ->assertOk()
            ->assertJsonPath('visit.report', "BESOIN EXPRIMÉ\n- Garde-corps");

        Http::assertSent(fn (HttpRequest $request) => str_contains($request['messages'][0]['content'], 'Longueur : 3000 mm')
            && str_contains($request['messages'][0]['content'], 'Deux garde-corps.'));
    }

    public function test_report_needs_material(): void
    {
        Http::fake();

        $this->postJson(route('opportunities.visits.report', $this->draft()))->assertStatus(502);

        Http::assertNothingSent();
    }

    public function test_validating_creates_an_onsite_visit_event_and_locks_the_visit(): void
    {
        $visit = $this->draft(['report' => 'Compte rendu de la visite']);

        $this->postJson(route('opportunities.visits.validate', $visit))
            ->assertOk()
            ->assertJsonPath('visit.is_draft', false);

        $event = OpportunitiesEventsLogs::sole();
        $this->assertSame(3, (int) $event->type);
        $this->assertSame('Compte rendu de la visite', $event->comment);
        $this->assertSame($event->id, $visit->fresh()->opportunities_events_logs_id);

        $this->patchJson(route('opportunities.visits.update', $visit), ['notes' => 'trop tard'])
            ->assertStatus(409);
    }

    public function test_validating_requires_a_report_or_notes(): void
    {
        $this->postJson(route('opportunities.visits.validate', $this->draft()))->assertStatus(422);

        $this->assertSame(0, OpportunitiesEventsLogs::count());
    }

    public function test_opportunity_page_lists_visits_and_loads_react_bundle(): void
    {
        $this->draft();

        $this->get(route('opportunities.show', $this->opportunity->id))
            ->assertOk()
            ->assertSee(route('opportunities.visit', $this->opportunity->id), false);
    }

    /**
     * @return array<int, string>
     */
    private function storedFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(storage_path('app'), \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            $files[] = $file->getPathname();
        }
        sort($files);

        return $files;
    }
}
