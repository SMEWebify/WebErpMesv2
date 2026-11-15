<?php

/*
 * Recopie de la GED vers SharePoint (Intégrations → SharePoint).
 *
 * L'arborescence est {dossier racine}/{dossier du type}/{code} - {libellé}/fichier.
 * Un dossier d'entité n'est nommé qu'une fois : son identifiant SharePoint est gardé,
 * donc renommer l'entité (ou le dossier dans SharePoint) ne crée pas de doublon.
 */
return [
    // Dossier du type, sous le dossier racine. « / » = sous-dossier.
    // Un alias absent de la liste n'est jamais recopié (dossier salarié, notes de frais).
    'folders' => [
        'company'            => 'Sociétés',
        'opportunity'        => 'Opportunités',
        'quote'              => 'Devis',
        'order'              => 'Commandes',
        'delivery'           => 'Bons de livraison',
        'invoice'            => 'Factures',
        'credit-note'        => 'Avoirs',
        'product'            => 'Articles',
        'purchase'           => 'Achats/Commandes',
        'purchase-quotation' => 'Achats/Demandes de prix',
        'purchase-receipt'   => 'Achats/Réceptions',
        'stock-move'         => 'Stock/Mouvements',
        'batch'              => 'Stock/Lots',
        'inventory'          => 'Stock/Inventaires',
        'non-conformity'     => 'Qualité/Non-conformités',
        'derogation'         => 'Qualité/Dérogations',
    ],

    // Lignes rangées dans un sous-dossier de leur document.
    'line_parents' => [
        'quote-line' => 'quote',
        'order-line' => 'order',
    ],

    // Au-delà, envoi par session en morceaux (limite de l'envoi simple de Graph : 4 Mo).
    'simple_upload_max_bytes' => 4 * 1024 * 1024,
    // Multiple de 320 Kio exigé par Graph.
    'chunk_bytes' => 10 * 320 * 1024,

    'timeout' => (int) env('SHAREPOINT_TIMEOUT', 120),
];
