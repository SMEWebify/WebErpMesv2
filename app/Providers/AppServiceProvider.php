<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Workflow\Orders;
use App\Observers\OrdersObserver;
use App\Services\SelectDataService;
use Illuminate\Console\Command;
use Illuminate\Pagination\Paginator;
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

        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole('Admin');
        });
    }
}
