<?php

return [
    'saved'    => 'Note du fournisseur enregistrée.',
    'no_lines' => 'Réception sans ligne : aucune commande d\'achat à laquelle rattacher la note.',

    // Fenêtre de notation rapide (fiche réception)
    'window' => [
        'card_title'      => 'Note du fournisseur',
        'title'           => 'Noter le fournisseur',
        'intro'           => 'Facultatif : quelques secondes pour suivre la qualité de vos fournisseurs.',
        'conformity'      => 'Conformité',
        'conformity_help' => 'Pièces conformes à la commande',
        'delay'           => 'Délai',
        'delay_help'      => 'Respect de la date promise',
        'documents'       => 'Documents',
        'documents_help'  => 'BL, certificats, étiquetage',
        'comment'         => 'Commentaire',
        'later'           => 'Plus tard',
        'save'            => 'Enregistrer',
        'saving'          => 'Enregistrement…',
        'rate'            => 'Noter',
        'edit'            => 'Modifier',
        'not_rated'       => 'Réception non notée.',
        'no_lines'        => 'Ajoutez une ligne pour pouvoir noter le fournisseur.',
        'days_late'       => ':days j de retard sur la date promise',
        'on_time'         => 'Livré à la date promise',
        'no_promised'     => 'Aucune date promise sur les lignes d\'achat',
        'suggested'       => 'proposé',
        'has_nc'          => 'Non-conformité sur cette réception : la note qualité compte pour :score/5.',
        'rolling'         => 'Note glissante :months mois',
        'rolling_detail'  => ':count évaluation(s), :nc réception(s) non conforme(s)',
        'no_rolling'      => 'Aucune évaluation sur :months mois',
        'error'           => 'Erreur lors de l\'enregistrement',
    ],

    // Fiche fournisseur
    'company' => [
        'rolling'        => 'Note glissante (:months mois)',
        'since'          => 'depuis le :date',
        'quality'        => 'Qualité',
        'logistics'      => 'Délai',
        'service'        => 'Documents / service',
        'count'          => ':count évaluation(s)',
        'nc_receipts'    => ':count réception(s) avec NC',
        'none'           => 'Aucune évaluation ni réception non conforme sur la période.',
        'receipt'        => 'Réception',
    ],
];
