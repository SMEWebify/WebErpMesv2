<?php

// Textes des briques React partagées (DataTable, Pagination, dépôt CAO), posés par le gabarit dans <meta name="ui-common"> (lib/i18n.js, commonText).
return [
    'show_column' => 'Réafficher la colonne',
    'hide_column' => 'Masquer la colonne',
    'date_from' => 'Du',
    'date_to' => 'Au',
    'results' => 'résultats',
    'result' => 'résultat',
    'cad_no_file' => 'Aucun fichier reconnu (:formats).',
    'cad_import_error' => 'Erreur lors de l\'import.',
    'cad_network_error' => 'Erreur réseau lors de l\'envoi.',
    'cad_importing' => 'Import en cours…',
    'cad_drop' => 'Déposer des fichiers',
    'cad_help' => 'Le plan déposé est rattaché à la ligne dans la GED (hors .sym, .drg et .bvc, entièrement lus à l\'import). Un .geo produit en plus un SVG exploitable par l\'imbrication. Une imbrication RADAN (.drg) crée une ligne par pièce, quantité = pièces par tôle × nombre de tôles. Un job BySoft (.bvc) crée une ligne par pièce avec son contour en SVG, quantité = quantité du job. Un plan .pdf est lu par l\'IA (Intégrations → IA) : cartouche à vérifier sur la ligne créée.',
    'cad_drop_help' => 'ici pour créer les lignes automatiquement  — ou cliquer pour sélectionner',
];
