<?php

return [
    'title' => 'Marge (interne)',
    'internal' => 'Information interne : jamais imprimée sur les documents client.',
    'cost_total' => 'Coût de revient',
    'sale_total' => 'Prix de vente HT',
    'margin' => 'Marge',
    'margin_rate' => 'Marge / coût',
    'markup_rate' => 'Marge / prix de vente',
    'column' => 'Marge',
    'column_toggle' => 'Afficher la colonne marge',
    'unit_cost' => 'Coût unitaire',
    'no_cost' => 'Pas de coût de revient (aucune opération, nomenclature ni sous-ensemble chiffré)',
    'below_threshold' => 'Marge sous le seuil de :threshold %',
    'lines_without_cost' => '{1} :count ligne sans coût de revient : comptée au prix de vente, sans coût.|[2,*] :count lignes sans coût de revient : comptées au prix de vente, sans coût.',
    'cost_help' => 'Coût = opérations (sous-traitance comprise) + nomenclature + sous-ensembles. Lignes de section, sous-total et texte ignorées.',

    'apply_title' => 'Appliquer une marge globale',
    'apply_rate' => 'Taux de marge sur le coût (%)',
    'apply_button' => 'Recalculer les prix',
    'apply_help' => 'Prix de vente de chaque ligne = coût × (1 + taux), remise de la ligne compensée. Le prix calculé depuis la gamme est désactivé. Lignes sans coût inchangées.',
    'apply_confirm' => 'Recalculer le prix de vente de toutes les lignes à partir de leur coût ?',
    'applied' => '{0} Aucun prix recalculé.|{1} :count prix recalculé.|[2,*] :count prix recalculés.',
    'applied_skipped' => ':count ligne(s) sans coût laissée(s) à leur prix.',
    'refused_converted' => 'Ce devis est converti en commande : ses prix sont figés, la marge globale ne peut plus être appliquée.',
    'refused_status' => 'Seul un devis ouvert peut être recalculé.',
    'locked_converted' => 'Devis converti en commande : prix figés.',
];
