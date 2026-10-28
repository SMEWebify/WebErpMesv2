<?php

return [
    'label' => 'Motif de perte',
    'plural' => 'Motifs de perte',
    'none' => 'Non renseigné',
    'inactive' => 'inactif',
    'info' => 'Motifs proposés quand un devis passe au statut Perdu ou une opportunité en Perdue. Le motif est demandé dès qu\'au moins un motif est actif ; les documents perdus avant sa mise en place restent valides sans motif. Un motif déjà utilisé ne se supprime pas : désactivé, il n\'est plus proposé mais reste affiché sur les documents qui le portent.',
    'new' => 'Nouveau motif',
    'edit' => 'Modifier :label',
    'code' => 'Code',
    'order' => 'Ordre',
    'active' => 'Actif',
    'usage' => 'Utilisation',
    'usage_detail' => ':quotes devis, :opportunities opportunité(s)',
    'created' => 'Motif de perte créé.',
    'updated' => 'Motif de perte mis à jour.',
    'code_locked' => 'Le code ne se modifie pas.',

    'defaults' => [
        'PRICE' => 'Prix',
        'DELAY' => 'Délai',
        'TECHNICAL' => 'Technique',
        'COMPETITOR' => 'Concurrent',
        'NO_FOLLOW_UP' => 'Sans suite',
    ],

    'dialog' => [
        'title_quote' => 'Devis perdu : pourquoi ?',
        'title_opportunity' => 'Opportunité perdue : pourquoi ?',
        'reason' => 'Motif',
        'choose' => '— Choisir un motif —',
        'comment' => 'Commentaire',
        'comment_help' => 'Concurrent retenu, écart de prix, précision du client…',
        'cancel' => 'Annuler',
        'confirm' => 'Enregistrer',
        'edit' => 'Modifier',
        'fill' => 'Renseigner',
        'error' => 'Erreur lors de l\'enregistrement du motif.',
    ],

    'required' => 'Indiquez le motif de la perte.',
    'not_lost' => 'Le motif de perte ne se saisit que sur un document perdu.',

    'stats' => [
        'title' => 'Pertes commerciales',
        'by_reason' => 'Par motif',
        'by_category' => 'Par catégorie d\'affaire',
        'count' => 'Devis',
        'amount' => 'Montant HT',
        'share' => 'Part',
        'total' => 'Total',
        'no_category' => 'Sans catégorie',
        'empty' => 'Aucun devis perdu sur la période.',
        'period' => 'Période',
        'periods' => [
            '12m' => '12 derniers mois',
            'year' => 'Année en cours',
            'all' => 'Depuis le début',
        ],
        'period_help' => 'Devis perdus créés sur la période.',
    ],
];
