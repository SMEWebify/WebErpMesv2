<?php

namespace Tests\Unit;

use App\Models\Admin\Factory;
use Tests\TestCase;

class FactoryLogoTest extends TestCase
{
    /** Un logo référencé en base mais absent du disque ne doit rien faire planter
     *  (pages publiques, PDF, Factur-X) : la méthode rend null, sans logo. */
    public function test_missing_logo_file_returns_null_instead_of_failing(): void
    {
        $factory = new Factory(['picture' => 'logo_introuvable_'.uniqid().'.png']);

        $this->assertFalse($factory->hasPictureFile());
        $this->assertNull($factory->getImageFactoryPath());
    }

    public function test_no_logo_returns_null(): void
    {
        $factory = new Factory(['picture' => null]);

        $this->assertFalse($factory->hasPictureFile());
        $this->assertNull($factory->getImageFactoryPath());
    }

    public function test_existing_logo_is_encoded(): void
    {
        $name = 'logo_test_'.uniqid().'.png';
        $path = public_path('images/factory/'.$name);
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, 'png');

        try {
            $factory = new Factory(['picture' => $name]);

            $this->assertTrue($factory->hasPictureFile());
            $this->assertSame(base64_encode('png'), $factory->getImageFactoryPath());
        } finally {
            @unlink($path);
        }
    }
}
