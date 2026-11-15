<?php

// Offres fournisseurs indicées des demandes de prix (App\Services\Purchases\PurchaseOfferRevisions)
return [
    'print_caption'  => 'Offre fournisseur',
    'print_revision' => 'indice :revision',
    'created'        => 'Nouvelle offre :code créée : la demande précédente est remplacée.',
    'refused' => [
        'superseded' => 'Cette offre a été remplacée : seul le dernier indice (:code) se modifie ou se transforme en commande.',
        'ordered'    => 'Une commande est déjà sortie de cette demande de prix : une nouvelle offre permettrait de commander une seconde fois.',
    ],
    'screen' => [
        'reference'         => 'Référence de l\'offre',
        'revision'          => 'Indice de l\'offre',
        'date'              => 'Date de l\'offre',
        'validity'          => 'Validité de l\'offre',
        'new_revision'      => 'Nouvelle offre (indice suivant)',
        'new_revision_help' => 'Recopie la demande et ses lignes. Celle-ci passe « remplacée » : elle ne se modifiera ni ne se commandera plus.',
        'revision_prompt'   => 'Indice donné par le fournisseur (laisser vide pour l\'indice suivant) :',
        'history'           => 'Historique des offres',
        'superseded'        => 'Remplacé',
        'current'           => 'Dernier indice',
        'superseded_banner' => 'Offre remplacée par :code : elle ne se modifie ni ne se commande plus.',
        'open_latest'       => 'Ouvrir le dernier indice',
        'no_revision'       => 'sans indice',
        'revision_short'    => 'ind.',
        'total'             => 'Total',
    ],
];
