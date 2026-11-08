<?php

namespace Tests\Feature\Rgpd;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\User;
use App\Models\Workflow\Orders;
use App\Services\Rgpd\RecordRetention;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Conservation des enregistrements (ISO 19443) : rgpd:purge ne supprime jamais une
 * personne ou un tiers qu'un visa, un document ou une activité cite ;
 * wem:activitylog:clean purge chaque journal selon sa propre durée.
 */
class RecordRetentionTest extends TestCase
{
    use RefreshDatabase;

    private function trashedLongAgo($model): void
    {
        $model->delete();
        DB::table($model->getTable())->where('id', $model->id)->update(['deleted_at' => now()->subDays(100)]);
    }

    private function orderBy(User $author, array $attributes = []): Orders
    {
        return Orders::factory()->create(array_merge(['user_id' => $author->id], $attributes));
    }

    // ── rgpd:purge : utilisateurs ────────────────────────────────────────

    public function test_a_user_who_signed_a_visa_is_anonymised_and_kept(): void
    {
        $author = User::factory()->create();
        $reviewer = User::factory()->create(['name' => 'Jeanne Martin']);
        $order = $this->orderBy($author, ['reviewed_by' => $reviewer->id]);
        $this->trashedLongAgo($reviewer);

        $this->artisan('rgpd:purge')->assertSuccessful();

        $kept = User::onlyTrashed()->find($reviewer->id);
        $this->assertNotNull($kept, 'Le valideur ne doit pas être supprimé définitivement.');
        $this->assertSame('Utilisateur #' . $reviewer->id, $kept->name);
        $this->assertSame('deleted-' . $reviewer->id . '@supprime.invalid', $kept->email);
        $this->assertSame($reviewer->id, (int) $order->fresh()->reviewed_by);

        // L'anonymisation ne recopie pas l'ancien nom dans le journal (entrée « modifié »).
        $this->assertFalse(Activity::query()
            ->where('subject_type', $kept->getMorphClass())
            ->where('subject_id', $kept->id)
            ->where('event', 'updated')
            ->where('properties', 'like', '%Jeanne Martin%')
            ->exists());
    }

    public function test_a_kept_user_is_anonymised_only_once(): void
    {
        $reviewer = User::factory()->create();
        $this->orderBy(User::factory()->create(), ['reviewed_by' => $reviewer->id]);
        $this->trashedLongAgo($reviewer);

        $this->artisan('rgpd:purge')->assertSuccessful();
        $this->artisan('rgpd:purge')->assertSuccessful();

        $this->assertSame(1, Activity::query()
            ->where('subject_type', $reviewer->getMorphClass())
            ->where('subject_id', $reviewer->id)
            ->where('properties->rgpd', 'anonymisation')
            ->count());
    }

    public function test_a_user_without_any_visa_is_deleted_as_before(): void
    {
        $user = User::factory()->create();
        $this->trashedLongAgo($user);
        $recent = User::factory()->create();
        $recent->delete();

        $this->artisan('rgpd:purge')->assertSuccessful();

        $this->assertNull(User::withTrashed()->find($user->id));
        $this->assertNotNull(User::onlyTrashed()->find($recent->id), 'Moins de 90 jours : restaurable.');
    }

    public function test_the_causer_of_an_activity_is_kept(): void
    {
        $user = User::factory()->create();
        activity()->causedBy($user)->log('Modification');
        $this->trashedLongAgo($user);

        $this->artisan('rgpd:purge')->assertSuccessful();

        $this->assertSame('Utilisateur #' . $user->id, User::onlyTrashed()->find($user->id)?->name);
    }

    public function test_the_author_of_an_order_is_kept_so_the_order_is_not_cascade_deleted(): void
    {
        $author = User::factory()->create();
        $order = $this->orderBy($author);
        $this->trashedLongAgo($author);

        $this->artisan('rgpd:purge')->assertSuccessful();

        $this->assertNotNull(User::onlyTrashed()->find($author->id));
        $this->assertNotNull(Orders::find($order->id));
    }

    // ── rgpd:purge : sociétés, contacts, adresses ────────────────────────

    public function test_a_company_contact_and_address_cited_by_an_order_are_kept(): void
    {
        $company = Companies::factory()->create();
        $contact = CompaniesContacts::factory()->create(['companies_id' => $company->id, 'name' => 'Durand']);
        $address = CompaniesAddresses::factory()->create(['companies_id' => $company->id]);
        $order = $this->orderBy(User::factory()->create(), [
            'companies_id'           => $company->id,
            'companies_contacts_id'  => $contact->id,
            'companies_addresses_id' => $address->id,
        ]);

        $this->trashedLongAgo($contact);
        $this->trashedLongAgo($address);
        $this->trashedLongAgo($company);

        $this->artisan('rgpd:purge')->assertSuccessful();

        $this->assertNotNull(Companies::onlyTrashed()->find($company->id));
        $this->assertNotNull(CompaniesAddresses::onlyTrashed()->find($address->id));
        $keptContact = CompaniesContacts::onlyTrashed()->find($contact->id);
        $this->assertNotNull($keptContact);
        $this->assertSame('Anonymisé', $keptContact->name);
        $this->assertNotNull(Orders::find($order->id));
    }

