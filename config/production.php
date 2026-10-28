<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bon pour fabrication (BPF) obligatoire
    |--------------------------------------------------------------------------
    |
    | Vrai : une tâche de production (service productif) d'une ligne de
    | commande ne démarre qu'après le BPF du bureau d'études. Les achats, la
    | sous-traitance et les besoins matière ne sont jamais bloqués : leur
    | approvisionnement peut être anticipé.
    |
    | Faux (défaut) : comportement historique, aucun blocage ni affichage BPF.
    |
    */

    'require_release' => (bool) env('PRODUCTION_REQUIRE_RELEASE', false),

];
