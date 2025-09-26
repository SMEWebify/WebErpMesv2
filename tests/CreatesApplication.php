<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        if (empty($_ENV['APP_KEY']) && empty($_SERVER['APP_KEY']) && getenv('APP_KEY') === false) {
            $key = 'base64:'.base64_encode(random_bytes(32));

            $_ENV['APP_KEY'] = $_SERVER['APP_KEY'] = $key;
            putenv('APP_KEY='.$key);
        }

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
