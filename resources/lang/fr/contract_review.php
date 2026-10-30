<?php

return [
    'tab' => 'Revue de contrat',
    'quote_tab' => 'Revue',
    'title' => 'Revue de contrat',
    'intro' => 'Avant de lancer la commande, vérifier qu\'elle correspond à l\'offre et que l\'entreprise peut la tenir. Le visa fige la revue.',

    // Indicateur (index, onglet)
    'status' => [
        'done' => 'Revue faite',
        'rejected' => 'Revue rejetée',
        'todo' => 'Revue à faire',
    ],
    'column' => 'Revue',

    'checklist_title' => 'Points revus',
    'checklist' => [
        'requirements_understood' => 'Exigences du client comprises et conformes à l\'offre',
        'lead_time_achievable' => 'Délai demandé tenable',
        'contract_documents_received' => 'Documents contractuels reçus',
        'quality_requirements_identified' => 'Exigences qualité et codes applicables identifiés',
    ],
    'checklist_removed' => 'point retiré de la configuration',

    'gaps' => 'Écarts avec l\'offre',
    'gaps_help' => 'Différences entre la commande et l\'offre (prix, quantités, délai, exigences), et comment elles sont levées. Obligatoire pour rejeter, ou pour valider avec un point non coché.',
    'no_gaps' => 'Aucun écart déclaré.',

    'documents' => 'Documents contractuels',
    'documents_help' => 'Pièces de la GED de la commande au type « Document contractuel ».',
    'no_documents' => 'Aucun document contractuel dans la GED de la commande.',
    'open_documents' => 'Ouvrir les documents',

    'save' => 'Enregistrer la préparation',
    'saved' => 'Préparation enregistrée.',
    'approve' => 'Viser : revue validée',
    'reject' => 'Viser : revue rejetée',
    'approve_confirm' => 'Valider la revue de contrat ? Elle sera figée et l\'accusé de réception de commande sera préparé.',
    'reject_confirm' => 'Rejeter la revue de contrat ? Elle sera figée et la commande ne pourra plus recevoir de bon pour fabrication.',
    'visa_reserved' => 'Le visa est réservé aux personnes autorisées (permission « contract-review-approve »).',
    'visa' => 'Visée le :date par :user',
    'frozen' => 'Revue visée : elle ne peut plus être modifiée.',
    'unchecked_warning' => 'Point(s) non coché(s) : décrire les écarts avant de valider.',
    'saving' => 'Enregistrement…',
    'error' => 'L\'enregistrement a échoué.',

    // Avertissement de la fiche commande (jamais bloquant)
    'warning_todo' => 'La revue de contrat de cette commande n\'est pas visée.',
    'warning_rejected' => 'La revue de contrat de cette commande est rejetée.',
    'warning_link' => 'Ouvrir la revue',

    // Demande de changement
    'change' => [
        'title' => 'Demande de changement',
        'help' => 'Changement demandé après la revue (indice, quantité, délai…). Tant qu\'il n\'est pas approuvé, aucun bon pour fabrication n\'est donné.',
        'pending' => 'Changement en attente d\'approbation',
        'save' => 'Enregistrer le changement',
        'saved' => 'Demande de changement enregistrée.',
    ],

    // Avenants (remplacent la demande de changement)
    'amendment' => [
        'title' => 'Avenants',
        'help' => 'Un avenant (indice, quantité, délai, prix…) rouvre la revue de contrat : la commande repasse « revue à faire », et aucun bon pour fabrication n\'est donné, jusqu\'au visa de la revue. Le devis d\'origine n\'est pas modifié.',
        'number' => 'Avenant n° :number',
        'new' => 'Nouvel avenant',
        'reason' => 'Motif',
        'requested_by' => 'Demandeur',
        'date' => 'Date',
        'create' => 'Créer l\'avenant et rouvrir la revue',
        'create_confirm' => 'Créer l\'avenant ? La revue de contrat sera rouverte jusqu\'à son visa.',
        'created' => 'Avenant créé : la revue de contrat est rouverte.',
        'cancel' => 'Annuler l\'avenant',
        'cancel_confirm' => 'Annuler cet avenant ? La revue de contrat reviendra à son état d\'avant l\'avenant.',
        'cancelled_ok' => 'Avenant annulé : la revue précédente est rétablie.',
        'amount_before' => 'TTC avant',
        'amount_after' => 'TTC après',
        'amount_pending' => 'au visa',
        'decision' => 'Visa',
        'none' => 'Aucun avenant.',
        'pending_exists' => 'Un avenant est déjà ouvert : le viser (revue de contrat) ou l\'annuler avant d\'en créer un autre.',
        'reopened' => 'Revue rouverte par l\'avenant n° :number : la viser vise l\'avenant.',
        'warning' => 'L\'avenant n° :number attend le visa de la revue de contrat.',
        'status' => [
            'pending' => 'En attente de visa',
            'approved' => 'Validé',
            'rejected' => 'Rejeté',
            'cancelled' => 'Annulé',
        ],
        'legacy' => 'Ancienne demande de changement',
        'legacy_help' => 'Saisie avant les avenants, conservée pour l\'historique.',
        'legacy_approved_at' => 'Approuvée le',
        'errors' => [
            'not_applicable' => 'Une commande interne n\'a pas d\'avenant.',
            'already_pending' => 'Un avenant est déjà en attente de visa sur cette commande.',
            'closed' => 'Cet avenant est déjà clos.',
        ],
    ],

    'history' => 'Historique de la revue',

    // Refus
    'errors' => [
        'forbidden' => 'Vous n\'avez pas le droit de viser une revue de contrat.',
        'frozen' => 'La revue de contrat est visée : elle ne peut plus être modifiée.',
        'not_applicable' => 'Une commande interne n\'a pas de revue de contrat.',
        'invalid_decision' => 'Décision inconnue.',
        'gaps_required_reject' => 'Décrire les écarts avec l\'offre pour rejeter la revue.',
        'gaps_required_unchecked' => 'Points non cochés (:items) : décrire les écarts avec l\'offre pour valider la revue.',
    ],
];
