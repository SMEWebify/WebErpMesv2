<?php

return [
    'status' => [
        'qualified'      => 'Qualifié',
        'unqualified'    => 'Non qualifié',
        'review_overdue' => 'Revue échue',
    ],

    'detail' => [
        'no_evaluation'  => 'aucune évaluation fournisseur.',
        'not_approved'   => 'dernière évaluation au statut « :status ».',
        'review_overdue' => 'revue de qualification échue depuis le :date.',
    ],

    'blocked_title'           => 'Fournisseur non qualifié',
    'blocked_create'          => 'Commande d\'achat refusée : fournisseur non qualifié.',
    'blocked_validate'        => 'Validation de la commande refusée : fournisseur non qualifié.',
    'blocked_supplier_change' => 'Changement de fournisseur refusé sur une commande validée : fournisseur non qualifié. Repassez la commande « en cours » pour changer de fournisseur.',
    'no_permission'           => 'Vous n\'avez pas le droit de commander à un fournisseur non qualifié. Une demande de prix reste possible.',
    'override_reason'         => 'Motif du passe-droit',
    'override_reason_help'    => 'Tracé sur la commande et visible sur la fiche du fournisseur.',
    'override_reason_required'=> 'Indiquez le motif du passe-droit.',
    'override'                => 'Commander quand même',

    // Fiche société et fiche commande
    'label'           => 'Qualification',
    'overrides_title' => 'Passe-droits « fournisseur non qualifié »',
    'override_on'     => 'Passe-droit sur :document',
    'context'         => [
        'create'          => 'création',
        'validate'        => 'validation',
        'supplier_change' => 'changement de fournisseur',
    ],

    // Export
    'export'          => 'Fournisseurs qualifiés (CSV)',
    'export_columns'  => [
        'code'              => 'Code',
        'label'             => 'Raison sociale',
        'qualification'     => 'Qualification',
        'evaluation_status' => 'Statut de la dernière évaluation',
        'evaluated_at'      => 'Évalué le',
        'approved_at'       => 'Approuvé le',
        'next_review_at'    => 'Prochaine revue',
    ],
];
