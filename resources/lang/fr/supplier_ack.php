<?php

return [
    // Liste « sans AR »
    'menu'                => 'Commandes sans AR',
    'missing_title'       => 'Commandes d\'achat sans accusé de réception',
    'missing_info'        => 'Commandes passées depuis au moins :days jour(s) dont une ligne n\'a pas encore d\'accusé de réception du fournisseur. Saisissez l\'AR depuis la fiche commande (onglet des lignes, bouton « AR reçu »).',
    'missing_none'        => 'Aucune commande en attente d\'accusé de réception.',
    'ordered_at'          => 'Commandée le',
    'waiting_days'        => 'Attente',
    'days'                => '{0} aujourd\'hui|{1} :count jour|[2,*] :count jours',
    'lines_without_ack'   => 'Lignes sans AR',
    'next_requested_date' => 'Première date demandée',

    'confirmed_date_hint' => 'Date confirmée par le fournisseur (AR)',

    // Fiche commande d'achat (PurchaseLinesPage, SupplierAckModal)
    'screen' => [
        'action'          => 'AR reçu',
        'title'           => 'Accusé de réception du fournisseur',
        'ack_at'          => 'Date de l\'AR',
        'reference'       => 'Référence de l\'AR',
        'file'            => 'Document (facultatif)',
        'hint'            => 'Cochez les lignes confirmées et saisissez la date de livraison annoncée par le fournisseur (par défaut la date demandée). Elle devient la date de référence des retards. Le document est rangé dans l\'onglet Documents, rôle « AR fournisseur ».',
        'all_lines'       => 'Toutes les lignes',
        'line'            => 'Ligne',
        'requested'       => 'Date demandée',
        'confirmed'       => 'Date confirmée',
        'gap'             => 'Écart',
        'already'         => 'AR déjà reçu',
        'day_unit'        => 'j',
        'save'            => 'Enregistrer l\'AR',
        'saving'          => 'Enregistrement…',
        'cancel'          => 'Annuler',
        'saved'           => 'Accusé de réception enregistré',
        'error'           => 'Erreur lors de l\'enregistrement de l\'AR',
        'file_error'      => 'AR enregistré, mais le document n\'a pas pu être déposé :',
        'no_line'         => 'Cochez au moins une ligne.',
        'missing'         => 'Sans AR',
        'ack_short'       => 'AR du',
        'requested_short' => 'demandée',
        'confirmed_title' => 'Date confirmée par le fournisseur (date de référence)',
        'requested_title' => 'Date demandée (pas encore confirmée)',
    ],

    // Saisie
    'not_ordered'         => 'L\'accusé de réception se saisit sur une commande passée (ni en cours, ni annulée).',
    'foreign_line'        => 'Une des lignes n\'appartient pas à cette commande.',
];
