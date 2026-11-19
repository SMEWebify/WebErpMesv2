<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configurateurs externes
    |--------------------------------------------------------------------------
    |
    | Un configurateur tiers ouvre une session depuis une ligne de devis ou de
    | commande et renvoie des lignes, détails, options et fichiers (contrat :
    | docs/conception/configurateurs-externes.md). Les configurateurs se règlent
    | dans Intégrations → Configurateurs ; cet interrupteur coupe tout.
    |
    */
    'enabled' => (bool) env('CONFIGURATORS_ENABLED', true),

    // Durée de vie d'une session ouverte (minutes) ; au-delà, son résultat est refusé.
    'session_minutes' => 120,

    // Bornes d'un résultat.
    'max_lines'        => 50,
    'max_file_bytes'   => 50 * 1024 * 1024,
    'max_result_bytes' => 200 * 1024 * 1024,

    // Délais d'attente des appels au configurateur (secondes).
    'timeout'      => 10,
    'file_timeout' => 30,
];
