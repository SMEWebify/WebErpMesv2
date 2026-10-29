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

];
