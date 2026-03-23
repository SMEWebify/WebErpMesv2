<?php

namespace App\Providers;

use App\Models\Admin\Factory;
use App\Models\User;
use App\Models\Workflow\Orders;
use App\Observers\OrdersObserver;
use App\Services\SelectDataService;
use Illuminate\Console\Command;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SelectDataService::class, function () {
            return new SelectDataService();
        });

        $this->app->resolving(Command::class, function (Command $command, $app) {
            $command->setLaravel($app);
        });
    }

    public function boot(): void
    {
        Paginator::useBootstrap();

        Orders::observe(OrdersObserver::class);

        if (config('branding.commercial')) {
            $this->overrideCommercialLogo();
        }

        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole('Admin');
        });
    }

    private function overrideCommercialLogo(): void
    {
        $picture = Cache::rememberForever('branding_factory_logo', function () {
            return Factory::value('picture');
        });

        if ($picture) {
            Config::set('adminlte.logo_img', 'images/factory/' . $picture);
            Config::set('adminlte.logo_img_alt', config('branding.app_name'));
        }
    }
}
