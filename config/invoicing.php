<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Facturation électronique — contenu normatif du document
    |--------------------------------------------------------------------------
    |
    | Ces valeurs alimentent le Factur-X produit par App\Services\Invoicing\
    | FacturXBuilder. Elles relèvent du droit commercial et des conditions
    | générales de vente de la société émettrice, pas de la plateforme : elles
    | restent donc identiques quelle que soit la PDP utilisée.
    |
    */

    /*
     * Mode de facturation (BT-23, règle française BR-FR-08).
     *
     * Valeurs autorisées : B1, S1, M1, B2, S2, M2, S3, B4, S4, M4, S5, S6,
     * B7, S7, B8, S8, M8, B9, S9, M9.
     *
     * M1 = facture unitaire sans commande ni décompte, le cas courant en
     * facturation commerciale.
     */
    'business_process' => env('INVOICING_BUSINESS_PROCESS', 'M1'),

    /*
     * Mentions légales obligatoires entre professionnels (BT-22, BR-FR-05),
     * indexées par code sujet UNTDID 4451 :
     *   PMD — pénalités de retard
     *   PMT — indemnité forfaitaire pour frais de recouvrement
     *   AAB — escompte pour paiement anticipé, ou son absence
     *
     * Les textes ci-dessous sont le régime légal supplétif (art. L441-9 et
     * L441-10 du Code de commerce). Si les CGV de la société prévoient un taux
     * de pénalité ou un escompte différent, remplacer les textes ici : une
     * facture qui contredit les CGV n'est pas opposable.
     *
     * Mettre une valeur vide retire la mention du document.
     */
    'legal_notices' => [
        'PMD' => "En cas de retard de paiement, des pénalités sont exigibles au taux de trois fois le taux d'intérêt légal, sans qu'un rappel soit nécessaire.",
        'PMT' => 'Tout retard de paiement donne lieu à une indemnité forfaitaire pour frais de recouvrement de 40 euros (art. L441-10 du Code de commerce).',
        'AAB' => "Aucun escompte n'est accordé pour paiement anticipé.",
    ],

    /*
     * Factures d'acompte (invoice_type 4) et leur déduction sur les factures
     * suivantes (App\Services\Invoicing\DownPayments).
     *
     * account : compte crédité par l'acompte (HT) et débité par sa déduction,
     * à la place du compte de produit. 4191 = « Clients – avances et acomptes
     * reçus sur commandes » du PCG. La TVA suit le compte de TVA habituel.
     * À valider avec l'expert-comptable de chaque société déployée.
     *
     * Seule la part payée d'un acompte est déduite : un acompte impayé reste dû
     * sur sa propre facture, il n'est pas reporté sur la facture suivante.
     */
    'down_payment' => [
        'account'       => env('INVOICING_DOWN_PAYMENT_ACCOUNT', '4191'),
        'account_label' => 'Clients - avances et acomptes reçus',
    ],

];
