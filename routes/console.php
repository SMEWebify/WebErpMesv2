<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tâches planifiées RGPD
|--------------------------------------------------------------------------
*/

// Purge hebdomadaire : tokens expirés, email_logs > 1 an, force-delete > 90j
Schedule::command('rgpd:purge')->weekly();

// Nettoyage mensuel de l'activity_log (durée configurée dans config/activitylog.php)
Schedule::command('activitylog:clean')->monthly();
