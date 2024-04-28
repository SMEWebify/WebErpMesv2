<?php

namespace App\Providers;

use App\Models\Admin\Factory;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class FactoryServiceProvider extends ServiceProvider
{
    /**
     * The first factory
     *
     * @var static|null
     */
    private static ?Factory $firstFactory = null;

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        //view()->share('Factory', self::$firstFactory);
        View::composer('*', function ($view) {
            self::$firstFactory ??= Factory::first();
            $view->with('Factory', self::$firstFactory);
        });
    }
}