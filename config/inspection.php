<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Format du rapport premier article
    |--------------------------------------------------------------------------
    |
    | neutral : rapport premier article en clair, sans référence à une norme.
    | as9102  : mêmes données, intitulés et numéros de cases du formulaire
    |           SAE AS9102 (clients aéronautiques qui l'exigent).
    |
    */

    'report_format' => env('INSPECTION_REPORT_FORMAT', 'neutral'),

];
