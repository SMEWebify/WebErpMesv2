<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Companies\Companies;
use App\Models\Workflow\Opportunities;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Workflow\OpportunitiesEventsLogs;
use App\Models\Workflow\OpportunitiesActivitiesLogs;

class OpportunityCalendarTest extends TestCase
{
    private User $owner;
    private User $other;
    private Opportunities $opportunity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);

        $this->owner = User::factory()->create();
        $this->other = User::factory()->create();
        $this->actingAs($this->owner);

        $company = Companies::factory()->create(['user_id' => $this->owner->id]);
        $this->opportunity = Opportunities::factory()->create([
            'companies_id'           => $company->id,
            'companies_contacts_id'  => CompaniesContacts::factory()->create(['companies_id' => $company->id])->id,
            'companies_addresses_id' => CompaniesAddresses::factory()->create(['companies_id' => $company->id])->id,
            'user_id'                => $this->owner->id,
            'leads_id'               => null,
        ]);
    }

    private function activity(array $attributes): OpportunitiesActivitiesLogs
    {
        return OpportunitiesActivitiesLogs::create($attributes + [
            'opportunities_id' => $this->opportunity->id,
            'label'            => 'Rappeler',
            'type'             => 5,
            'statu'            => 1,
            'priority'         => 2,
            'due_date'         => '2026-10-15',
        ]);
    }

    private function feed(array $query = []): array
    {
        return $this->getJson(route('opportunities.calendar.events', $query + [
            'start' => '2026-10-01',
            'end'   => '2026-11-01',
        ]))->assertOk()->json();
    }

    public function test_calendar_page_renders(): void
    {
        $this->get(route('opportunities.calendar'))->assertOk();
    }

    public function test_feed_returns_activities_and_events_of_the_period(): void
    {
        $this->activity(['label' => 'Appel octobre']);
        $this->activity(['label' => 'Appel novembre', 'due_date' => '2026-11-20']);
        $this->activity(['label' => 'Sans échéance', 'due_date' => null]);
        OpportunitiesEventsLogs::create([
            'opportunities_id' => $this->opportunity->id,
            'label'            => 'Visite atelier',
            'type'             => 3,
            'start_date'       => '2026-09-29',
            'end_date'         => '2026-10-02',
        ]);

        $ids = collect($this->feed())->pluck('id');

        $this->assertCount(2, $ids);
        $this->assertSame(1, $ids->filter(fn ($id) => str_starts_with($id, 'activity-'))->count());
        $this->assertSame(1, $ids->filter(fn ($id) => str_starts_with($id, 'event-'))->count());
    }

    public function test_event_end_date_is_made_exclusive_for_fullcalendar(): void
    {
        OpportunitiesEventsLogs::create([
            'opportunities_id' => $this->opportunity->id,
            'label'            => 'Salon',
            'type'             => 4,
            'start_date'       => '2026-10-10',
            'end_date'         => '2026-10-12',
        ]);

        $event = $this->feed()[0];

        $this->assertSame('2026-10-10', $event['start']);
        $this->assertSame('2026-10-13', $event['end']);
    }

    public function test_user_filter_uses_line_user_then_opportunity_owner(): void
    {
        $this->activity(['label' => 'Pour le commercial', 'user_id' => null]);
        $this->activity(['label' => 'Pour un collègue', 'user_id' => $this->other->id]);

        $forOwner = collect($this->feed(['user_id' => $this->owner->id]))->pluck('extendedProps.user');
        $forOther = collect($this->feed(['user_id' => $this->other->id]))->pluck('extendedProps.user');

        $this->assertEquals([$this->owner->name], $forOwner->all());
        $this->assertEquals([$this->other->name], $forOther->all());
        $this->assertCount(2, $this->feed());
    }

    public function test_opportunity_page_shows_the_assigned_user(): void
    {
        $this->activity(['label' => 'Relance', 'user_id' => $this->other->id]);

        $this->get(route('opportunities.show', $this->opportunity->id))
            ->assertOk()
            ->assertSee($this->other->name);
    }

    public function test_activity_can_be_assigned_to_a_user(): void
    {
        $this->post(route('opportunities.store.activity', $this->opportunity->id), [
            'opportunities_id' => $this->opportunity->id,
            'user_id'          => $this->other->id,
            'label'            => 'Relance devis',
            'type'             => 5,
            'priority'         => 2,
            'due_date'         => '2026-10-20',
        ])->assertStatus(302);

        $this->assertDatabaseHas('opportunities_activities_logs', [
            'label'   => 'Relance devis',
            'user_id' => $this->other->id,
        ]);
    }
}
