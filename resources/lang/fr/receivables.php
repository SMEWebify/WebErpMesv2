<?php

return [

    'credit' => [
        // Contrôle à la création d'une commande ou d'un BL
        'warning'                  => 'Encours client au-delà du plafond : :projected pour un plafond de :limit.',
        'blocked_title'            => 'Plafond d\'encours dépassé',
        'blocked'                  => 'Ce client est bloqué : son encours atteindrait :projected pour un plafond de :limit (encours actuel :outstanding).',
        'no_permission'            => 'Seul un utilisateur autorisé peut passer outre le plafond d\'encours.',
        'override'                 => 'Forcer pour ce document',
        'override_reason'          => 'Motif du dépassement',
        'override_reason_help'     => 'Obligatoire. Conservé avec le document, son auteur et la date.',
        'this_document'            => 'Ce document',
        'projected'                => 'Encours après ce document',
        'override_reason_required' => 'Le motif du dépassement est obligatoire.',

        // Fiche société
        'card_title'        => 'Encours client',
        'limit'             => 'Plafond d\'encours (TTC)',
        'limit_help'        => 'Vide = pas de plafond.',
        'block_level'       => 'Au-delà du plafond',
        'level_none'        => 'Aucun contrôle',
        'level_warn'        => 'Alerte, sans bloquer',
        'level_block'       => 'Blocage des commandes et BL',
        'read_only'         => 'Modifiable par les utilisateurs autorisés à passer outre le plafond.',
        'outstanding'       => 'Encours',
        'invoices_due'      => 'Factures non soldées',
        'open_orders'       => 'Commandes non facturées',
        'no_limit'          => 'Pas de plafond',
        'of_limit'          => 'du plafond',
        'overrides_title'   => 'Derniers passe-droits',
        'override_by'       => 'par',
    ],

    'reminders' => [
        'title'              => 'Factures à relancer',
        'subtitle'           => 'Factures échues non soldées. Aucun envoi automatique : chaque relance part de votre validation.',
        'to_remind_only'     => 'À relancer seulement',
        'all_overdue'        => 'Toutes les factures échues',
        'search'             => 'Rechercher un client ou une facture',
        'company'            => 'Client',
        'invoice'            => 'Facture',
        'due_date'           => 'Échéance',
        'days_late'          => 'Retard (j)',
        'amount_ttc'         => 'Montant TTC',
        'balance'            => 'Reste dû',
        'current_level'      => 'Dernière relance',
        'proposed_level'     => 'Niveau proposé',
        'up_to_date'         => 'Déjà relancée',
        'not_yet'            => 'Pas encore',
        'selected'           => 'sélectionnée(s)',
        'remind_by_email'    => 'Relancer par e-mail',
        'log_manual'         => 'Tracer un appel / courrier',
        'no_permission'      => 'L\'envoi des relances est réservé aux utilisateurs autorisés.',
        'empty'              => 'Aucune facture échue non soldée.',
        'no_levels'          => 'Aucun niveau de relance n\'est paramétré.',
        'levels_link'        => 'Niveaux de relance',
        'total_due'          => 'Total dû',
        'invoices_count'     => 'facture(s)',

        // Fenêtre d'envoi
        'send_title'         => 'Relance de :client',
        'to'                 => 'Destinataire',
        'subject'            => 'Objet',
        'message'            => 'Message',
        'edit_html'          => 'Modifier le texte (HTML)',
        'attachments'        => 'Pièces jointes',
        'send'               => 'Envoyer la relance',
        'sending'            => 'Envoi…',
        'cancel'             => 'Annuler',
        'channel'            => 'Canal',
        'note'               => 'Note',
        'note_placeholder'   => 'Ce qui a été dit ou envoyé, promesse de paiement…',
        'save_log'           => 'Enregistrer la relance',

        'sent_ok'            => 'Relance envoyée à :to.',
        'send_failed'        => 'L\'e-mail n\'est pas parti : :error',
        'logged_ok'          => 'Relance enregistrée sur :count facture(s).',
        'pdf_failed'         => 'Le PDF de la facture :code n\'a pas pu être généré : :error',
        'not_overdue'        => 'La sélection contient une facture qui n\'est plus échue et due (réglée entre-temps ?). Rechargez la liste.',

        'default_subject'    => 'Relance : facture(s) impayée(s)',
        'default_content'    => '<p>Bonjour,</p><p>Sauf erreur de notre part, les factures suivantes restent impayées :</p><div>{factures}</div><p>Montant restant dû : <strong>{total_du}</strong>.</p><p>Cordialement,<br>{entreprise}</p>',

        'channels' => [
            'email'  => 'E-mail',
            'phone'  => 'Téléphone',
            'letter' => 'Courrier',
        ],

        'table' => [
            'invoice'   => 'Facture',
            'date'      => 'Date',
            'due_date'  => 'Échéance',
            'days_late' => 'Jours de retard',
            'balance'   => 'Reste dû',
        ],

        // Historique sur la fiche facture
        'history_title'      => 'Relances',
        'history_empty'      => 'Aucune relance.',
        'email_failed'       => 'échec de l\'envoi',

        // Paramétrage des niveaux
        'levels_title'       => 'Niveaux de relance',
        'levels_help'        => 'Un niveau est proposé dès que le retard atteint son délai. Les modèles d\'e-mail se modifient dans Administration → Modèles d\'e-mail (type « Relance de facture »). Variables : {client}, {factures}, {total_du}, {retard}, {entreprise}.',
        'level'              => 'Niveau',
        'label'              => 'Libellé',
        'delay_days'         => 'Délai après échéance (jours)',
        'email_template'     => 'Modèle d\'e-mail',
        'color'              => 'Couleur',
        'no_template'        => '— Texte par défaut —',
        'add_level'          => 'Ajouter un niveau',
        'save'               => 'Enregistrer',
        'delete'             => 'Supprimer',
        'level_saved'        => 'Niveau enregistré.',
        'level_deleted'      => 'Niveau supprimé.',
        'template_type'      => 'Relance de facture',
    ],

];
