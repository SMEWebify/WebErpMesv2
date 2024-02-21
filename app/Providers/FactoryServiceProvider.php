<?php

namespace App\Providers;

use App\Models\Admin\Factory;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class FactoryServiceProvider extends ServiceProvider
{
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
        //view()->share('Factory', Factory::first());
        View::composer('*', function ($view) {
            $Factory = Factory::first();
            $view->with('Factory', $Factory);
        });
    }
}
