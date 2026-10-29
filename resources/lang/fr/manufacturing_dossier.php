<?php

return [
    'label' => 'Dossier de fin de fabrication',
    'short' => 'Dossier',
    'validated' => 'Dossier validé',
    'not_validated' => 'Dossier à valider',
    'pending_title' => 'Dossier de fin de fabrication exigé et non validé : BL et facture seront refusés.',
    'validated_on' => 'Dossier validé le :date par :user',
    'source_siteflow' => 'Siteflow',

    // Refus à la création d'un BL ou d'une facture
    'blocked' => '{1} Dossier de fin de fabrication non validé pour la ligne : :lines. Ni BL ni facture tant qu\'il n\'est pas validé.|[2,*] Dossier de fin de fabrication non validé pour les lignes : :lines. Ni BL ni facture tant qu\'il n\'est pas validé.',

    // Refus sur la ligne
    'not_article' => 'Une ligne de présentation n\'a pas de dossier de fin de fabrication.',
    'already_validated' => 'Le dossier de cette ligne est déjà validé.',
    'not_validated_yet' => 'Le dossier de cette ligne n\'est pas validé.',
    'already_shipped' => 'La ligne est déjà livrée ou facturée, même en partie : la validation du dossier ne s\'annule plus.',

    // Actions groupées de la fiche commande
    'actions' => 'Dossier',
    'action_validate' => 'Valider le dossier',
    'action_revoke' => 'Annuler la validation',
    'action_require' => 'Exiger le dossier',
    'action_waive' => 'Ne plus exiger le dossier',
    'revoke_confirm' => 'Annuler la validation du dossier des lignes sélectionnées ?',
    'waive_confirm' => 'Ne plus exiger de dossier de fin de fabrication sur les lignes sélectionnées ? Elles pourront être livrées et facturées sans lui.',
    'bulk_none' => 'Aucune ligne sélectionnée.',
    'bulk_refused' => 'Non traitée(s) :',
    'bulk_result' => [
        'validate' => ':count ligne(s) au dossier validé.',
        'revoke' => ':count validation(s) annulée(s).',
        'require' => ':count ligne(s) exigent désormais le dossier.',
        'waive' => ':count ligne(s) n\'exigent plus le dossier.',
    ],
];
