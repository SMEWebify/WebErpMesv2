<?php

return [
    'fields' => [
        'delivery_date' => 'Date de livraison',
        'internal_delay' => 'Date interne',
    ],
    'origins' => [
        'manual' => 'Saisie manuelle',
        'order' => 'Date de la commande',
        'import' => 'Import',
        'api' => 'API',
        'pre_order' => 'Pré-commande',
        'system' => 'Système',
    ],

    'reason_label' => 'Motif du report',
    'reason_placeholder' => 'Facultatif : retard fournisseur, demande client…',
    'promised_label' => 'Promise le',
    'postponed' => 'Reportée',
    'history_title' => 'Historique des reports',
    'no_history' => 'Aucun report.',
    'loading' => 'Chargement…',
    'load_error' => 'Impossible de charger l\'historique.',
    'by' => 'par',

    // Taux de service (widget OTD du tableau de bord)
    'otd_current' => 'Date actuelle',
    'otd_promised' => 'Date promise',
    'otd_promised_help' => 'Livraisons à l\'heure par rapport à la date promise au client, reports compris.',
    'otd_fallback' => ':count ligne(s) sans date promise : date actuelle utilisée',
];
