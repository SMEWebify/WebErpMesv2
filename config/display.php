<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Unité d'affichage des temps opératoires (réglage, unitaire, total)
    |--------------------------------------------------------------------------
    |
    | « hours » (défaut) ou « minutes ». Ne change que la saisie et l'affichage :
    | le stockage (tasks.seting_time, tasks.unit_time, methods_standard_tasks),
    | l'API, les exports, les capacités et les calculs restent en HEURES.
    | La valeur effective vient du réglage société « display.time_unit »
    | (Administration → Unité des temps) ; cette clé n'est que le repli.
    |
    */

    'time_unit' => env('DISPLAY_TIME_UNIT', 'hours'),

    /*
    |--------------------------------------------------------------------------
    | Arrondi à l'affichage en minutes
    |--------------------------------------------------------------------------
    |
    | Nombre de décimales conservées quand un temps stocké en heures est affiché
    | en minutes (zéros de fin retirés : 30 min, 1,5 min). Le stockage ayant 3
    | décimales d'heure (0,001 h = 3,6 s), 1 décimale de minute (6 s) est le
    | maximum qui ait un sens. Sert aussi à la saisie : le champ accepte plus,
    | c'est le stockage qui arrondit.
    |
    */

    'minutes_decimals' => (int) env('DISPLAY_MINUTES_DECIMALS', 1),

];
