<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Import CAO par glisser-déposer sur les lignes
    |--------------------------------------------------------------------------
    |
    | Active le dépôt de fichiers CAO (.sym, .geo, .dxf, .step, .svg) sur les
    | écrans de lignes de devis et de commande pour créer les lignes.
    |
    | La valeur est lue par config() et non par env() : avec php artisan
    | config:cache, Laravel n'ouvre plus le .env au runtime, donc un env() en
    | contrôleur ou en vue renverrait toujours la valeur par défaut et
    | désactiverait silencieusement la fonctionnalité en production.
    |
    | RADAN_SYM_IMPORT est conservé en repli pour les .env déjà déployés.
    |
    */

    'line_import' => (bool) env('CAD_LINE_IMPORT', env('RADAN_SYM_IMPORT', false)),

];
