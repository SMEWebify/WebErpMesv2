<?php

return [
    'enable' => 'Activer prix calculé',
    'disable' => 'Désactiver prix calculé',
    'disable_title' => 'Le prix calculé du moment sera enregistré comme prix de vente de la ligne.',
    'reason_title' => 'Commande validée : le prix doit rester figé. Motif de la réactivation du prix calculé ?',
    'reason_placeholder' => 'Motif (obligatoire)',
    'confirm' => 'Activer',
    'cancel' => 'Annuler',

    'errors' => [
        'not_article' => 'Une ligne de présentation n\'a pas de prix calculé.',
        'shipped' => 'La ligne est déjà livrée ou facturée, même en partie : son prix ne peut plus être recalculé.',
        'order_locked' => 'La commande n\'est plus en préparation (statut au-delà d\'« Ouvert » ou revue de contrat validée) : le prix est figé.',
        'reason_required' => 'Un motif est obligatoire pour réactiver le prix calculé sur une commande validée.',
    ],
];
