<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fournisseur qualifié obligatoire
    |--------------------------------------------------------------------------
    |
    | Vrai : une commande d'achat n'est créée ni validée (passage à
    | « commandée ») que pour un fournisseur qualifié, c'est-à-dire dont la
    | dernière évaluation (hors notes de réception) est approuvée et dont la
    | date de revue n'est pas dépassée. Passe-droit motivé pour les porteurs de
    | `unqualified-supplier-override`. Les demandes de prix restent libres.
    |
    | Faux (défaut) : comportement historique, aucun contrôle ni badge.
    | (App\Services\Purchases\SupplierQualification)
    |
    */

    'require_qualified_supplier' => (bool) env('PURCHASES_REQUIRE_QUALIFIED_SUPPLIER', false),

    /*
    |--------------------------------------------------------------------------
    | Livraison à l'heure des fournisseurs : tolérance
    |--------------------------------------------------------------------------
    |
    | Une ligne d'achat est livrée à l'heure si sa dernière réception a lieu au
    | plus tard N jours après la date confirmée par le fournisseur (à défaut,
    | la date demandée). 0 = le jour même au plus tard.
    | (App\Services\Purchases\SupplierOnTimeDelivery)
    |
    */

    'on_time_tolerance_days' => (int) env('PURCHASES_ON_TIME_TOLERANCE_DAYS', 0),

    /*
    |--------------------------------------------------------------------------
    | Accusé de réception fournisseur : délai d'alerte
    |--------------------------------------------------------------------------
    |
    | Une commande d'achat passée (commandée ou partiellement reçue) dont une
    | ligne n'a pas d'AR N jours après la commande apparaît dans la liste
    | « Commandes sans AR » du menu Achats. Rien n'est bloqué.
    | (App\Services\Purchases\SupplierAcknowledgement)
    |
    */

    'ack_overdue_days' => (int) env('PURCHASES_ACK_OVERDUE_DAYS', 3),

];
