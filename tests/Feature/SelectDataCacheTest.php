<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Services\SelectDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * The select lists are cached forever: a row created, renamed or deleted must show
 * up in them at once, without a cache:clear.
 */
class SelectDataCacheTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_new_family_appears_in_the_cached_select()
    {
        $service = new SelectDataService();
        $this->assertCount(0, $service->getFamilies());

        $family = MethodsFamilies::factory()->create();

        $this->assertTrue($service->getFamilies()->contains('id', $family->id));
    }

    /** @test */
    public function renaming_or_deleting_a_service_refreshes_every_service_list()
    {
        $service = new SelectDataService();
        $row = MethodsServices::factory()->create(['type' => 1, 'label' => 'Laser']);

        $this->assertSame('Laser', $service->getServices()->firstWhere('id', $row->id)->label);
        $this->assertTrue($service->getTechServices()->contains('id', $row->id));

        $row->update(['label' => 'Découpe']);
        $this->assertSame('Découpe', $service->getServices()->firstWhere('id', $row->id)->label);

        $row->delete();
        $this->assertFalse($service->getServices()->contains('id', $row->id));
        $this->assertFalse($service->getTechServices()->contains('id', $row->id));
    }
}
