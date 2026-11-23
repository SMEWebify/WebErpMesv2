<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RgpdPolicyPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_privacy_page_renders(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('rgpd.policy'))
            ->assertOk()
            ->assertSee('Durées de conservation')
            ->assertSee(route('me.rgpd.index'), false);
    }
}
