<?php

namespace Tests;

use App\Models\Admin\Factory as FactoryModel;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (!FactoryModel::query()->exists()) {
            FactoryModel::create([
                'name' => 'Test Factory',
            ]);
        }
    }

    protected function authenticateApiUser(): User
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        return $user;
    }
}
