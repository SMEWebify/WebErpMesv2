<?php

return [
    // Modèle d'impression du BL (print/layouts/packing-list)
    'layout'           => 'Packliste',
    'title'            => 'Packliste',
    'delivery_note'    => 'Lieferschein',
    'tracking_number'  => 'Sendungsnummer',
    'package'          => 'Packstück',
    'package_of'       => 'Packstück :number / :count',
    'packages_count'   => 'Anzahl Packstücke',
    'dimensions'       => 'Abmessungen (L × B × H)',
    'net_weight'       => 'Nettogewicht',
    'gross_weight'     => 'Bruttogewicht',
    'total_net_weight' => 'Nettogewicht gesamt',
    'total_gross_weight' => 'Bruttogewicht gesamt',
    'weight_unit'      => 'kg',
    'content'          => 'Inhalt',
    'empty_package'    => 'Keine Position in diesem Packstück.',
    'no_package'       => 'Kein Packstück auf diesem Lieferschein erfasst.',
    'unpacked'         => 'Nicht verpackt',
    'unpacked_help'    => 'Mengen des Lieferscheins, die keinem Packstück zugeordnet sind.',
    'qty_in_package'   => 'Menge',

    // Saisie du contenu (fiche BL, onglet Packaging)
    'content_help'     => 'Menge jeder Lieferscheinposition in diesem Packstück (leer = keine).',
    'already_packed'   => 'bereits anderweitig verpackt: :qty',

    'errors' => [
        'foreign_line' => "Diese Position gehört nicht zum Lieferschein des Packstücks.",
        'negative'     => 'Die Menge darf nicht negativ sein.',
        'over_packed'  => 'Position :code: :qty übersteigt die noch zu verpackende Menge (:available).',
    ],
];
