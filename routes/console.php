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

/*
|--------------------------------------------------------------------------
| Sauvegardes automatiques
|--------------------------------------------------------------------------
*/

// Backup quotidien à 02h00 (DB + storage/app)
Schedule::command('backup:run')->dailyAt('02:00');

// Nettoyage des anciennes sauvegardes (rétention définie dans config/backup.php)
Schedule::command('backup:clean')->dailyAt('01:00');

// Vérification de santé des sauvegardes (alerte mail si backup > 2 jours)
Schedule::command('backup:monitor')->dailyAt('09:00');

/*
|--------------------------------------------------------------------------
| Facturation électronique (PDP)
|--------------------------------------------------------------------------
*/

// Les plateformes sans webhooks (SUPER PDP) se synchronisent par interrogation :
// cycle de vie des factures émises + réception des factures fournisseurs.
// withoutOverlapping : une synchronisation lente ne doit pas en déclencher une
// seconde qui relirait le même curseur en parallèle.
Schedule::command('wem:pdp:sync')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