    public function test_a_company_whose_contact_is_cited_is_kept_even_if_the_company_is_not(): void
    {
        $company = Companies::factory()->create();
        $contact = CompaniesContacts::factory()->create(['companies_id' => $company->id]);
        $order = $this->orderBy(User::factory()->create(), ['companies_contacts_id' => $contact->id]);
        $this->trashedLongAgo($company);

        $this->artisan('rgpd:purge')->assertSuccessful();

        $this->assertNotNull(Companies::onlyTrashed()->find($company->id));
        $this->assertNotNull(Orders::find($order->id));
    }

    public function test_an_unreferenced_company_and_contact_are_deleted_as_before(): void
    {
        $company = Companies::factory()->create();
        $contact = CompaniesContacts::factory()->create(['companies_id' => $company->id]);
        $this->trashedLongAgo($contact);
        $this->trashedLongAgo($company);

        $this->artisan('rgpd:purge')->assertSuccessful();

        $this->assertNull(CompaniesContacts::withTrashed()->find($contact->id));
        $this->assertNull(Companies::withTrashed()->find($company->id));
    }

    public function test_every_listed_column_exists(): void
    {
        $dropped = ['inspection_documents']; // supprimée par migration quand elle était vide
        $missing = [];

        foreach ([
            RecordRetention::USER_VISA_COLUMNS,
            RecordRetention::USER_RECORD_COLUMNS,
            RecordRetention::COMPANY_COLUMNS,
            RecordRetention::CONTACT_COLUMNS,
            RecordRetention::ADDRESS_COLUMNS,
        ] as $columns) {
            foreach ($columns as $table => $tableColumns) {
                if (in_array($table, $dropped, true)) {
                    continue;
                }
                foreach ($tableColumns as $column) {
                    if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                        $missing[] = "{$table}.{$column}";
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)));
    }

    // ── wem:activitylog:clean ────────────────────────────────────────────

    private function entry(?string $logName, int $daysAgo): int
    {
        $activity = activity($logName)->log('entrée');
        DB::table('activity_log')->where('id', $activity->id)->update(['created_at' => now()->subDays($daysAgo)]);

        return $activity->id;
    }

    public function test_access_entries_are_purged_after_their_retention_and_visas_are_kept(): void
    {
        config([
            'activitylog.retention.default'          => null,
            'activitylog.retention.log_names.access' => 365,
        ]);
        $oldAccess = $this->entry('access', 400);
        $recentAccess = $this->entry('access', 30);
        $oldVisa = $this->entry('default', 3000);
        $unnamed = $this->entry(null, 3000);

        $this->artisan('wem:activitylog:clean')->assertSuccessful();

        $this->assertNull(Activity::find($oldAccess));
        $this->assertNotNull(Activity::find($recentAccess));
        $this->assertNotNull(Activity::find($oldVisa));
        $this->assertNotNull(Activity::find($unnamed));
    }

    public function test_the_default_retention_applies_to_every_other_log(): void
    {
        config([
            'activitylog.retention.default'          => '1000',
            'activitylog.retention.log_names.access' => null,
        ]);
        $oldAccess = $this->entry('access', 3000);
        $old = $this->entry('default', 1500);
        $recent = $this->entry('default', 500);

        $this->artisan('wem:activitylog:clean')->assertSuccessful();

        $this->assertNotNull(Activity::find($oldAccess), 'access sans durée : jamais purgé.');
        $this->assertNull(Activity::find($old));
        $this->assertNotNull(Activity::find($recent));
    }

    public function test_dry_run_deletes_nothing(): void
    {
        config(['activitylog.retention.log_names.access' => 365]);
        $oldAccess = $this->entry('access', 400);

        $this->artisan('wem:activitylog:clean', ['--dry-run' => true])->assertSuccessful();

        $this->assertNotNull(Activity::find($oldAccess));
    }

    public function test_the_scheduler_no_longer_runs_the_package_clean_command(): void
    {
        $commands = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->map(fn ($event) => (string) $event->command);

        $this->assertTrue($commands->contains(fn ($c) => str_contains($c, 'wem:activitylog:clean')));
        $this->assertFalse($commands->contains(fn ($c) => preg_match('/\sactivitylog:clean/', $c) === 1));
    }
}
