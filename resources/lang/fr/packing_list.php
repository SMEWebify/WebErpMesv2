<?php

return [
    // Modèle d'impression du BL (print/layouts/packing-list)
    'layout'           => 'Liste de colisage',
    'title'            => 'Liste de colisage',
    'delivery_note'    => 'Bon de livraison',
    'tracking_number'  => 'N° de suivi',
    'package'          => 'Colis',
    'package_of'       => 'Colis :number / :count',
    'packages_count'   => 'Nombre de colis',
    'dimensions'       => 'Dimensions (L × l × H)',
    'net_weight'       => 'Poids net',
    'gross_weight'     => 'Poids brut',
    'total_net_weight' => 'Poids net total',
    'total_gross_weight' => 'Poids brut total',
    'weight_unit'      => 'kg',
    'content'          => 'Contenu',
    'empty_package'    => 'Aucune ligne rangée dans ce colis.',
    'no_package'       => 'Aucun colis saisi sur ce bon de livraison.',
    'unpacked'         => 'Hors colis',
    'unpacked_help'    => 'Quantités du bon de livraison rangées dans aucun colis.',
    'qty_in_package'   => 'Qté',

    // Saisie du contenu (fiche BL, onglet Packaging)
    'content_help'     => 'Quantité de chaque ligne du BL rangée dans ce colis (vide = aucune).',
    'already_packed'   => 'déjà colisé ailleurs : :qty',

    'errors' => [
        'foreign_line' => "Cette ligne n'appartient pas au bon de livraison du colis.",
        'negative'     => 'La quantité ne peut pas être négative.',
        'over_packed'  => 'Ligne :code : :qty dépasse la quantité restant à coliser (:available).',
    ],
];
