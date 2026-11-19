<?php

return [
    'form' => [
        'title'            => 'Faire un avoir',
        'help'             => 'Cochez les lignes, puis ajustez la quantité ou le prix pour un avoir partiel (pièces rebutées, retour partiel, geste commercial).',
        'line'             => 'Ligne',
        'invoiced'         => 'Facturé',
        'creditable'       => 'Reste créditable',
        'qty'              => 'Qté à créditer',
        'unit_price'       => 'P.U HT crédité',
        'fully_credited'   => 'Déjà en avoir',
        'deduction'        => 'Déduction d\'acompte',
        'restore'          => 'Remettre ces quantités dans le reste à facturer (pièces retournées ou refaites, à refacturer)',
        'restore_help'     => 'Décochez pour un geste commercial ou un rebut qui ne sera pas refait : la commande reste facturée.',
        'submit'           => 'Créer l\'avoir',
    ],

    'line' => [
        'restored'     => 'Remis à facturer',
        'not_restored' => 'Non remis à facturer',
    ],

    'refused' => [
        'no_line'            => 'Veuillez sélectionner au moins une ligne de facture.',
        'several_invoices'   => 'Un avoir porte sur une seule facture.',
        'fully_credited'     => 'La ligne :line est déjà entièrement en avoir.',
        'qty'                => 'Ligne :line : la quantité doit être positive et ne pas dépasser :max.',
        'price'              => 'Ligne :line : le prix crédité ne peut pas dépasser le prix facturé (:max €).',
        'amount'             => 'Ligne :line : le montant crédité dépasse ce qui reste créditable (:max € HT).',
        'already_reinvoiced' => 'Les quantités de la ligne :line ont été refacturées depuis : l\'avoir ne peut plus être refusé.',
    ],
];
