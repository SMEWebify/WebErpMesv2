<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Assets\Asset;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateAsset()
    {
        $data = [
            'name' => 'Laptop',
            'category' => 'IT',
            'acquisition_value' => 1500,
            'acquisition_date' => '2024-01-01',
            'depreciation_duration' => 36,
        ];

        $asset = Asset::create($data);

        $this->assertDatabaseHas('assets', ['name' => 'Laptop']);
        $this->assertEquals('IT', $asset->category);
    }
}
