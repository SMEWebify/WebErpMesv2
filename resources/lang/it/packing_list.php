<?php

return [
    // Modèle d'impression du BL (print/layouts/packing-list)
    'layout'           => 'Packing list',
    'title'            => 'Packing list',
    'delivery_note'    => 'Documento di trasporto',
    'tracking_number'  => 'N. di tracciamento',
    'package'          => 'Collo',
    'package_of'       => 'Collo :number / :count',
    'packages_count'   => 'Numero di colli',
    'dimensions'       => 'Dimensioni (L × P × H)',
    'net_weight'       => 'Peso netto',
    'gross_weight'     => 'Peso lordo',
    'total_net_weight' => 'Peso netto totale',
    'total_gross_weight' => 'Peso lordo totale',
    'weight_unit'      => 'kg',
    'content'          => 'Contenuto',
    'empty_package'    => 'Nessuna riga inserita in questo collo.',
    'no_package'       => 'Nessun collo inserito su questo documento di trasporto.',
    'unpacked'         => 'Fuori collo',
    'unpacked_help'    => 'Quantità del documento di trasporto non inserite in alcun collo.',
    'qty_in_package'   => 'Q.tà',

    // Saisie du contenu (fiche BL, onglet Packaging)
    'content_help'     => 'Quantità di ogni riga del DDT inserita in questo collo (vuoto = nessuna).',
    'already_packed'   => 'già imballato altrove: :qty',

    'errors' => [
        'foreign_line' => 'Questa riga non appartiene al documento di trasporto del collo.',
        'negative'     => 'La quantità non può essere negativa.',
        'over_packed'  => 'Riga :code: :qty supera la quantità ancora da imballare (:available).',
    ],
];
