<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Revue de contrat — points de la check-list
    |--------------------------------------------------------------------------
    |
    | Points revus avant le visa de la revue de contrat d'une commande client
    | (ISO 19443 / ISO 9001 § 8.2.3). Chaque clé est cochée ou non, et rangée
    | dans orders.review_checklist. Libellé : contract_review.checklist.{clé}
    | (fr / en) ; une clé sans traduction s'affiche telle quelle.
    |
    | Retirer une clé ne supprime rien : une revue déjà visée garde sa
    | check-list. Une revue validée alors qu'un point n'est pas coché exige
    | que les écarts soient décrits.
    |
    */

    'review_checklist' => [
        'requirements_understood',
        'lead_time_achievable',
        'contract_documents_received',
        'quality_requirements_identified',
    ],

];
