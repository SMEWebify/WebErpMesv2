<?php

namespace App\Providers;

use App\Services\Mail\MailSettingsService;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Applique la config SMTP stockée en base au démarrage de l'app.
 *
 * Le service reste disponible même si le boot silence l'apply() (base
 * indisponible pendant `php artisan config:cache` par exemple) : dans ce
 * cas, on repart sur le .env sans planter.
 */
class MailSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MailSettingsService::class);
    }

    public function boot(): void
    {
        try {
            $this->app->make(MailSettingsService::class)->apply();
        } catch (Throwable) {
            // Boot avant migration ou DB indispo : on garde la config .env.
        }
    }
}
