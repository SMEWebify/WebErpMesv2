<?php

namespace App\Providers;

use App\Models\Admin\Factory;
use Illuminate\Support\Facades\Schema;
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
        // Charger Factory UNE SEULE FOIS avec un singleton
        app()->singleton('Factory', function () {
            if (!Schema::hasTable('factory')) {
                return new Factory();
            }

            return Factory::first() ?? new Factory(); // Retourne un objet vide si null
        });

        // Partager Factory avec toutes les vues (comme avant)
        View::composer('*', function ($view) {
            $view->with('Factory', app('Factory'));
        });
    }
}
